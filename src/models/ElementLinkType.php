<?php

namespace typedlinkfield\models;

use Craft;
use craft\base\ElementInterface;
use craft\errors\SiteNotFoundException;
use craft\helpers\Html;
use Exception;
use Throwable;
use typedlinkfield\fields\LinkField;
use typedlinkfield\utilities\ElementSourceValidator;
use typedlinkfield\utilities\Url;
use yii\base\Model;

/**
 * Class ElementLinkType
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
   *
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
   * @inheritDoc
   */
  public function getDefaultSettings(): array {
    return [
      'allowCustomQuery' => false,
      'sources' => '*',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDisplayName(): string {
    $elementType = $this->elementType;
    return $elementType::displayName();
  }

  /**
   * @inheritDoc
   */
  public function getDisplayGroup(): string {
    return Craft::t('typedlinkfield', $this->displayGroup);
  }

  /**
   * @inheritdoc
   */
  public function getElement(Link $link, $ignoreStatus = false) {
    if ($this->isEmpty($link)) {
      return null;
    }

    $query = [
      'id' => $link->value,
      'site' => $link->getOwnerSite(),
    ];

    if ($ignoreStatus || Craft::$app->request->getIsCpRequest()) {
      $query += [
        'enabledForSite' => null,
        'status' => null,
      ];
    }

    $elementType = $this->elementType;
    return $elementType::findOne($query);
  }

  /**
   * @inheritDoc
   */
  public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element = null): string {
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
    } catch (Exception $e) {}

    $selectFieldOptions = [
      'criteria'    => $criteria,
      'elementType' => $this->elementType,
      'elements'    => $elements,
      'id'          => $field->handle . '-' . $linkTypeName . '-value',
      'limit'       => 1,
      'name'        => $field->handle . '[' . $linkTypeName . '][value]',
      'storageKey'  => 'field.' . $field->handle,
      'sources'     => $sources === '*' ? null : $sources,
    ];

    $queryFieldOptions = null;
    if ($settings['allowCustomQuery']) {
      $queryFieldOptions = [
        'disabled'    => $field->isStatic(),
        'id'          => $field->handle . '-' . $linkTypeName . '-customQuery',
        'name'        => $field->handle . '[' . $linkTypeName . '][customQuery]',
        'placeholder' => Craft::t('typedlinkfield', 'Query, starts with "#" or "?"'),
        'value'       => empty($value->customQuery) ? '' : $value->customQuery,
      ];
    }

    try {
      return Craft::$app->view->renderTemplate('typedlinkfield/_input-element', [
        'disabled'           => $field->isStatic(),
        'isSelected'         => $isSelected,
        'linkTypeName'       => $linkTypeName,
        'queryFieldOptions'  => $queryFieldOptions,
        'selectFieldOptions' => $selectFieldOptions,
      ]);
    } catch (Throwable $exception) {
      return Html::tag('p', Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [ 'name' => $this->getDisplayName() ]
      ));
    }
  }

  /**
   * @param ElementInterface|null $element
   * @return int
   * @throws SiteNotFoundException
   */
  protected function getTargetSiteId(ElementInterface $element = null): int {
    if (Craft::$app->getIsMultiSite()) {
      if ($element !== null && property_exists($element, 'siteId')) {
        return $element->siteId;
      }
    }

    return Craft::$app->getSites()->getCurrentSite()->id;
  }

  /**
   * @inheritDoc
   */
  public function getSettingsHtml(string $linkTypeName, LinkField $field): string {
    try {
      return Craft::$app->view->renderTemplate('typedlinkfield/_settings-element', [
        'settings'     => $field->getLinkTypeSettings($linkTypeName, $this),
        'elementName'  => $this->getDisplayName(),
        'linkTypeName' => $linkTypeName,
        'sources'      => $this->getSources(),
      ]);
    } catch (Throwable $exception) {
      return Html::tag('p', Craft::t(
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
   * @inheritDoc
   */
  public function getText(Link $link) {
    $element = $link->getElement();
    if (is_null($element)) {
      return null;
    }

    return (string)$element;
  }

  /**
   * @inheritDoc
   */
  public function getUrl(Link $link) {
    $element = $link->getElement();
    if (is_null($element)) {
      return null;
    }

    $url = $element->getUrl();
    $field = $link->getLinkField();

    // For appending the custom query we need access to the link field
    // instance which might not be available during initial element creation
    if (is_null($field)) {
      return $url;
    }

    $settings = $field->getLinkTypeSettings($link->type, $this);
    $customQuery = is_string($link->customQuery) ? trim($link->customQuery) : '';

    if (
      $settings['allowCustomQuery'] &&
      in_array(substr($customQuery, 0, 1), ['#', '?'])
    ) {
      try {
        $baseUrl = new Url($url);
        $customQueryUrl = new Url($customQuery);

        $baseUrl->setQuery(
          $baseUrl->getQuery() +
          $customQueryUrl->getQuery()
        );

        $fragment = $customQueryUrl->getFragment();
        if (!empty($fragment)) {
          $baseUrl->setFragment($fragment);
        }

        $url = (string)$baseUrl;
      } catch (Throwable $error) {}
    }

    return $url;
  }

  /**
   * @inheritdoc
   */
  public function hasElement(Link $link, $ignoreStatus = false): bool {
    $element = $link->getElement($ignoreStatus);
    return !is_null($element);
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(Link $link): bool {
    if (is_numeric($link->value)) {
      return $link->value <= 0;
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function readLinkValue($formData) {
    if (
      !is_array($formData) ||
      !array_key_exists('value', $formData) ||
      !is_array($formData['value'])
    ) {
      return null;
    }

    return $formData['value'][0];
  }

  /**
   * @inheritdoc
   */
  public function validateSettings(array $settings): array {
    if (
      array_key_exists('sources', $settings) &&
      is_array($settings['sources'])
    ) {
      $settings['sources'] = ElementSourceValidator::apply(
        $this->elementType,
        $settings['sources']
      );
    }

    return $settings;
  }

  /**
   * @inheritDoc
   */
  public function validateValue(LinkField $field, Link $link) {
    return null;
  }
}
