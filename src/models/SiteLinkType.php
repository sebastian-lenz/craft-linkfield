<?php

namespace typedlinkfield\models;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\models\Site;
use typedlinkfield\fields\LinkField;
use typedlinkfield\utilities\ElementSourceValidator;
use yii\base\Model;

/**
 * Class SiteLinkType
 * @package typedlinkfield\models
 */
class SiteLinkType extends Model implements LinkTypeInterface
{
  /**
   * @var string
   */
  public $displayGroup = 'Common';

  /**
   * @var string
   */
  public $displayName;


  /**
   * SiteLinkType constructor.
   * @param string|array $displayName
   * @param array $options
   */
  public function __construct($displayName, array $options = []) {
    if (is_array($displayName)) {
      $options = $displayName;
    } else {
      $options['displayName'] = $displayName;
    }

    parent::__construct($options);
  }

  /**
   * @return array
   */
  public function getDefaultSettings(): array {
    return [
      'sites' => '*',
    ];
  }

  /**
   * @return string
   */
  public function getDisplayName(): string {
    return \Craft::t('typedlinkfield', $this->displayName);
  }

  /**
   * @return string
   */
  public function getDisplayGroup(): string {
    return \Craft::t('typedlinkfield', $this->displayGroup);
  }

  /**
   * @param Link $link
   * @return null|Site
   */
  public function getSite(Link $link) {
    if ($this->isEmpty($link)) {
      return null;
    }

    return Craft::$app->getSites()->getSiteById($link->value);
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @param Link $value
   * @param ElementInterface $element
   * @return string
   */
  public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element): string {
    $settings     = $field->getLinkTypeSettings($linkTypeName, $this);
    $siteIds      = $settings['sites'];
    $isSelected   = $value->type === $linkTypeName;
    $selectedSite = $isSelected ? $this->getSite($value) : null;

    $selectFieldOptions = [
      'disabled' => $field->isStatic(),
      'id'       => $field->handle . '-' . $linkTypeName,
      'name'     => $field->handle . '[' . $linkTypeName . ']',
      'options'  => $this->getSiteOptions($siteIds),
      'value'    => $selectedSite->id ?? null,
    ];

    try {
      return \Craft::$app->view->renderTemplate('typedlinkfield/_input-select', [
        'isSelected'         => $isSelected,
        'linkTypeName'       => $linkTypeName,
        'selectFieldOptions' => $selectFieldOptions,
      ]);
    } catch (\Throwable $exception) {
      return Html::tag('p', \Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [
          'name' => $this->getDisplayName()
        ]
      ));
    }
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  public function getLinkValue($value) {
    return $value ?? null;
  }

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @return string
   */
  public function getSettingsHtml(string $linkTypeName, LinkField $field): string {
    try {
      return \Craft::$app->view->renderTemplate('typedlinkfield/_settings-site', [
        'settings'     => $field->getLinkTypeSettings($linkTypeName, $this),
        'elementName'  => $this->getDisplayName(),
        'linkTypeName' => $linkTypeName,
        'siteOptions'  => $this->getSiteOptions(),
      ]);
    } catch (\Throwable $exception) {
      return Html::tag('p', \Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [
          'name' => $this->getDisplayName()
        ]
      ));
    }
  }

  /**
   * @param string|array|null $siteIds
   * @return array
   */
  protected function getSiteOptions($siteIds = null) {
    if ($siteIds === '*') {
      $siteIds = null;
    } elseif ($siteIds === '') {
      $siteIds = [];
    }

    $options = array_map(function ($site) use ($siteIds) {
      if (!$site->hasUrls || (is_array($siteIds) && !in_array($site->id, $siteIds))) {
        return null;
      }

      return [
        'value' => $site->id,
        'label' => $site->name
      ];
    }, Craft::$app->getSites()->getAllSites());

    return array_filter($options);
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getText(Link $link) {
    $site = $this->getSite($link);
    if (is_null($site)) {
      return null;
    }

    return (string)$site;
  }

  /**
   * @param Link $link
   * @return null|string
   */
  public function getUrl(Link $link) {
    $site = $this->getSite($link);
    if (is_null($site)) {
      return null;
    }

    return Craft::getAlias($site->baseUrl);
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
   * @inheritdoc
   */
  public function validateSettings(array $settings): array {
    return $settings;
  }

  /**
   * @param LinkField $field
   * @param Link $link
   * @return array|null
   */
  public function validateValue(LinkField $field, Link $link) {
    return null;
  }

  /**
   * @inheritdoc
   */
  public function getElement(Link $link, $ignoreStatus = false) {
    return null;
  }

  /**
   * @inheritdoc
   */
  public function hasElement(Link $link, $ignoreStatus = false): bool {
    return false;
  }
}
