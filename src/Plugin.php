<?php

namespace typedlinkfield;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use typedlinkfield\events\LinkTypeEvent;
use typedlinkfield\fields\LinkField;
use typedlinkfield\models\ElementLinkType;
use typedlinkfield\models\InputLinkType;
use typedlinkfield\models\LinkTypeInterface;
use yii\base\Event;

/**
 * Class Plugin
 * @package typedlinkfield
 */
class Plugin extends \craft\base\Plugin
{
  /**
   * @var LinkTypeInterface[]
   */
  private $linkTypes;

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
      [utilities\CraftQLListener::class, 'onCraftQlGetFieldSchema']
    );
  }

  /**
   * @param string $name
   * @param LinkTypeInterface $type
   */
  public function addLinkType(string $name, LinkTypeInterface $type) {
    \Craft::$app->getDeprecator()->log(
      'typedlinkfield\\Plugin::addLinkType()',
      'typedlinkfield\\Plugin::addLinkType() is deprecated and will be removed. Use the event Plugin::EVENT_REGISTER_LINK_TYPES to add new link types.'
    );

    $this->getLinkTypes();
    $this->linkTypes[$name] = $type;
  }

  /**
   * @return LinkTypeInterface[]
   */
  public function getLinkTypes() {
    if (!isset($this->linkTypes)) {
      $event = new LinkTypeEvent();
      $event->linkTypes = $this->createDefaultLinkTypes();
      $this->trigger(self::EVENT_REGISTER_LINK_TYPES, $event);

      $this->linkTypes = $event->linkTypes;
    }

    return $this->linkTypes;
  }

  /**
   * @return LinkTypeInterface[]
   */
  private function createDefaultLinkTypes() {
    return [
      'url'       => new InputLinkType('Url', [ 'inputType' => 'url' ]),
      'email'     => new InputLinkType('Mail', [ 'inputType' => 'email' ]),
      'tel'       => new InputLinkType('Telephone', [ 'inputType' => 'tel' ]),
      'asset'     => new ElementLinkType(\craft\elements\Asset::class),
      'category'  => new ElementLinkType(\craft\elements\Category::class),
      'entry'     => new ElementLinkType(\craft\elements\Entry::class),
      'globalset' => new ElementLinkType(\craft\elements\GlobalSet::class),
    ];
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }
}
