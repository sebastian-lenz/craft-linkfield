<?php

namespace lenz\linkfield\listeners;

use craft\elements\Category;
use craft\elements\Asset;
use craft\elements\Entry;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\element\ElementLinkType;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;
use markhuot\CraftQL\Builders\Schema;
use markhuot\CraftQL\Events\GetFieldSchema;
use markhuot\CraftQL\Types\CategoryInterface;
use markhuot\CraftQL\Types\EntryInterface;
use markhuot\CraftQL\Types\VolumeInterface;

/**
 * Class CraftQLListener
 */
class CraftQLListener {
  /**
   * @var array
   */
  static $QL_TYPES = [
    Category::class  => CategoryInterface::class,
    Asset::class     => VolumeInterface::class,
    Entry::class     => EntryInterface::class,
  ];


  /**
   * @param string $linkName
   * @param LinkType $linkType
   * @param Schema $schema
   */
  private static function addElementTypeField($linkName, LinkType $linkType, Schema $schema) {
    if (!($linkType instanceof ElementLinkType)) {
      return;
    }

    $elementType = $linkType->elementType;
    if (!isset(self::$QL_TYPES[$elementType])) {
      return;
    }

    $schema->addField($linkName)
      ->type(self::$QL_TYPES[$elementType])
      ->resolve(function($link) use ($elementType) {
        $element = $link instanceof Link ? $link->getElement() : null;
        return $element instanceof $elementType ? $element : null;
      });
  }

  /**
   * @param GetFieldSchema $event
   */
  public static function onCraftQlGetFieldSchema(GetFieldSchema $event) {
    $field = $event->sender;
    if (!($field instanceof LinkField)) {
      return;
    }

    $object = $event->schema->createObjectType(ucfirst($field->handle) . 'LinkType');
    $types = array();
    foreach ($field->getEnabledLinkTypes() as $linkName => $linkType) {
      self::addElementTypeField($linkName, $linkType, $object);
      $types[] = $linkName;
    }

    $link = $object->addStringField('link');
    $link->addStringArgument('text');
    $link->resolve(function($link, $args) {
        return $link instanceof Link ? (string) $link->getLink($args['text'] ?? null) : '';
    });

    $object->addStringField('customText');
    $object->addStringField('defaultText');
    $object->addStringField('target');
    $object->addStringField('text');
    $object->addEnumField('type')->values($types);
    $object->addStringField('url')->resolve(function($link) {
      return $link instanceof Link ? $link->getUrl() : '';
    });

    $event->handled = true;
    $event->schema->addField($field)->type($object);
  }
}
