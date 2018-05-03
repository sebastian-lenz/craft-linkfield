<?php

namespace typedlinkfield\models;

use craft\base\ElementInterface;
use craft\helpers\Html;
use typedlinkfield\fields\LinkField;
use yii\base\Model;

/**
 * Class ElementLinkType
 * @package typedlinkfield\models
 */
class ElementLinkType extends Model implements LinkTypeInterface
{
  /**
   * @var ElementInterface
   */
  public $elementType;

  /**
   * @var string
   */
  public $displayGroup = 'Common';


  /**
   * ElementLinkType constructor.
   * @param string|array $elementType
   * @param array $options
   */
  public function __construct($elementType, array $options = []) {
    if (is_array($elementType)) {
      $options = $elementType;
    } else {
      $options['elementType'] = $elementType;
    }

    parent::__construct($options);
  }

  /**
   * @return array
   */
  public function getDefaultSettings(): array {
    return [
      'sources' => '*',
    ];
  }

  /**
   * @return string
   */
  public function getDisplayName(): string {
    $elementType = $this->elementType;
    return $elementType::displayName();
  }

  /**
   * @return string
   */
  public function getDisplayGroup(): string {
    return \Craft::t('typedlinkfield', $this->displayGroup);
  }

  /**
   * @param Link $link
   * @return null|ElementInterface
   */
  public function getElement(Link $link) {
    if ($this->isEmpty($link)) {
      return null;
    }

    $query = [
      'id' => $link->value,
      'site' => $link->getOwnerSite(),
    ];

    if (\Craft::$app->request->getIsCpRequest()) {
      $query += [
        'enabledForSite' => null,
        'status' => null,
      ];
    }

    $elementType = $this->elementType;
    return $elementType::findOne($query);
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @param Link $value
   * @param ElementInterface $element
   * @return string
   */
  public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element): string {
    $settings   = $field->getLinkTypeSettings($linkTypeName, $this);
    $sources    = $settings['sources'];
    $isSelected = $value->type === $linkTypeName;
    $elements   = $isSelected ? array_filter([$this->getElement($value)]) : null;

    $criteria = [
      'enabledForSite' => null,
      'status' => null,
    ];

    try {
      $criteria['siteId'] = $this->getTargetSiteId($element);
    } catch (\Exception $e) {}

    $selectFieldOptions = [
      'criteria'        => $criteria,
      'elementType'     => $this->elementType,
      'elements'        => $elements,
      'id'              => $field->handle . '-' . $linkTypeName,
      'limit'           => 1,
      'name'            => $field->handle . '[' . $linkTypeName . ']',
      'storageKey'      => 'field.' . $field->handle,
      'sources'         => $sources === '*' ? null : $sources,
      'sourceElementId' => !empty($element->id) ? $element->id : null,
    ];

    try {
      return \Craft::$app->view->renderTemplate('typedlinkfield/_input-element', [
        'isSelected'         => $isSelected,
        'linkTypeName'       => $linkTypeName,
        'selectFieldOptions' => $selectFieldOptions,
      ]);
    } catch (\Throwable $exception) {
      return Html::tag('p', \Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [ 'name' => $this->getDisplayName() ]
      ));
    }
  }

  /**
   * @param ElementInterface|null $element
   * @return int
   * @throws \craft\errors\SiteNotFoundException
   */
  protected function getTargetSiteId(ElementInterface $element = null): int {
    if (\Craft::$app->getIsMultiSite()) {
      if ($element !== null) {
        return $element->siteId;
      }
    }

    return \Craft::$app->getSites()->getCurrentSite()->id;
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  public function getLinkValue($value) {
    return is_array($value) ? $value[0] : null;
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @return string
   */
  public function getSettingsHtml(string $linkTypeName, LinkField $field): string {
    try {
      return \Craft::$app->view->renderTemplate('typedlinkfield/_settings-element', [
        'settings'     => $field->getLinkTypeSettings($linkTypeName, $this),
        'elementName'  => $this->getDisplayName(),
        'linkTypeName' => $linkTypeName,
        'sources'      => $this->getSources(),
      ]);
    } catch (\Throwable $exception) {
      return Html::tag('p', \Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [ 'name' => $this->getDisplayName() ]
      ));
    }
  }

  /**
   * @return array
   */
  protected function getSources() {
    $elementType = $this->elementType;
    $options = array();

    foreach ($elementType::sources() as $source) {
      if (array_key_exists('key', $source) && $source['key'] !== '*') {
        $options[$source['key']] = $source['label'];
      }
    }

    return $options;
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getText(Link $link) {
    $element = $this->getElement($link);
    if (is_null($element)) {
      return null;
    }

    return (string)$element;
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getUrl(Link $link) {
    $element = $this->getElement($link);
    if (is_null($element)) {
      return null;
    }

    return $element->getUrl();
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function hasElement(Link $link): bool {
    $element = $this->getElement($link);
    return !is_null($element);
  }

  /**
   * @param Link $link
   * @return bool
   */
  public function isEmpty(Link $link): bool {
    if (is_numeric($link->value)) {
      return $link->value <= 0;
    }

    return true;
  }

  /**
   * @param LinkField $field
   * @param Link $link
   * @return array|null
   */
  public function validateValue(LinkField $field, Link $link) {
    return null;
  }
}
