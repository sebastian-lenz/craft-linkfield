<?php

namespace typedlinkfield\fields;

use craft\base\ElementInterface;
use craft\base\Field;
use typedlinkfield\Plugin;
use typedlinkfield\models\Link;
use typedlinkfield\models\LinkTypeInterface;
use typedlinkfield\validators\LinkFieldValidator;

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
  public $defaultText = '';

  /**
   * @var array
   */
  public $typeSettings = array();


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
    ];

    if (is_string($value)) {
      $attr += array_filter(
        json_decode($value, true) ?: [],
        function ($key) {
          return in_array($key, [ 'customText', 'target', 'type', 'value' ]);
        },
        ARRAY_FILTER_USE_KEY
      );
    } else if (is_array($value)) {
      $attr += [
        'customText' => $this->allowCustomText && isset($value['customText']) ? $value['customText'] : null,
        'target'     => $this->allowTarget && isset($value['target']) ? $value['target'] : null,
        'type'       => isset($value['type']) ? $value['type'] : null,
        'value'      => $this->getLinkValue($value)
      ];
    }

    if (isset($attr['type']) && !$this->isAllowedLinkType($attr['type'])) {
      $attr['type']  = null;
      $attr['value'] = null;
    }

    return new Link($attr);
  }

  /**
   * @return array
   */
  public function getElementValidationRules(): array {
    return [
      LinkFieldValidator::class,
    ];
  }

  /**
   * @param $value
   * @param ElementInterface|null $element
   * @return string
   * @throws \Twig_Error_Loader
   * @throws \yii\base\Exception
   */
  public function getInputHtml($value, ElementInterface $element = null): string {
    $linkTypes = $this->getAllowedLinkTypes();
    $linkNames = [];
    $linkInputs = [];

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
    $linkTypes = Plugin::getInstance()->getLinkTypes();
    $linkNames = [];
    $linkSettings = [];

    foreach ($linkTypes as $linkTypeName => $linkType) {
      $linkNames[$linkTypeName] = $linkType->getDisplayName();
      $linkSettings[] = $linkType->getSettingsHtml($linkTypeName, $this);
    }

    return \Craft::$app->getView()->renderTemplate('typedlinkfield/_settings', [
      'linkNames' => $linkNames,
      'settings'  => $this->getSettings(),
    ]) . implode('', $linkSettings);
  }

  /**
   * @param $value
   * @return bool
   */
  public function isEmpty($value): bool {
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
   * @return LinkTypeInterface[]
   */
  private function getAllowedLinkTypes() {
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
