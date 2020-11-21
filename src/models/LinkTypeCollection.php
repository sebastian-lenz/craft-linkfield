<?php

namespace lenz\linkfield\models;

use ArrayIterator;
use Countable;
use Craft;
use lenz\linkfield\events\LinkTypeEvent;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\Plugin;
use yii\base\Event;

/**
 * Class LinkTypeCollection
 */
class LinkTypeCollection implements Countable, \IteratorAggregate
{
  /**
   * @var LinkType[]
   */
  private $_types;


  /**
   * LinkTypeCollection constructor.
   *
   * @param LinkType[] $types
   */
  public function __construct(array $types) {
    $this->_types = $types;
  }

  /**
   * @return LinkTypeCollection
   */
  public function clone() {
    return new LinkTypeCollection($this->_types);
  }

  /**
   * @inheritDoc
   */
  public function count() {
    return count($this->_types);
  }

  /**
   * @return void
   */
  public function enableEmptyType() {
    $this->_types['empty'] = LinkType::getEmptyType();
  }

  /**
   * @param string $className
   * @return LinkTypeCollection
   */
  public function getAllByClass(string $className) {
    return new LinkTypeCollection(
      array_filter($this->_types, function(LinkType $type) use ($className) {
        return is_a($type, $className);
      })
    );
  }

  /**
   * @param string ...$names
   * @return LinkType|null
   */
  public function getByName(...$names) {
    foreach ($names as $name) {
      if ($name == '*') {
        return $this->getFirstType();
      }

      if (!empty($name) && array_key_exists($name, $this->_types)) {
        return $this->_types[$name];
      }
    }

    return null;
  }

  /**
   * @return string[]
   */
  public function getDisplayNames() {
    $result = array_map(function(LinkType $type) {
      return $type->getDisplayName();
    }, $this->_types);

    asort($result);
    return $result;
  }

  /**
   * @return LinkTypeCollection
   */
  public function getEnabledTypes() {
    return new LinkTypeCollection(array_filter(
      $this->_types,
      function(LinkType $linkType) {
        return $linkType->enabled;
      }
    ));
  }

  /**
   * @return string|null
   */
  public function getFirstName() {
    $type = reset($this->_types);
    return $type ? $type->name : null;
  }

  /**
   * @return LinkType|false
   */
  public function getFirstType() {
    return reset($this->_types);
  }

  /**
   * @inheritDoc
   */
  public function getIterator() {
    return new ArrayIterator($this->_types);
  }

  /**
   * @return string[]
   */
  public function getNames() {
    return array_map(function(LinkType $type) {
      return $type->name;
    }, $this->_types);
  }

  /**
   * @return array
   */
  public function getSettings() {
    return array_map(function(LinkType $linkType) {
      return $linkType->getSettings();
    }, $this->_types);
  }

  /**
   * @param array $value
   */
  public function setSettings(array $value) {
    foreach ($this->_types as $linkName => $linkType) {
      if (array_key_exists($linkName, $value)) {
        $linkType->setSettings($value[$linkName]);
      }
    }
  }

  /**
   * @return $this
   */
  public function sort() {
    self::sortLinkTypes($this->_types);
    return $this;
  }


  // Static methods
  // --------------

  /**
   * @param LinkField $field
   * @return LinkTypeCollection
   */
  static public function createForField(LinkField $field) {
    $event = new LinkTypeEvent($field);
    $plugin = Plugin::getInstance();

    if (is_null($plugin)) {
      Craft::warning('Link field `getLinkTypes` called before the plugin has been loaded.');
      Event::trigger(self::class, Plugin::EVENT_REGISTER_LINK_TYPES, $event);
    } else {
      $plugin->trigger(Plugin::EVENT_REGISTER_LINK_TYPES, $event);
    }

    $types = $event->linkTypes;
    foreach ($types as $name => $type) {
      $type->name = $name;
    }

    return new LinkTypeCollection($types);
  }


  // Static private methods
  // ----------------------

  /**
   * @param LinkType[] $types
   * @return boolean
   */
  static private function sortLinkTypes(array &$types) {
    return uasort($types, function(LinkType $a, LinkType $b) {
      $aGroup = $a->getTranslatedDisplayGroup();
      $bGroup = $b->getTranslatedDisplayGroup();

      return $aGroup === $bGroup
        ? strcmp($a->getDisplayName(), $b->getDisplayName())
        : strcmp($aGroup, $bGroup);
    });
  }
}
