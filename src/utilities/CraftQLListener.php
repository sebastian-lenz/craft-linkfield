<?php

namespace typedlinkfield\utilities;

use craft\elements\Category;
use craft\elements\Asset;
use craft\elements\Entry;
use markhuot\CraftQL\Builders\Schema;
use markhuot\CraftQL\Events\GetFieldSchema;
use typedlinkfield\fields\LinkField;
use typedlinkfield\models\ElementLinkType;
use typedlinkfield\models\Link;
use typedlinkfield\models\LinkTypeInterface;

/**
 * Class CraftQLListener
 * @package typedlinkfield\utilities
 */
class CraftQLListener {
  /**
   * @var array
   */
  static $QL_TYPES = [
    Category::class  => \markhuot\CraftQL\Types\CategoryInterface::class,
    Asset::class     => \markhuot\CraftQL\Types\VolumeInterface::class,
    Entry::class     => \markhuot\CraftQL\Types\EntryInterface::class,
  ];


  /**
   * @param string $linkName
   * @param LinkTypeInterface $linkType
   * @param Schema $schema
   */
  private static function addElementTypeField($linkName, LinkTypeInterface $linkType, Schema $schema) {
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
    foreach ($field->getAllowedLinkTypes() as $linkName => $linkType) {
      self::addElementTypeField($linkName, $linkType, $object);
      $types[] = $linkName;
    }

    $link = $object->addStringField('link');
    $link->addStringArgument('text');
    $link->resolve(function($link, $args) {
        return $link instanceof Link ? (string) $link->getLink($args['text'] ?? null) : '';
    });

    // Deprecated: Will be removed in 2.0
    $object->addBooleanField('allowCustomText');
    $object->addBooleanField('allowTarget');

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
