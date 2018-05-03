<?php

namespace typedlinkfield\fields;

use craft\base\ElementInterface;
use craft\base\Field;
use typedlinkfield\Plugin;
use typedlinkfield\models\Link;
use typedlinkfield\models\LinkTypeInterface;
use typedlinkfield\validators\LinkFieldValidator;
use yii\db\Schema;

/**
 * Class LinkField
 * @package typedlinkfield\fields
 */
class LinkField extends Field
{
  /**
   * @var bool
   */
  public $allowCustomText = true;

  /**
   * @var string|array
   */
  public $allowedLinkNames = '*';

  /**
   * @var bool
   */
  public $allowTarget = false;

  /**
   * @var string
   */
  public $defaultLinkName = '';

  /**
   * @var string
   */
  public $defaultText = '';

  /**
   * @var array
   */
  public $typeSettings = array();


  /**
   * @param bool $isNew
   * @return bool
   */
  public function beforeSave(bool $isNew): bool {
    $this->allowedLinkNames = array_filter($this->allowedLinkNames);
    foreach ($this->allowedLinkNames as $linkName) {
      if ($linkName === '*') {
        $this->allowedLinkNames = '*';
        break;
      }
    }

    return parent::beforeSave($isNew);
  }

  /**
   * Get Content Column Type
   * Used to set the correct column type in the DB
   * @return string
   */
  public function getContentColumnType(): string {
    return Schema::TYPE_TEXT;
  }

  /**
   * @param $value
   * @param ElementInterface|null $element
   * @return Link
   */
  public function normalizeValue($value, ElementInterface $element = null) {
    if ($value instanceof Link) {
      return $value;
    }

    $attr = [
      'allowCustomText' => $this->allowCustomText,
      'allowTarget'     => $this->allowTarget,
      'defaultText'     => $this->defaultText,
      'owner'           => $element,
    ];

    // If value is a string we are loading the data from the database
    if (is_string($value)) {
      $attr += array_filter(
        json_decode($value, true) ?: [],
        function ($key) {
          return in_array($key, [ 'customText', 'target', 'type', 'value' ]);
        },
        ARRAY_FILTER_USE_KEY
      );

      // If it is an array and the field `isCpFormData` is set, we are saving a cp form
    } else if (is_array($value) && isset($value['isCpFormData'])) {
      $attr += [
        'customText' => $this->allowCustomText && isset($value['customText']) ? $value['customText'] : null,
        'target'     => $this->allowTarget && isset($value['target']) ? $value['target'] : null,
        'type'       => isset($value['type']) ? $value['type'] : null,
        'value'      => $this->getLinkValue($value)
      ];

      // Finally, if it is an array it is a serialized value
    } elseif (is_array($value)) {
      $attr = [
        'owner' => $element,
      ] + $value;
    }

    if (isset($attr['type']) && !$this->isAllowedLinkType($attr['type'])) {
      $attr['type']  = null;
      $attr['value'] = null;
    }

    return new Link($attr);
  }

  /**
   * @return LinkTypeInterface[]
   */
  public function getAllowedLinkTypes() {
    $allowedLinkNames = $this->allowedLinkNames;
    $linkTypes = Plugin::getInstance()->getLinkTypes();

    if (is_string($allowedLinkNames)) {
      if ($allowedLinkNames === '*') {
        return $linkTypes;
      }

      $allowedLinkNames = [$allowedLinkNames];
    }

    return array_filter($linkTypes, function($linkTypeName) use ($allowedLinkNames) {
      return in_array($linkTypeName, $allowedLinkNames);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * @return array
   */
  public function getElementValidationRules(): array {
    return [
      [LinkFieldValidator::class, 'field' => $this],
    ];
  }

  /**
   * @param Link $value
   * @param ElementInterface|null $element
   * @return string
   * @throws \Twig_Error_Loader
   * @throws \yii\base\Exception
   */
  public function getInputHtml($value, ElementInterface $element = null): string {
    $linkTypes = $this->getAllowedLinkTypes();
    $linkNames = [];
    $linkInputs = [];
    $singleType = count($linkTypes) === 1 ? array_keys($linkTypes)[0] : null;

    if (!array_key_exists($value->type, $linkTypes) && count($linkTypes) > 0) {
      $value->type = array_keys($linkTypes)[0];
      $value->value = null;
    }

    if (
      $value->isEmpty() &&
      !empty($this->defaultLinkName) &&
      array_key_exists($this->defaultLinkName, $linkTypes)
    ) {
      $value->type = $this->defaultLinkName;
    }

    foreach ($linkTypes as $linkTypeName => $linkType) {
      $linkNames[$linkTypeName] = $linkType->getDisplayName();
      $linkInputs[] = $linkType->getInputHtml($linkTypeName, $this, $value, $element);
    }

    return \Craft::$app->getView()->renderTemplate('typedlinkfield/_input', [
      'linkInputs' => implode('', $linkInputs),
      'linkNames'  => $linkNames,
      'name'       => $this->handle,
      'nameNs'     => \Craft::$app->view->namespaceInputId($this->handle),
      'settings'   => $this->getSettings(),
      'singleType' => $singleType,
      'value'      => $value,
    ]);
  }

  /**
   * @param string $linkTypeName
   * @param LinkTypeInterface $linkType
   * @return array
   */
  public function getLinkTypeSettings(string $linkTypeName, LinkTypeInterface $linkType): array {
    $settings = $linkType->getDefaultSettings();
    if (array_key_exists($linkTypeName, $this->typeSettings)) {
      $settings = $this->typeSettings[$linkTypeName] + $settings;
    }

    return $settings;
  }

  /**
   * @return string
   * @throws \Twig_Error_Loader
   * @throws \yii\base\Exception
   */
  public function getSettingsHtml() {
    $settings = $this->getSettings();
    $allowedLinkNames = $settings['allowedLinkNames'];
    $linkTypes = [];
    $linkNames = [];
    $linkSettings = [];

    $allTypesAllowed = false;
    if (!is_array($allowedLinkNames)) {
      $allTypesAllowed = $allowedLinkNames == '*';
    } else {
      foreach ($allowedLinkNames as $linkName) {
        if ($linkName === '*') {
          $allTypesAllowed = true;
          break;
        }
      }
    }

    foreach (Plugin::getInstance()->getLinkTypes() as $linkTypeName => $linkType) {
      $linkTypes[] = array(
        'displayName' => $linkType->getDisplayName(),
        'enabled'     => $allTypesAllowed || (is_array($allowedLinkNames) && in_array($linkTypeName, $allowedLinkNames)),
        'name'        => $linkTypeName,
        'group'       => $linkType->getDisplayGroup(),
        'settings'    => $linkType->getSettingsHtml($linkTypeName, $this),
      );

      $linkNames[$linkTypeName] = $linkType->getDisplayName();
      $linkSettings[] = $linkType->getSettingsHtml($linkTypeName, $this);
    }

    asort($linkNames);
    usort($linkTypes, function($a, $b) {
      return $a['group'] === $b['group']
        ? strcmp($a['displayName'], $b['displayName'])
        : strcmp($a['group'], $b['group']);
    });

    return \Craft::$app->getView()->renderTemplate('typedlinkfield/_settings', [
      'allTypesAllowed' => $allTypesAllowed,
      'name'            => 'linkField',
      'nameNs'          => \Craft::$app->view->namespaceInputId('linkField'),
      'linkTypes'       => $linkTypes,
      'linkNames'       => $linkNames,
      'settings'        => $settings,
    ]);
  }

  /**
   * @param $value
   * @param ElementInterface $element
   * @return bool
   */
  public function isValueEmpty($value, ElementInterface $element): bool {
    if ($value instanceof Link) {
      return $value->isEmpty();
    }

    return true;
  }

  /**
   * @param string $type
   * @return bool
   */
  private function isAllowedLinkType($type) {
    $allowedLinkTypes = $this->getAllowedLinkTypes();
    return array_key_exists($type, $allowedLinkTypes);
  }

  /**
   * @param array $data
   * @return mixed
   */
  private function getLinkValue(array $data) {
    $linkTypes = Plugin::getInstance()->getLinkTypes();
    $type = $data['type'];
    if (!array_key_exists($type, $linkTypes)) {
      return null;
    }

    return $linkTypes[$type]->getLinkValue($data[$type]);
  }

  /**
   * @return string
   */
  static public function displayName(): string {
    return \Craft::t('typedlinkfield', 'Link field');
  }
}
