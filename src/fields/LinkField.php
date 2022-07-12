<?php

namespace lenz\linkfield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\MatrixBlock;
use Exception;
use GraphQL\Type\Definition\Type as GraphQLType;
use InvalidArgumentException;
use lenz\craft\utils\foreignField\ForeignField;
use lenz\craft\utils\foreignField\ForeignFieldModel;
use lenz\linkfield\helpers\StringHelper;
use lenz\linkfield\listeners\CacheListenerJob;
use lenz\linkfield\listeners\ElementListenerState;
use lenz\linkfield\models\LinkGqlType;
use lenz\linkfield\models\LinkTypeCollection;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;
use lenz\linkfield\records\LinkRecord;

/**
 * Class LinkField
 *
 * @extends ForeignField<Link, LinkRecord>
 */
class LinkField extends ForeignField
{
  /**
   * @var bool
   */
  public bool $allowCustomText = true;

  /**
   * @var bool
   */
  public bool $allowTarget = false;

  /**
   * @var bool
   */
  public bool $autoNoReferrer = false;

  /**
   * @var int
   */
  public int $customTextMaxLength = 0;

  /**
   * @var bool
   */
  public bool $customTextRequired = false;

  /**
   * @var string
   */
  public string $defaultLinkName = '';

  /**
   * @var string
   */
  public string $defaultText = '';

  /**
   * @var bool
   */
  public bool $enableAriaLabel = false;

  /**
   * @var bool
   */
  public bool $enableAllLinkTypes = true;

  /**
   * @var bool
   */
  public bool $enableElementCache = false;

  /**
   * @var bool
   */
  public bool $enableTitle = false;

  /**
   * @var LinkTypeCollection
   */
  private LinkTypeCollection $_linkTypes;

  /**
   * @var array|null
   */
  private ?array $_linkTypeSettings = null;


  /**
   * @param bool $isNew
   * @throws Exception
   */
  public function afterSave(bool $isNew): void {
    parent::afterSave($isNew);

    ElementListenerState::getInstance()->updateFields();
    CacheListenerJob::createForField($this);
  }

  /**
   * @return LinkTypeCollection
   */
  public function getAvailableLinkTypes(): LinkTypeCollection {
    if (!isset($this->_linkTypes)) {
      $this->_linkTypes = LinkTypeCollection::createForField($this);

      if (!is_null($this->_linkTypeSettings)) {
        $this->_linkTypes->setSettings($this->_linkTypeSettings);
      }
    }

    return $this->_linkTypes;
  }

  /**
   * @inheritDoc
   */
  public function getContentGqlType(): GraphQLType|array {
    return LinkGqlType::getType();
  }

  /**
   * @param ElementInterface|null $element
   * @return LinkTypeCollection
   */
  public function getEnabledLinkTypes(ElementInterface $element = null): LinkTypeCollection {
    $result = $this->enableAllLinkTypes
      ? $this->getAvailableLinkTypes()->clone()
      : $this->getAvailableLinkTypes()->getEnabledTypes();

    $isNewElement = $element && is_null($element->dateCreated);
    if ($this->useEmptyType() && !$isNewElement) {
      $result->enableEmptyType();
    }

    return $result;
  }

  /**
   * (1) Should prevent newly created fields from reverting to the empty type
   * (2) Should detect dummy MatrixBlock blocks created by the cp,
   * prevents defaulting to the empty type, see #221
   *
   * @inheritDoc
   * @throws Exception
   */
  protected function getHtml(ForeignFieldModel $value, ElementInterface $element = null, $disabled = false): string {
    if (
      $value->isEditorEmpty() &&
      $this->useEmptyType() &&
      !is_null($element) &&
      !$value->getLinkType()->isEmptyType() &&
      // (1)
      !$element->getIsUnpublishedDraft() &&
      // (2)
      !($element instanceof MatrixBlock && is_null($element->getId()))
    ) {
      $value = LinkType::getEmptyType()->createLink($this, $element);
    }

    return parent::getHtml($value, $element, $disabled);
  }

  /**
   * @inheritDoc
   */
  public function getSettings(): array {
    $result = parent::getSettings();
    if (array_key_exists('typeSettings', $result)) {
      $result['typeSettings'] = (object)$result['typeSettings'];
    }

    return $result;
  }

  /**
   * @return array
   * @noinspection PhpUnused ($typeSettings getter)
   */
  public function getTypeSettings(): array {
    return $this->getAvailableLinkTypes()->getSettings();
  }

  /**
   * @return bool
   * @noinspection PhpUnused (Used in field template)
   */
  public function hasSingleLinkType(): bool {
    return count($this->getEnabledLinkTypes()) == 1;
  }

  /**
   * @return boolean
   */
  public function hasSettings(): bool {
    return (
      $this->allowCustomText ||
      $this->enableAriaLabel ||
      $this->enableTitle
    );
  }

  /**
   * @param mixed $model
   * @return string|null
   * @noinspection PhpUnused (Used in field template)
   */
  public function resolveSelectedLinkTypeName(mixed $model): ?string {
    $linkType = $this
      ->getEnabledLinkTypes()
      ->getByName($model->getType(), '*');

    return is_null($linkType) ? null : $linkType->name;
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    return array_merge(parent::rules(), [
      ['allowCustomText', 'boolean'],
      ['allowTarget', 'boolean'],
      ['autoNoReferrer', 'boolean'],
      ['customTextMaxLength', 'integer', 'min' => 0],
      ['customTextRequired', 'boolean'],
      ['defaultLinkName', 'string'],
      ['defaultText', 'string'],
      ['enableAriaLabel', 'boolean'],
      ['enableAllLinkTypes', 'boolean'],
      ['enableElementCache', 'boolean'],
      ['enableTitle', 'boolean'],
      ['typeSettings', 'validateTypeSettings'],
    ]);
  }

  /**
   * @inheritDoc
   */
  public function settingsAttributes(): array {
    return array_merge(parent::settingsAttributes(), ['typeSettings']);
  }

  /**
   * @param array $value
   * @noinspection PhpUnused ($typeSettings setter)
   */
  public function setTypeSettings(array $value) {
    $this->_linkTypeSettings = $value;
  }

  /**
   * @return bool
   */
  public function useEmptyType(): bool {
    return !$this->required;
  }

  /**
   * @return void
   * @noinspection PhpUnused (Validator)
   */
  public function validateTypeSettings(): void {
    foreach ($this->getAvailableLinkTypes() as $name => $linkType) {
      if (!$linkType->validate()) {
        $this->addError('typeSettings', 'Invalid link type settings for link type `' . $name . '`.');
        return;
      }
    }
  }

  /**
   * This is primary used to evaluate required fields within the control panel,
   * we consider a field as non-empty if the user has entered either an url or
   * has selected an entry.
   *
   * This does not guarantee that the field actually returns a valid url (e.g.
   * the linked element might not have a url), but for the cp user these cases
   * would not be comprehensible.
   *
   * @inheritDoc
   */
  public function isValueEmpty($value, ElementInterface $element): bool {
    return (!($value instanceof Link) || $value->isEditorEmpty());
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   * @throws Exception
   */
  protected function createModel(array $attributes = [], ElementInterface $element = null): Link {
    $attributes = array_map(
      fn($value) => is_string($value) ? StringHelper::decodeMb4($value) : $value,
      $attributes
    );

    return $this
      ->resolveLinkType($attributes['type'] ?? '', $element)
      ->createLink($this, $element, $attributes);
  }

  /**
   * @param string $linkName
   * @param ElementInterface|null $element
   * @return LinkType
   */
  protected function resolveLinkType(string $linkName, ElementInterface $element = null): LinkType {
    $result = $this
      ->getEnabledLinkTypes($element)
      ->getByName($linkName, $this->defaultLinkName, '*');

    return is_null($result)
      ? new LinkType()
      : $result;
  }

  /**
   * @inheritDoc
   */
  protected function toRecordAttributes(ForeignFieldModel $model, ElementInterface $element): array {
    if (!($model instanceof Link)) {
      throw new InvalidArgumentException('$model must be an instance of Link');
    }

    return array_map(
      fn($value) => is_string($value) ? StringHelper::encodeMb4($value) : $value,
      $model->getLinkType()->toRecordAttributes($model)
    );
  }


  // Static methods
  // --------------

  /**
   * @return string
   */
  static public function displayName(): string {
    return self::t('Link field');
  }

  /**
   * @inheritDoc
   */
  public static function inputTemplate(): string {
    return 'typedlinkfield/_input';
  }

  /**
   * @inheritDoc
   */
  public static function modelClass(): string {
    return Link::class;
  }

  /**
   * @inheritDoc
   */
  public static function queryExtensionClass(): string {
    return LinkFieldQueryExtension::class;
  }

  /**
   * @inheritDoc
   */
  public static function recordClass(): string {
    return LinkRecord::class;
  }

  /**
   * @inheritDoc
   */
  public static function recordModelAttributes(): array {
    return [
      'linkedId',
      'linkedSiteId',
      'linkedTitle',
      'linkedUrl' ,
      'payload',
      'type',
    ];
  }

  /**
   * @inheritDoc
   */
  public static function settingsTemplate(): ?string {
    return 'typedlinkfield/_settings';
  }

  /**
   * @inheritDoc
   */
  public static function supportedTranslationMethods(): array {
    return [
      self::TRANSLATION_METHOD_NONE,
      self::TRANSLATION_METHOD_SITE,
      self::TRANSLATION_METHOD_SITE_GROUP,
      self::TRANSLATION_METHOD_LANGUAGE,
      self::TRANSLATION_METHOD_CUSTOM,
    ];
  }

  /**
   * @inheritDoc
   */
  public static function t(string $message): string {
    return Craft::t('typedlinkfield', $message);
  }
}
