<?php

namespace lenz\linkfield\listeners;

use Craft;
use craft\events\RegisterCacheOptionsEvent;
use Exception;
use lenz\linkfield\fields\LinkField;

/**
 * Class CacheListener
 */
class CacheListener
{
  /**
   * @param RegisterCacheOptionsEvent $event
   */
  static function onRegisterCacheOptions(RegisterCacheOptionsEvent $event): void {
    $event->options[] = [
      'key'    => 'linkfieldElementCache',
      'label'  => 'Link field element cache',
      'action' => [self::class, 'onClearCache']
    ];
  }

  /**
   * @return void
   * @throws Exception
   * @noinspection PhpUnused (Used as callable)
   */
  static function onClearCache(): void {
    $allFields = Craft::$app->getFields()->getAllFields(false);
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
