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
  public ?int $linkedSiteId = null;


  /**
   * @inheritDoc
   */
  public function attributes(): array {
    return array_merge(parent::attributes(), [
      'linkedSiteId',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicText(): string {
    $site = $this->getSite();
    return is_null($site) ? '' : (string)$site;
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicUrl(): ?string {
    $site = $this->getSite();
    return is_null($site) ? null : $site->getBaseUrl();
  }

  /**
   * @return null|Site
   */
  public function getSite(): ?Site {
    if ($this->isEmpty()) {
      return null;
    }

    return Craft::$app
      ->getSites()
      ->getSiteById($this->linkedSiteId);
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(): bool {
    return empty($this->linkedSiteId);
  }
}
