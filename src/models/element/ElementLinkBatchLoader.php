<?php

namespace lenz\linkfield\models\element;

use Craft;
use craft\base\ElementInterface;
use Exception;

/**
 * Class ElementLinkBatchLoader
 */
class ElementLinkBatchLoader
{
  /**
   * @var ElementInterface[][]|null
   */
  private $_elements = null;

  /**
   * @var int[][]
   */
  private $_elementIds = [];


  /**
   * @param ElementLink $link
   * @throws Exception
   */
  public function addLink(ElementLink $link) {
    if ($this->isInUse()) {
      throw new Exception('This batch loader is already in use.');
    }

    $type = $link->getLinkType()->elementType;
    if (!isset($this->_elementIds[$type])) {
      $this->_elementIds[$type] = [];
    }

    $id = $link->linkedId;
    if (!in_array($id, $this->_elementIds[$type])) {
      $this->_elementIds[$type][] = $id;
    }
  }

  /**
   * @return bool
   */
  public function isInUse() {
    return !is_null($this->_elements);
  }

  /**
   * @param string $type
   * @param string|int $id
   * @return ElementInterface|null
   */
  public function loadElement(string $type, $id) {
    if (!isset($this->_elements)) {
      $this->_elements = [];
    }

    if (!isset($this->_elements[$type])) {
      $this->_elements[$type] = $this->loadElements($type);
    }

    return array_key_exists($id, $this->_elements[$type])
      ? $this->_elements[$type][$id]
      : null;
  }


  // Private methods
  // ---------------

  /**
   * @param string|ElementInterface $type
   * @return ElementInterface[]
   */
  private function loadElements(string $type) {
    if (!array_key_exists($type, $this->_elementIds)) {
      return [];
    }

    $query = $type::find()->id($this->_elementIds[$type]);
    if (Craft::$app->request->getIsCpRequest()) {
      $query->anyStatus();
    }

    $elements = [];
    foreach ($query->all() as $element) {
      $elements[$element->getId()] = $element;
    }

    return $elements;
  }
}
