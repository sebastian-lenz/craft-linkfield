<?php

namespace lenz\linkfield\fields;

use Craft;
use craft\base\ElementInterface;
use Exception;
use InvalidArgumentException;
use lenz\craft\utils\foreignField\ForeignField;
use lenz\craft\utils\foreignField\ForeignFieldModel;
use lenz\linkfield\listeners\CacheListenerJob;
use lenz\linkfield\listeners\ElementListenerState;
use lenz\linkfield\models\LinkGqlType;
use lenz\linkfield\Plugin;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;
use lenz\linkfield\records\LinkRecord;

/**
 * Class LinkField
 */
class LinkField extends ForeignField
{
  /**
   * @var bool
   */
  public $allowCustomText = true;

  /**
   * @var bool
   */
  public $allowTarget = false;

  /**
   * @var bool
   */
  public $autoNoReferrer = false;

  /**
   * @var string
   */
  public $defaultLinkName = '';

  /**
   * @var string
   */
  public $defaultText = '';

  /**
   * @var bool
   */
  public $enableAriaLabel = false;

  /**
   * @var bool
   */
  public $enableAllLinkTypes = true;

  /**
   * @var bool
   */
  public $enableElementCache = false;

  /**
   * @var bool
   */
  public $enableTitle = false;

  /**
   * @var LinkType[]
   */
  private $_linkTypes;


  /**
   * @param bool $isNew
   * @throws Exception
   */
  public function afterSave(bool $isNew) {
    parent::afterSave($isNew);

    ElementListenerState::getInstance()->updateFields();
    CacheListenerJob::createForField($this);
  }

  /**
   * @return LinkType[]
   */
  public function getAvailableLinkTypes() {
    if (!isset($this->_linkTypes)) {
      $linkTypes = Plugin::getLinkTypes($this);
      foreach ($linkTypes as $name => $linkType) {
        $linkType->name = $name;
      }

      uasort($linkTypes, function(LinkType $a, LinkType $b) {
        return $a->getTranslatedDisplayGroup() === $b->getTranslatedDisplayGroup()
          ? strcmp($a->getDisplayName(), $b->getDisplayName())
          : strcmp($a->getTranslatedDisplayGroup(), $b->getTranslatedDisplayGroup());
      });

      $this->_linkTypes = $linkTypes;
    }

    return $this->_linkTypes;
  }

  /**
   * @return string[]
   */
  public function getAvailableLinkTypeNames() {
    $linkNames = array_map(function(LinkType $type) {
      return $type->getDisplayName();
    }, $this->getAvailableLinkTypes());

    asort($linkNames);
    return $linkNames;
  }

  /**
   * @inheritDoc
   */
  public function getContentGqlType() {
    return LinkGqlType::getType();
  }

  /**
   * @return LinkType[]
   */
  public function getEnabledLinkTypes() {
    $linkTypes = $this->getAvailableLinkTypes();
    if ($this->useEmptyType()) {
      $linkTypes['empty'] = LinkType::getEmptyType();
    }

    if ($this->enableAllLinkTypes) {
      return $linkTypes;
    }

    return array_filter(
      $linkTypes,
      function(LinkType $linkType) {
        return $linkType->enabled;
      }
    );
  }

  /**
   * @return string[]
   */
  public function getEnabledLinkTypeNames() {
    $linkNames = array_map(function(LinkType $type) {
      return $type->getDisplayName();
    }, $this->getEnabledLinkTypes());

    asort($linkNames);
    return $linkNames;
  }

  /**
   * @inheritDoc
   */
  protected function getHtml(ForeignFieldModel $value, ElementInterface $element = null, $disabled = false) {
    if (
      $value->isEditorEmpty() &&
      $this->useEmptyType() &&
      !is_null($element) &&
      !$element->getIsUnsavedDraft() &&
      !$value->getLinkType()->isEmptyType()
    ) {
      $value = LinkType::getEmptyType()->createLink($this, $element);
    }

    return parent::getHtml($value, $element, $disabled);
  }

  /**
   * @return array
   */
  public function getTypeSettings() {
    return array_map(function(LinkType $linkType) {
      return $linkType->getSettings();
    }, $this->getAvailableLinkTypes());
  }

  /**
   * @return bool
   */
  public function hasSingleLinkType() {
    return count($this->getEnabledLinkTypeNames()) == 1;
  }

  /**
   * @return boolean
   */
  public function hasSettings() {
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
  public function resolveSelectedLinkTypeName($model) {
    $linkTypes = $this->getEnabledLinkTypes();
    if (count($linkTypes) === 1) {
      return array_keys($linkTypes)[0];
    }

    return $model instanceof Link ? $model->getType() : null;
  }

  /**
   * @inheritDoc
   */
  public function rules() {
    return array_merge(parent::rules(), [
      ['allowCustomText', 'boolean'],
      ['allowTarget', 'boolean'],
      ['autoNoReferrer', 'boolean'],
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
    $attributes = parent::settingsAttributes();
    $attributes[] = 'typeSettings';
    return $attributes;
  }

  /**
   * @param array $value
   */
  public function setTypeSettings(array $value) {
    foreach ($this->getAvailableLinkTypes() as $linkName => $linkType) {
      if (array_key_exists($linkName, $value)) {
        $linkType->setSettings($value[$linkName]);
      }
    }
  }

  /**
   * @return void
   */
  public function validateTypeSettings() {
    foreach ($this->getAvailableLinkTypes() as $name => $linkType) {
      if (!$linkType->validate()) {
        $this->addError('linkTypes', 'Invalid link type settings for link type `' . $name . '`.');
        return;
      }
    }
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function createModel(array $attributes = [], ElementInterface $element = null) {
    return $this
      ->resolveLinkType(isset($attributes['type']) ? $attributes['type'] : '')
      ->createLink($this, $element, $attributes);
  }

  /**
   * @return LinkType|null
   */
  protected function getDefaultLinkType() {
    $linkName = $this->defaultLinkName;
    if (empty($linkName)) {
      return null;
    }

    $allowedLinkTypes = $this->getEnabledLinkTypes();
    return array_key_exists($linkName, $allowedLinkTypes)
      ? $allowedLinkTypes[$linkName]
      : null;
  }

  /**
   * @param Link $value
   * @param ElementInterface $element
   * @return Link
   */
  protected function getEditLink(Link $value, ElementInterface $element = null) {
    $defaultLinkType = $this->getDefaultLinkType();
    if (
      $value->isEmpty() &&
      !is_null($defaultLinkType) &&
      $value->getLinkType() !== $defaultLinkType
    ) {
      $value = $defaultLinkType->createLink($this, $value->getOwner(), $value->getAttributes());
    }

    $value->setOwner($element);
    return $value;
  }

  /**
   * @param string $linkName
   * @return LinkType
   */
  protected function resolveLinkType(string $linkName) {
    $allowedLinkTypes = $this->getEnabledLinkTypes();
    if (array_key_exists($linkName, $allowedLinkTypes)) {
      return $allowedLinkTypes[$linkName];
    }

    if (array_key_exists($this->defaultLinkName, $allowedLinkTypes)) {
      return $allowedLinkTypes[$this->defaultLinkName];
    }

    if (count($allowedLinkTypes) > 0) {
      return reset($allowedLinkTypes);
    }

    return new LinkType();
  }

  /**
   * @inheritDoc
   */
  protected function toRecordAttributes(ForeignFieldModel $model, ElementInterface $element) {
    if (!($model instanceof Link)) {
      throw new InvalidArgumentException('$model mus be an instance of Link');
    }

    return $model->getLinkType()
      ->toRecordAttributes($model);
  }

  /**
   * @return bool
   */
  protected function useEmptyType() {
    return !$this->required;
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
  public static function settingsTemplate() {
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
