<?php

namespace typedlinkfield\utilities;

use craft\base\ElementInterface;
use typedlinkfield\models\ElementLinkType;
use typedlinkfield\models\Link;

/**
 * Class ElementPrefetch
 */
class ElementPrefetch
{
  /**
   * @param string $handle
   * @param ElementInterface[] $sourceElements
   */
  static function prefetchElements(string $handle, array $sourceElements) {
    $elementTypes = array();

    foreach ($sourceElements as $element) {
      $value = $element->$handle;

      if (!($value instanceof Link)) {
        continue;
      }

      $type = $value->getLinkType();
      if ($type instanceof ElementLinkType) {
        $elementTypes[(string)$type->elementType][$value->value][] = $value;
      }
    }

    foreach ($elementTypes as $type => $mappings) {
      /** @noinspection PhpUndefinedMethodInspection */
      $elements = $type::find()
        ->id(array_keys($mappings))
        ->all();

      $elementsById = array();
      foreach ($elements as $element) {
        $elementsById[$element->getId()] = $element;
      }

      foreach ($mappings as $id => $links) {
        if (!isset($elementsById[$id])) continue;
        $element = $elementsById[$id];

        foreach ($links as $link) {
          $link->setPrefetchedElement($element);
        }
      }
    }
  }
}
