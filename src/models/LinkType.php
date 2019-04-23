<?php

namespace lenz\linkfield\models;

use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\records\LinkRecord;
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
  public $enabled = true;

  /**
   * @var string
   */
  public $name;

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
   * @param Link $link
   * @param LinkRecord $record
   */
  public function createRecord(Link $link, LinkRecord $record) {
    $payload = [];
    $recordAttributes = $record->attributes();
    $linkAttributes = $link->getAttributes() + [
      'linkedId'     => null,
      'linkedSiteId' => null,
      'linkedTitle'  => null,
      'linkedUrl'    => null,
    ];

    foreach ($linkAttributes as $name => $value) {
      if (in_array($name, $recordAttributes)) {
        $record->$name = $value;
      } else if (!empty($value)) {
        $payload[$name] = $value;
      }
    }

    $record->payload = \craft\helpers\Json::encode($payload);
    $record->type = $link->getType();
  }

  /**
   * @return string
   */
  public function getDisplayGroup(): string {
    return \Craft::t('typedlinkfield', $this->displayGroup);
  }

  /**
   * @return string
   */
  public function getDisplayName(): string {
    return 'Empty link';
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


  // Protected methods
  // -----------------

  /**
   * @param Link $value
   * @param string $name
   * @param array $data
   * @return array
   */
  protected function getFieldSettings(Link $value, string $name, array $data = []) {
    $fieldHandle = $value->getField()->handle;
    $typeName    = $this->name;

    return [
      'id'   => "{$fieldHandle}-cpForm-{$typeName}-{$name}",
      'name' => "{$fieldHandle}[cpForm][{$typeName}][{$name}]",
    ] + $data;
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
  protected function prepareLegacyData($data) {
    return null;
  }
}
