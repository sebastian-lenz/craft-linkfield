<?php

namespace linkfield\models;

use craft\base\ElementInterface;
use craft\helpers\Html;
use linkfield\fields\LinkField;
use yii\base\Model;

/**
 * Class InputLinkType
 * @package linkfield\models
 */
class InputLinkType extends Model implements LinkTypeInterface
{
  /**
   * @var string
   */
  public $displayName;

  /**
   * @var string
   */
  public $inputType;

  /**
   * @var string
   */
  public $placeholder;


  /**
   * ElementLinkType constructor.
   * @param string $displayName
   * @param array $options
   */
  public function __construct(string $displayName, array $options = []) {
    parent::__construct($options);
    $this->displayName = $displayName;
  }

  /**
   * @return array
   */
  public function getDefaultSettings(): array {
    return [];
  }

  /**
   * @return string
   */
  public function getDisplayName(): string {
    return \Craft::t('linkfield', $this->displayName);
  }

  /**
   * @param Link $link
   * @return ElementInterface|null
   */
  public function getElement(Link $link) {
    return null;
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @param Link $value
   * @param ElementInterface $element
   * @return string
   */
  public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element): string {
    $isSelected = $value->type === $linkTypeName;
    $value      = $isSelected ? $value->value : '';

    $textFieldOptions = [
      'id'    => $field->handle . '-' . $linkTypeName,
      'name'  => $field->handle . '[' . $linkTypeName . ']',
      'value' => $value,
    ];

    if (isset($this->inputType)) {
      $textFieldOptions['type'] = $this->inputType;
    }

    if (isset($this->placeholder)) {
      $textFieldOptions['placeholder'] = \Craft::t('linkfield', $this->placeholder);
    }

    try {
      return \Craft::$app->view->renderTemplate('linkfield/_input-input', [
        'isSelected'       => $isSelected,
        'linkTypeName'     => $linkTypeName,
        'textFieldOptions' => $textFieldOptions,
      ]);
    } catch (\Throwable $exception) {
      return Html::tag('p', \Craft::t(
        'linkfield',
        'Error: Could not render the template for the field `{name}`.',
        [ 'name' => $this->getDisplayName() ]
      ));
    }
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  public function getLinkValue($value) {
    return is_string($value) ? $value : '';
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @return string
   */
  public function getSettingsHtml(string $linkTypeName, LinkField $field): string {
    return '';
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getText(Link $link) {
    return null;
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getUrl(Link $link) {
    if ($this->isEmpty($link)) {
      return null;
    }

    switch ($this->inputType) {
      case('email'):
        return 'mailto:' . $link->value;
      case('tel'):
        return 'tel:' . $link->value;
      default:
        return $link->value;
    }
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function hasElement(Link $link): bool {
    return false;
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function isEmpty(Link $link): bool {
    if (is_string($link->value)) {
      return trim($link->value) === '';
    }

    return true;
  }

  /**
   * @param Link $link
   * @return array|null
   */
  public function validateValue(Link $link) {
    if ($this->isEmpty($link)) {
      return null;
    }

    $value = $link->value;

    switch ($this->inputType) {
      case('email'):
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          return [\Craft::t('linkfield', 'Please enter a valid email address.'), []];
        }
        break;

      case('tel'):
        $regexp = '/^[0-9+\(\)#\.\s\/ext-]+$/';
        if (!filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $regexp)))) {
          return [\Craft::t('linkfield', 'Please enter a valid phone number.'), []];
        }
        break;

      case('custom'):
        if (!filter_var($value, FILTER_VALIDATE_URL) && $this->value == '#') {
          return [\Craft::t('linkfield', 'Please enter a valid url.'), []];
        }
        break;
    }

    return null;
  }
}
