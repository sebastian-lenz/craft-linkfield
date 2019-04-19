<?php

namespace lenz\linkfield\models\site;

use Craft;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;

/**
 * Class SiteLinkType
 */
class SiteLinkType extends LinkType
{
  /**
   * @var string
   */
  public $displayName;

  /**
   * @var string|array
   */
  public $sites = '*';

  /**
   * @inheritDoc
   */
  const MODEL_CLASS = SiteLink::class;


  /**
   * @return string
   */
  public function getDisplayName(): string {
    return Craft::t('typedlinkfield', $this->displayName);
  }

  /**
   * @inheritDoc
   */
  public function getInputHtml(Link $value, bool $disabled): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_input-select',
      [
        'selected'    => $this->isSelected($value),
        'linkType'    => $this,
        'selectField' => $this->getSelectField($value, $disabled),
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function getSettingsHtml(LinkField $field): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_settings-site',
      [
        'linkType' => $this,
      ]
    );
  }

  /**
   * @param string|array|null $siteIds
   * @return array
   */
  public function getSiteOptions($siteIds = null) {
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
   * @inheritDoc
   */
  public function rules() {
    return array_merge(parent::rules(), [
      ['sites', 'validateSites']
    ]);
  }

  /**
   * @inheritDoc
   */
  public function settingsAttributes(): array {
    return array_merge(parent::settingsAttributes(), [
      'sites'
    ]);
  }

  /**
   * @return void
   */
  public function validateSites() {
    $sites = $this->sites;
    if (!is_array($sites)) {
      $sites = [$sites];
    }

    $siteIds = Craft::$app->getSites()->getAllSiteIds();
    $resolvedSites = array();
    foreach ($sites as $site) {
      if ($site == '*') {
        $this->sites = '*';
        return;
      }

      if (in_array($site, $siteIds)) {
        $resolvedSites[] = $site;
      }
    }

    $this->sites = $resolvedSites;
  }


  // Protected methods
  // -----------------

  /**
   * @param Link $value
   * @param bool $disabled
   * @return array
   */
  protected function getSelectField(Link $value, bool $disabled) {
    $site = $this->isSelected($value) && $value instanceof SiteLink
      ? $value->getSite()
      : null;

    return $this->getFieldSettings(
      $value,
      'linkedSiteId',
      [
        'disabled' => $disabled,
        'options'  => $this->getSiteOptions($this->sites),
        'value'    => is_null($site) ? null : $site->id,
      ]
    );
  }

  /**
   * @inheritDoc
   */
  protected function prepareLegacyData($data) {
    if (!is_numeric($data)) {
      return null;
    }

    return [
      'linkedSiteId' => $data
    ];
  }
}
