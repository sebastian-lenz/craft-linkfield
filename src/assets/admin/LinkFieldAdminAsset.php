<?php

/** @noinspection PhpUnused */

namespace lenz\linkfield\assets\admin;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class LinkFieldAdminAsset
 */
class LinkFieldAdminAsset extends AssetBundle
{
  /**
   * @return void
   */
  public function init() {
    $this->sourcePath = __DIR__ . '/resources';
    $this->depends    = [ CpAsset::class ];
    $this->js         = [ 'LinkFieldAdmin.js' ];
    $this->css        = [ 'LinkFieldAdmin.css' ];

    parent::init();
  }
}
