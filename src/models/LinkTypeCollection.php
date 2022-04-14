<?php

namespace lenz\linkfield\models;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Craft;
use Exception;
use IteratorAggregate;
use lenz\linkfield\events\LinkTypeEvent;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\Plugin;
use yii\base\Event;

/**
 * Class LinkTypeCollection
 */
class LinkTypeCollection implements ArrayAccess, Countable, IteratorAggregate
{
  /**
   * @var LinkType[]
   */
  private array $_types;


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
  public function clone(): LinkTypeCollection {
    return new LinkTypeCollection($this->_types);
  }

  /**
   * @inheritDoc
   */
  public function count(): int {
    return count($this->_types);
  }

  /**
   * @return void
   */
  public function enableEmptyType(): void {
    $this->_types['empty'] = LinkType::getEmptyType();
  }

  /**
   * @param string $className
   * @return LinkTypeCollection
   */
  public function getAllByClass(string $className): LinkTypeCollection {
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
  public function getByName(...$names): LinkType|null {
    foreach ($names as $name) {
      if ($name == '*') {
        return $this->getFirstType();
      } elseif (empty($name)) {
        $name = 'empty';
      }

      if (array_key_exists($name, $this->_types)) {
        return $this->_types[$name];
      }
    }

    return null;
  }

  /**
   * @return string[]
   */
  public function getDisplayNames(): array {
    $result = array_map(function(LinkType $type) {
      return $type->getDisplayName();
    }, $this->_types);

    asort($result);
    return $result;
  }

  /**
   * @return LinkTypeCollection
   */
  public function getEnabledTypes(): LinkTypeCollection {
    return new LinkTypeCollection(array_filter(
      $this->_types,
      function(LinkType $linkType) {
        return $linkType->enabled;
      }
    ));
  }

  /**
   * @return string|null
   * @noinspection PhpUnused (API method)
   */
  public function getFirstName(): ?string {
    $type = reset($this->_types);
    return $type instanceof LinkType ? $type->name : null;
  }

  /**
   * @return LinkType|null
   */
  public function getFirstType(): LinkType|null {
    $type = reset($this->_types);
    return $type instanceof LinkType ? $type : null;
  }

  /**
   * @inheritDoc
   */
  public function getIterator(): ArrayIterator {
    return new ArrayIterator($this->_types);
  }

  /**
   * @return string[]
   */
  public function getNames(): array {
    return array_map(fn(LinkType $type) => $type->name, $this->_types);
  }

  /**
   * @return array
   */
  public function getSettings(): array {
    return array_map(fn(LinkType $linkType) => $linkType->getSettings(), $this->_types);
  }

  /**
   * @param array $value
   */
  public function setSettings(array $value): void {
    foreach ($this->_types as $linkName => $linkType) {
      if (array_key_exists($linkName, $value)) {
        $linkType->setSettings($value[$linkName]);
      }
    }
  }

  /**
   * @return $this
   */
  public function sort(): static {
    self::sortLinkTypes($this->_types);
    return $this;
  }


  // ArrayAccess
  // -----------

  /**
   * @inheritDoc
   */
  public function offsetExists(mixed $offset): bool {
    return array_key_exists($offset, $this->_types);
  }

  /**
   * @inheritDoc
   */
  public function offsetGet(mixed $offset) {
    return $this->_types[$offset];
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    throw new Exception('LinkTypeCollection is read-only.');
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function offsetUnset(mixed $offset): void {
    throw new Exception('LinkTypeCollection is read-only.');
  }


  // Static methods
  // --------------

  /**
   * @param LinkField $field
   * @return LinkTypeCollection
   */
  static public function createForField(LinkField $field): LinkTypeCollection {
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
   */
  static private function sortLinkTypes(array &$types): void {
    uasort($types, function (LinkType $a, LinkType $b) {
      $aGroup = $a->getTranslatedDisplayGroup();
      $bGroup = $b->getTranslatedDisplayGroup();

      return $aGroup === $bGroup
        ? strcmp($a->getDisplayName(), $b->getDisplayName())
        : strcmp($aGroup, $bGroup);
    });
  }
}
