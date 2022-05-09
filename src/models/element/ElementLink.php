<?php

namespace lenz\linkfield\models\element;

use Craft;
use craft\base\ElementInterface;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\Url;
use Throwable;

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
  public ?string $customQuery = null;

  /**
   * @var int|null
   */
  public ?int $linkedId = null;

  /**
   * @var int|null
   */
  public ?int $linkedSiteId = null;

  /**
   * @var string|null
   */
  public ?string $linkedTitle = null;

  /**
   * @var string|null
   */
  public ?string $linkedUrl = null;

  /**
   * @var ElementLinkBatchLoader
   */
  private ElementLinkBatchLoader $_batchLoader;

  /**
   * @var ElementInterface|null
   */
  private ?ElementInterface $_element;


  /**
   * @inheritDoc
   */
  public function attributes(): array {
    return array_merge(parent::attributes(), [
      'customQuery',
      'linkedId',
      'linkedSiteId',
      'linkedTitle',
      'linkedUrl',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getElement(bool $ignoreStatus = false): ?ElementInterface {
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
  public function getIntrinsicText(): string {
    if ($this->_field->enableElementCache) {
      return $this->linkedTitle ?? '';
    }

    $element = $this->getElement();
    return $element ? (string)$element : '';
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicUrl(): ?string {
    $url = $this->getElementUrl();
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
      } catch (Throwable) {
        // Ignore
      }
    }

    return $url;
  }

  /**
   * @return int|null
   */
  public function getSiteId(): ?int {
    try {
      return !$this->_linkType->allowCrossSiteLink || is_null($this->linkedSiteId)
        ? $this->getOwnerSite()->id
        : $this->linkedSiteId;
    } catch (Throwable) {
      return null;
    }
  }

  /**
   * @inheritDoc
   */
  public function hasElement(bool $ignoreStatus = false): bool {
    $element = $this->getElement($ignoreStatus);
    return !is_null($element);
  }

  /**
   * @return bool
   */
  public function isCrossSiteLink(): bool {
    try {
      return $this->getSiteId() !== $this->getOwnerSite()->id;
    } catch (Throwable) {
      return false;
    }
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(): bool {
    return empty($this->getElementUrl());
  }

  /**
   * @inheritDoc
   */
  public function isEditorEmpty(): bool {
    return !$this->hasElement(true);
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    return array_merge(parent::rules(), [
      [['customQuery', 'linkedTitle', 'linkedUrl'], 'string'],
      [['linkedId', 'linkedSiteId'], 'integer']
    ]);
  }

  /**
   * @param ElementLinkBatchLoader $batchLoader
   */
  public function setBatchLoader(ElementLinkBatchLoader $batchLoader) {
    $this->_batchLoader = $batchLoader;
  }


  // Protected methods
  // -----------------

  /**
   * @return string|null
   */
  protected function getElementUrl(): ?string {
    if ($this->_field->enableElementCache) {
      return $this->linkedUrl;
    }

    $element = $this->getElement();
    if (is_null($element)) {
      return null;
    }

    return $element->getUrl();
  }

  /**
   * @param bool $ignoreStatus
   * @return ElementInterface|null
   */
  protected function queryElement(bool $ignoreStatus = false): ?ElementInterface {
    if (!is_numeric($this->linkedId) || $this->linkedId <= 0) {
      return null;
    }

    $elementType = $this->_linkType->elementType;
    if (
      !$ignoreStatus &&
      !$this->isCrossSiteLink() &&
      isset($this->_batchLoader)
    ) {
      return $this->_batchLoader->loadElement($elementType, $this->linkedId);
    }

    $query = $elementType::find()
      ->id($this->linkedId)
      ->siteId($this->getSiteId());

    if ($ignoreStatus || Craft::$app->request->getIsCpRequest()) {
      $query->status(null);
    }

    return $query->one();
  }
}
