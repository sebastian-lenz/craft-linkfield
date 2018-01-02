<?php

namespace typedlinkfield\utilities;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class CpAssetBundle
 * @package typedlinkfield\utilities
 */
class CpAssetBundle extends AssetBundle
{
  /**
   * @return void
   */
  public function init() {
    $this->sourcePath = dirname(__DIR__) . '/resources';
    $this->depends    = [ CpAsset::class ];
    $this->js         = [ 'LinkField.js' ];
    $this->css        = [ 'LinkField.css' ];

    parent::init();
  }
}
