<?php

namespace typedlinkfield;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use typedlinkfield\events\LinkTypeEvent;
use typedlinkfield\fields\LinkField;
use typedlinkfield\models\ElementLinkType;
use typedlinkfield\models\InputLinkType;
use typedlinkfield\models\SiteLinkType;
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
    $result = [
      'url' => new InputLinkType([
        'displayName'  => 'Url',
        'displayGroup' => 'Input fields',
        'inputType'    => 'url'
      ]),
      'custom' => new InputLinkType([
        'displayName'  => 'Custom',
        'displayGroup' => 'Input fields',
        'inputType'    => 'text'
      ]),
      'email' => new InputLinkType([
        'displayName'  => 'Mail',
        'displayGroup' => 'Input fields',
        'inputType'    => 'email'
      ]),
      'tel' => new InputLinkType([
        'displayName'  => 'Telephone',
        'displayGroup' => 'Input fields',
        'inputType'    => 'tel'
      ]),
      'asset' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => \craft\elements\Asset::class,
      ]),
      'category' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => \craft\elements\Category::class
      ]),
      'entry' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => \craft\elements\Entry::class
      ]),
      'user' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => \craft\elements\User::class
      ]),
      'site' => new SiteLinkType([
        'displayGroup' => 'Craft CMS',
        'displayName'  => 'Site',
      ]),
    ];

    // Add craft commerce elements
    if (class_exists('craft\commerce\elements\Product')) {
      $result['craftCommerce-product'] = new ElementLinkType([
        'displayGroup' => 'Craft commerce',
        'elementType'  => 'craft\commerce\elements\Product'
      ]);
    }

    // Add solspace calendar elements
    if (class_exists('Solspace\Calendar\Elements\Event')) {
      $result['solspaceCalendar-event'] = new ElementLinkType([
        'displayGroup' => 'Solspace calendar',
        'elementType'  => 'Solspace\Calendar\Elements\Event'
      ]);
    }

    return $result;
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }
}
