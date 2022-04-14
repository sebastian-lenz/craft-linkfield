<?php

namespace lenz\linkfield\fields;

use craft\elements\db\ElementQuery;
use craft\events\PopulateElementEvent;
use Exception;
use lenz\craft\utils\foreignField\ForeignFieldQueryExtension;
use lenz\linkfield\models\element\ElementLink;
use lenz\linkfield\models\element\ElementLinkBatchLoader;

/**
 * Class LinkFieldQueryExtension
 */
class LinkFieldQueryExtension extends ForeignFieldQueryExtension
{
  /**
   * @var ElementLinkBatchLoader|null
   */
  static private ?ElementLinkBatchLoader $_batchLoader = null;


  /**
   * @param PopulateElementEvent $event
   * @throws Exception
   */
  public function onAfterPopulateElement(PopulateElementEvent $event): void {
    try {
      $link = $event->element->getFieldValue($this->field->handle);
    } catch (Exception) {
      $link = null;
    }

    if (
      $link instanceof ElementLink &&
      !$link->isCrossSiteLink()
    ) {
      if (
        is_null(self::$_batchLoader) ||
        self::$_batchLoader->isInUse()
      ) {
        self::$_batchLoader = new ElementLinkBatchLoader();
      }

      self::$_batchLoader->addLink($link);
      $link->setBatchLoader(self::$_batchLoader);
    }
  }


  // Protected methods
  // -----------------

  /**
   * @return void
   */
  protected function attachEagerLoad(): void {
    parent::attachEagerLoad();

    $this->query->on(
      ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
      [$this, 'onAfterPopulateElement']
    );
  }
}
