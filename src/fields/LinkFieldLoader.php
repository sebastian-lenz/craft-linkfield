<?php

namespace lenz\linkfield\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\PopulateElementEvent;
use lenz\linkfield\models\element\ElementLink;
use lenz\linkfield\records\LinkRecord;

/**
 * Class LinkFieldLoader
 */
class LinkFieldLoader
{
  /**
   * @var ElementInterface[][]
   */
  private $_elements = [];

  /**
   * @var int[][]
   */
  private $_elementIds = [];

  /**
   * @var string[]
   */
  private $_handles = [];

  /**
   * @var bool
   */
  private $_isUsed = false;

  /**
   * @var ElementQuery
   */
  private $_query;

  /**
   * @var bool|null
   */
  public static $forceEagerLoad = null;

  /**
   * @var bool|null
   */
  public static $forceInlineQuery = null;

  /**
   * @var LinkFieldLoader[]
   */
  private static $_loaders = [];

  /**
   * @var int
   */
  private static $_queryTableIndex = 0;


  /**
   * LinkFieldLoader constructor.
   * @param ElementQuery $query
   */
  public function __construct(ElementQuery $query) {
    self::$_loaders[] = $this;

    $this->_query = $query;
    $query->on(
      ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
      [$this, 'onPopulateElement']
    );
  }

  /**
   * @param LinkField $field
   */
  public function addField(LinkField $field) {
    if (!in_array($field->handle, $this->_handles)) {
      $this->_handles[] = $field->handle;
    }
  }

  /**
   * @param string $type
   * @param int $id
   * @return ElementInterface|null
   */
  public function loadElement(string $type, $id) {
    $this->_isUsed = true;
    if (!isset($this->_elements[$type])) {
      $this->_elements[$type] = $this->loadElements($type);
    }

    return array_key_exists($id, $this->_elements[$type])
      ? $this->_elements[$type][$id]
      : null;
  }

  /**
   * @param string|ElementInterface $type
   * @return ElementInterface[]
   */
  private function loadElements(string $type) {
    if (!array_key_exists($type, $this->_elementIds)) {
      return [];
    }

    $query = $type::find()->id($this->_elementIds[$type]);
    if (\Craft::$app->request->getIsCpRequest()) {
      $query->enabledForSite(false);
      $query->status(null);
    }

    $elements = [];
    foreach ($query->all() as $element) {
      $elements[$element->getId()] = $element;
    }

    return $elements;
  }

  /**
   * @param PopulateElementEvent $event
   */
  public function onPopulateElement(PopulateElementEvent $event) {
    if ($this->_isUsed) {
      return;
    }

    foreach ($this->_handles as $handle) {
      try {
        $link = $event->element->getFieldValue($handle);
      } catch (\Throwable $error) {
        continue;
      }

      if (!($link instanceof ElementLink)) {
        continue;
      }

      if ($link->isCrossSiteLink()) {
        continue;
      }

      $type = $link->getLinkType()->elementType;
      if (!isset($this->_elementIds[$type])) {
        $this->_elementIds[$type] = [];
      }

      $id = $link->linkedId;
      if (!in_array($id, $this->_elementIds[$type])) {
        $this->_elementIds[$type][] = $id;
      }

      $link->setLinkFieldLoader($this);
    }
  }

  /**
   * @param LinkField $field
   * @param ElementQueryInterface $query
   * @param mixed $value
   */
  public static function attachTo(LinkField $field, ElementQueryInterface $query, $value) {
    if (!($query instanceof ElementQuery)) {
      return;
    }

    $eagerLoad = self::enableEagerLoad($field, $query);
    if ($eagerLoad) {
      self::createLoader($query)->addField($field);
    }

    if ($eagerLoad || self::enableInlineQuery($field, $query)) {
      self::attachInlineQuery($field, $query);
    }
  }

  /**
   * @param LinkField $field
   * @param ElementQuery $query
   */
  private static function attachInlineQuery(LinkField $field, ElementQuery $query) {
    $tableName = 'linkfield_' . (self::$_queryTableIndex++);
    $fields = implode(',', [
      '"type"',         "[[{$tableName}.type]]",
      '"linkedUrl"',    "[[{$tableName}.linkedUrl]]",
      '"linkedId"',     "[[{$tableName}.linkedId]]",
      '"linkedSiteId"', "[[{$tableName}.linkedSiteId]]",
      '"linkedTitle"',  "[[{$tableName}.linkedTitle]]",
      '"payload"',      "[[{$tableName}.payload]]"
    ]);

    $jsonFunction = Craft::$app->getDb()->getIsMysql()
      ? 'json_object'
      : 'json_build_object';

    $query->query->leftJoin(
      LinkRecord::TABLE_NAME . ' ' . $tableName,
      implode(' AND ', [
        "[[{$tableName}.elementId]] = [[elements.id]]",
        "[[{$tableName}.siteId]] = [[elements_sites.siteId]]",
      ])
    );

    $query->addSelect([
      "field:{$field->handle}" => "{$jsonFunction}({$fields})"
    ]);
  }

  /**
   * @param ElementQuery $query
   * @return LinkFieldLoader
   */
  private static function createLoader(ElementQuery $query) {
    foreach (self::$_loaders as $loader) {
      if (!$loader->_isUsed) {
        return $loader;
      }
    }

    return new LinkFieldLoader($query);
  }

  /**
   * @param LinkField $field
   * @param ElementQuery $query
   * @return bool
   */
  private static function enableEagerLoad(LinkField $field, ElementQuery $query) {
    $handle = $field->handle;
    if ($query->with == $handle) {
      $query->with = null;
      return true;
    }

    if (is_array($query->with) && in_array($handle, $query->with)) {
      $query->with = array_filter(
        $query->with,
        function($with) use ($handle) {
          return $with == $handle;
        }
      );

      return true;
    }

    return is_bool(self::$forceEagerLoad)
      ? self::$forceEagerLoad
      : false;
  }

  /**
   * @param LinkField $field
   * @param ElementQuery $query
   * @return bool
   */
  private static function enableInlineQuery(LinkField $field, ElementQuery $query) {
    if (is_bool(self::$forceInlineQuery)) {
      return self::$forceInlineQuery;
    }

    return false;
  }
}
