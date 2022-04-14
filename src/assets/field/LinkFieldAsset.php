<?php

/** @noinspection PhpUnused */

namespace lenz\linkfield\assets\field;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class LinkFieldAsset
 */
class LinkFieldAsset extends AssetBundle
{
  /**
   * @return void
   */
  public function init() {
    $this->sourcePath = __DIR__ . '/resources';
    $this->depends    = [ CpAsset::class ];
    $this->js         = [ 'LinkField.js' ];
    $this->css        = [ 'LinkField.css' ];

    parent::init();
  }
}
