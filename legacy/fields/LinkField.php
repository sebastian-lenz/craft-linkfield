<?php

namespace typedlinkfield\fields;

use Craft;

Craft::$app->getDeprecator()->log(
  'linkfield-legacy-field',
  'Using of the legacy link field class is deprecated. The project configuration might need a rebuild, see https://github.com/sebastian-lenz/craft-linkfield/issues/122'
);

/**
 * Class LinkField
 * @deprecated
 */
class LinkField extends \lenz\linkfield\fields\LinkField
{
  /**
   * @var string|array
   * @deprecated
   */
  public $allowedLinkNames = '*';
}
