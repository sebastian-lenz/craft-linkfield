<?php

namespace lenz\linkfield\listeners;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use Exception;
use lenz\linkfield\records\LinkRecord;
use yii\base\Event;

/**
 * Class ElementListener
 */
class ElementListener
{
  /**
   * ElementListener constructor.
   */
  public function __construct() {
    Event::on(Element::class, Element::EVENT_AFTER_DELETE, [$this, 'onElementChanged']);
    Event::on(Element::class, Element::EVENT_AFTER_SAVE, [$this, 'onElementChanged']);
  }

  /**
   * @param Event $event
   * @throws Exception
   */
  public function onElementChanged(Event $event): void {
    $element = $event->sender;
    if ($element instanceof ElementInterface) {
      ElementListenerState::getInstance()->updateChangeDate();
      self::updateElement($element);
    }
  }

  /**
   * @throws Exception
   */
  public function processStatusChanges(): void {
    $state = ElementListenerState::getInstance();
    if (
      is_null($state->nextEntryChangeDate) ||
      $state->nextEntryChangeDate > time()
    ) {
      return;
    }

    $state->flush();
  }


  // Static methods
  // --------------

  /**
   * @param ElementInterface $element
   * @return array
   * @throws Exception
   */
  static function getElementConditions(ElementInterface $element): array {
    return [
      'and',
      ElementListenerState::getInstance()->getCachedElementLinkConditions(),
      [
        'linkedId' => $element->id,
      ],
      [
        'or',
        [
          'linkedSiteId' => $element->siteId,
        ],
        [
          'linkedSiteId' => null,
          'siteId'       => $element->siteId
        ]
      ]
    ];
  }

  /**
   * @param ElementInterface $element
   * @return bool
   */
  static function isElementPublished(ElementInterface $element): bool {
    return in_array(
      $element->getStatus(),
      [Element::STATUS_ENABLED, Entry::STATUS_LIVE]
    );
  }

  /**
   * @param ElementInterface $element
   * @throws Exception
   */
  static function updateElement(ElementInterface $element): void {
    $conditions = self::getElementConditions($element);

    if (self::isElementPublished($element)) {
      $attributes = [
        'linkedTitle' => (string)$element,
        'linkedUrl'   => $element->getUrl(),
      ];
    } else {
      $attributes = [
        'linkedTitle' => null,
        'linkedUrl'   => null,
      ];
    }

    LinkRecord::updateAll($attributes, $conditions);
  }
}
