<?php

namespace lenz\linkfield\models;

use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use lenz\linkfield\fields\LinkField;
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
  public $displayGroup = 'Common';

  /**
   * @var bool
   */
  public $enabled = false;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  protected $_translatedDisplayGroup;

  /**
   * The class of the link model associated with this type.
   * @var string
   */
  const MODEL_CLASS = Link::class;


  /**
   * @inheritDoc
   */
  public function behaviors() {
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
  public function createLink(LinkField $field, ElementInterface $owner = null, $value = []): Link {
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
      $data = ArrayHelper::getValue($value['cpForm'], $this->name);
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
      $this->_translatedDisplayGroup = \Craft::t('typedlinkfield', $this->displayGroup);
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
   * @throws \Throwable
   */
  public function getInputHtml(Link $value, bool $disabled): string {
    return '';
  }

  /**
   * @return array
   */
  public function getSettings() {
    $settings = [];
    foreach ($this->settingsAttributes() as $attribute) {
      $settings[$attribute] = $this->$attribute;
    }

    return $settings;
  }

  /**
   * @param LinkField $field
   * @return string
   * @throws \Throwable
   */
  public function getSettingsHtml(LinkField $field): string {
    return '';
  }

  /**
   * @return bool
   */
  public function isEmptyType() {
    return $this === self::getEmptyType();
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function isSelected(Link $link) {
    return $link->getLinkType() === $this;
  }

  /**
   * @inheritDoc
   */
  public function rules() {
    return [
      ['enabled', 'boolean'],
    ];
  }

  /**
   * @param array $settings
   */
  public function setSettings(array $settings) {
    \Yii::configure($this, $settings);
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
  public function toRecordAttributes(Link $model) {
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
    $attributes['type'] = $model->getType();
    return $attributes;
  }


  // Protected methods
  // -----------------

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
  protected function prepareLegacyData($data) {
    return null;
  }


  // Static methods
  // --------------

  /**
   * @return LinkType
   */
  public static function getEmptyType() {
    static $type;
    if (!isset($type)) {
      $type = new LinkType();
      $type->enabled = true;
    }

    return $type;
  }
}
