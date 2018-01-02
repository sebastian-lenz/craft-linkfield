<?php

namespace typedlinkfield;

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use typedlinkfield\fields\LinkField;
use typedlinkfield\models\LinkTypeInterface;
use typedlinkfield\models\ElementLinkType;
use typedlinkfield\models\InputLinkType;
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
   * @return void
   */
  public function init() {
    parent::init();

    Event::on(
      Fields::class,
      Fields::EVENT_REGISTER_FIELD_TYPES,
      [$this, 'onRegisterFieldTypes']
    );
  }

  /**
   * @param string $name
   * @param LinkTypeInterface $type
   */
  public function addLinkType(string $name, LinkTypeInterface $type) {
    if (!isset($this->linkTypes)) {
      $this->resetLinkTypes();
    }

    $this->linkTypes[$name] = $type;
  }

  /**
   * @return LinkTypeInterface[]
   */
  public function getLinkTypes() {
    if (!isset($this->linkTypes)) {
      $this->resetLinkTypes();
    }

    return $this->linkTypes;
  }

  /**
   * @return void
   */
  private function resetLinkTypes() {
    $this->linkTypes = [
      'url'      => new InputLinkType('Url', [ 'inputType' => 'url' ]),
      'email'    => new InputLinkType('Mail', [ 'inputType' => 'email' ]),
      'tel'      => new InputLinkType('Telephone', [ 'inputType' => 'tel' ]),
      'asset'    => new ElementLinkType(Asset::class),
      'category' => new ElementLinkType(Category::class),
      'entry'    => new ElementLinkType(Entry::class),
    ];
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }
}
