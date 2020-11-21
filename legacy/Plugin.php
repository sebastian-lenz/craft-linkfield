<?php

namespace typedlinkfield;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use lenz\linkfield\fields\LinkField;
use yii\base\Event;

/**
 * Class Plugin
 * @deprecated
 */
class Plugin extends \craft\base\Plugin
{
  public function init() {
    parent::init();

    Event::on(
      Fields::class,
      Fields::EVENT_REGISTER_FIELD_TYPES,
      [$this, 'onRegisterFieldTypes']
    );
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }
}
