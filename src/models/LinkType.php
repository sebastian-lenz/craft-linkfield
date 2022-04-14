<?php

namespace lenz\linkfield\models;

use Craft;
use craft\base\ElementInterface;
use lenz\craft\utils\helpers\ArrayHelper;
use lenz\linkfield\fields\LinkField;
use Throwable;
use yii\base\Model;
use yii\behaviors\AttributeTypecastBehavior;
use yii\helpers\Json;

/**
 * Class LinkType
 */
class LinkType extends Model
{
  /**
   * @var string
   */
  public string $displayGroup = 'Common';

  /**
   * @var bool
   */
  public bool $enabled = false;

  /**
   * @var string
   */
  public string $name = '';

  /**
   * @var string
   */
  protected string $_translatedDisplayGroup;

  /**
   * The class of the link model associated with this type.
   * @var string
   */
  const MODEL_CLASS = Link::class;


  /**
   * @inheritDoc
   */
  public function behaviors(): array {
    return [
      'typecast' => [
        'class' => AttributeTypecastBehavior::class,
      ],
    ];
  }

  /**
   * @param LinkField $field
   * @param ElementInterface|null $owner
   * @param array $value
   * @return Link
   */
  public function createLink(LinkField $field, ElementInterface $owner = null, array $value = []): Link {
    // If the value includes a payload, merge it
    if (isset($value['payload'])) {
      $data = Json::decode($value['payload']);
      unset($value['payload']);

      if (is_array($data)) {
        $value += $data;
      }
    }

    // If the value includes cp form data, merge it
    if (isset($value['cpForm'])) {
      $data = ArrayHelper::get($value['cpForm'], $this->name);
      unset($value['cpForm']);

      if (is_array($data)) {
        $value += $this->prepareFormData($data);
      }
    }

    // If the value includes a value field, it is probably an old format revision
    if (isset($value['value'])) {
      $data = $this->prepareLegacyData($value['value']);
      unset($value['value']);

      if (is_array($data)) {
        $value += $data;
      }
    }

    $modelClass = static::MODEL_CLASS;
    return new $modelClass($field, $this, $owner, $value);
  }

  /**
   * @return string
   */
  public function getTranslatedDisplayGroup(): string {
    if (!isset($this->_translatedDisplayGroup)) {
      $this->_translatedDisplayGroup = Craft::t('typedlinkfield', $this->displayGroup);
    }

    return $this->_translatedDisplayGroup;
  }

  /**
   * @return string
   */
  public function getDisplayName(): string {
    return '(No link)';
  }

  /**
   * @param Link $value
   * @param bool $disabled
   * @return string
   * @throws Throwable
   */
  public function getInputHtml(Link $value, bool $disabled): string {
    return '';
  }

  /**
   * @return array
   */
  public function getSettings(): array {
    $settings = [];
    foreach ($this->settingsAttributes() as $attribute) {
      $settings[$attribute] = $this->$attribute;
    }

    return $settings;
  }

  /**
   * @param LinkField $field
   * @return string
   * @throws Throwable
   * @noinspection PhpUnusedParameterInspection
   */
  public function getSettingsHtml(LinkField $field): string {
    return '';
  }

  /**
   * @return bool
   */
  public function isEmptyType(): bool {
    return $this === self::getEmptyType();
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function isSelected(Link $link): bool {
    return $link->getLinkType() === $this;
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    return [
      ['enabled', 'boolean'],
    ];
  }

  /**
   * @param array $settings
   */
  public function setSettings(array $settings): void {
    foreach ($settings as $name => $value) {
      try {
        $this->$name = $value;
      } catch (Throwable $error) {
        Craft::error(sprintf(
          'Error while trying to set config value "%s" on link field type "%s": %s',
          $name,
          $this->getDisplayName(),
          $error
        ), __METHOD__);
      }
    }
  }

  /**
   * @return array
   */
  public function settingsAttributes(): array {
    return ['enabled'];
  }

  /**
   * @param Link $model
   * @return array
   */
  public function toRecordAttributes(Link $model): array {
    if ($model->isEditorEmpty() && $model->getField()->useEmptyType()) {
      return $this->getEmptyRecordAttributes();
    }

    $attributes       = [];
    $payload          = [];
    $recordAttributes = LinkField::recordModelAttributes();
    $modelAttributes  = $model->getAttributes() + [
      'linkedId'     => null,
      'linkedSiteId' => null,
      'linkedTitle'  => null,
      'linkedUrl'    => null,
    ];

    foreach ($modelAttributes as $name => $value) {
      if (in_array($name, $recordAttributes)) {
        $attributes[$name] = $value;
      } else if (!empty($value)) {
        $payload[$name] = $value;
      }
    }

    $attributes['payload'] = \craft\helpers\Json::encode($payload);
    $attributes['type'] = $this->name;
    return $attributes;
  }


  // Protected methods
  // -----------------

  /**
   * @return array
   */
  protected function getEmptyRecordAttributes(): array {
    $result = [];
    foreach (LinkField::recordModelAttributes() as $attribute) {
      $result[$attribute] = null;
    }

    $result['type'] = $this->name;
    return $result;
  }

  /**
   * @param array $data
   * @return array
   */
  protected function prepareFormData(array $data): array {
    return $data;
  }

  /**
   * @param mixed $data
   * @return array|null
   */
  protected function prepareLegacyData(mixed $data): ?array {
    return null;
  }


  // Static methods
  // --------------

  /**
   * @return LinkType
   */
  public static function getEmptyType(): LinkType {
    static $type;
    if (!isset($type)) {
      $type = new LinkType();
      $type->enabled = true;
    }

    return $type;
  }
}
