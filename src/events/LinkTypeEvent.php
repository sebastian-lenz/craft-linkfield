<?php

namespace typedlinkfield\events;

use typedlinkfield\models\LinkTypeInterface;
use yii\base\Event;

/**
 * LinkTypeEvent class.
 */
class LinkTypeEvent extends Event
{
  /**
   * @var LinkTypeInterface[]
   */
  public $linkTypes;
}
