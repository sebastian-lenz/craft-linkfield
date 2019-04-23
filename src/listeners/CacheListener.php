<?php

namespace lenz\linkfield\listeners;

use craft\events\RegisterCacheOptionsEvent;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\records\LinkRecord;

/**
 * Class CacheListener
 */
class CacheListener
{
  /**
   * @param RegisterCacheOptionsEvent $event
   */
  static function onRegisterCacheOptions(RegisterCacheOptionsEvent $event) {
    $event->options[] = [
      'key'    => 'linkfieldElementCache',
      'label'  => 'Link field element cache',
      'action' => [self::class, 'onClearCache']
    ];
  }

  /**
   * @return void
   * @throws \Exception
   */
  static function onClearCache() {
    $allFields = \Craft::$app->getFields()->getAllFields(false);
    $state = ElementListenerState::getInstance();

    ElementListenerState::getInstance()->reset();

    foreach ($allFields as $field) {
      if (!($field instanceof LinkField)) {
        continue;
      }

      $conditions = $state->getFieldElementLinkConditions($field->id);
      if (is_null($conditions)) {
        continue;
      }

      CacheListenerJob::createForField($field);
    }
  }
}
