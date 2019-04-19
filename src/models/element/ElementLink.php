<?php

namespace lenz\linkfield\models\element;

use craft\base\ElementInterface;
use lenz\linkfield\fields\LinkFieldLoader;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\Url;

/**
 * Class ElementLink
 *
 * @property ElementLinkType $_linkType
 * @method ElementLinkType getLinkType()
 */
class ElementLink extends Link
{
  /**
   * @var string|null
   */
  public $customQuery;

  /**
   * @var int
   */
  public $linkedId;

  /**
   * @var int|null
   */
  public $linkedSiteId;

  /**
   * @var string
   */
  public $linkedTitle;

  /**
   * @var ElementInterface|null
   */
  private $_element;

  /**
   * @var LinkFieldLoader
   */
  private $_loader;


  /**
   * @inheritDoc
   */
  public function getElement($ignoreStatus = false) {
    if (
      !isset($this->_element) ||
      $this->_element->getId() != $this->linkedId
    ) {
      $element = $this->queryElement($ignoreStatus);
      if ($ignoreStatus) {
        return $element;
      } else {
        $this->_element = $element;
      }
    }

    return $this->_element;
  }

  /**
   * @return string
   */
  public function getIntrinsicText() {
    $element = $this->getElement();
    return $element ? (string)$element : '';
  }

  /**
   * @return int
   */
  public function getTargetSiteId() {
    return is_null($this->linkedSiteId)
      ? $this->getOwnerSite()->id
      : $this->linkedSiteId;
  }

  /**
   * @inheritDoc
   */
  public function getUrl() {
    $element = $this->getElement();
    if (is_null($element)) {
      return null;
    }

    $url = $element->getUrl();
    $customQuery = is_string($this->customQuery)
      ? trim($this->customQuery)
      : '';

    if (
      $this->_linkType->allowCustomQuery &&
      in_array(substr($customQuery, 0, 1), ['#', '?'])
    ) {
      try {
        $baseUrl = new Url($url);
        $customQueryUrl = new Url($customQuery);

        $baseUrl->setQuery(
          $baseUrl->getQuery() +
          $customQueryUrl->getQuery()
        );

        $fragment = $customQueryUrl->getFragment();
        if (!empty($fragment)) {
          $baseUrl->setFragment($fragment);
        }

        $url = (string)$baseUrl;
      } catch (\Throwable $error) {}
    }

    return $url;
  }

  /**
   * @inheritDoc
   */
  public function hasElement($ignoreStatus = false) {
    $element = $this->getElement($ignoreStatus);
    return !is_null($element);
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(): bool {
    return !$this->hasElement();
  }

  /**
   * @param LinkFieldLoader $loader
   */
  public function setLinkFieldLoader(LinkFieldLoader $loader) {
    $this->_loader = $loader;
  }


  // Protected methods
  // -----------------

  /**
   * @param bool $ignoreStatus
   * @return ElementInterface|null
   */
  protected function queryElement($ignoreStatus = false) {
    if (!is_numeric($this->linkedId) || $this->linkedId <= 0) {
      return null;
    }

    $elementType = $this->_linkType->elementType;
    if (!$ignoreStatus && isset($this->_loader)) {
      return $this->_loader->loadElement($elementType, $this->linkedId);
    }

    $query = $elementType::find()
      ->id($this->linkedId)
      ->siteId($this->getTargetSiteId());

    if ($ignoreStatus || \Craft::$app->request->getIsCpRequest()) {
      $query->enabledForSite(false);
      $query->status(null);
    }

    return $query->one();
  }
}
