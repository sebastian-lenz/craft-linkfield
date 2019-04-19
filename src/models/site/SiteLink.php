<?php

namespace lenz\linkfield\models\site;

use Craft;
use craft\models\Site;
use lenz\linkfield\models\Link;

/**
 * Class SiteLink
 *
 * @property SiteLinkType $_linkType
 * @method SiteLinkType getLinkType()
 */
class SiteLink extends Link
{
  /**
   * @var int|null
   */
  public $linkedSiteId;


  /**
   * @inheritDoc
   */
  public function attributes() {
    return array_merge(parent::attributes(), [
      'linkedSiteId',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicText() {
    $site = $this->getSite();
    return is_null($site)
      ? null
      : (string)$site;
  }

  /**
   * @return null|Site
   */
  public function getSite() {
    if ($this->isEmpty()) {
      return null;
    }

    return Craft::$app->getSites()->getSiteById($this->linkedSiteId);
  }

  /**
   * @return null|string
   */
  public function getUrl() {
    $site = $this->getSite();
    return is_null($site)
      ? null
      : Craft::getAlias($site->baseUrl);
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(): bool {
    return empty($this->linkedSiteId);
  }
}
