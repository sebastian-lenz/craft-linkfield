<?php

namespace lenz\linkfield;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use lenz\linkfield\events\LinkTypeEvent;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\LinkType;
use yii\base\Event;

/**
 * Class Plugin
 */
class Plugin extends \craft\base\Plugin
{
  /**
   * @inheritDoc
   */
  public $schemaVersion = '2.0.0';

  /**
   * @event events\LinkTypeEvent
   */
  const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';


  /**
   * @return void
   */
  public function init() {
    parent::init();

    Event::on(
      Fields::class,
      Fields::EVENT_REGISTER_FIELD_TYPES,
      [$this, 'onRegisterFieldTypes']
    );

    Event::on(
      LinkField::class,
      'craftQlGetFieldSchema',
      [listeners\CraftQLListener::class, 'onCraftQlGetFieldSchema']
    );
  }

  /**
   * @param LinkField $field
   * @return LinkType[]
   */
  public function getLinkTypes(LinkField $field) {
    $event = new LinkTypeEvent($field);
    $this->trigger(self::EVENT_REGISTER_LINK_TYPES, $event);
    return $event->linkTypes;
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }
}
