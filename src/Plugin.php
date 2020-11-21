<?php

namespace lenz\linkfield;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\services\Fields;
use craft\services\Gql;
use craft\services\Plugins;
use craft\utilities\ClearCaches;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\listeners\ElementListener;
use lenz\linkfield\listeners\ElementListenerState;
use lenz\linkfield\models\LinkGqlType;
use Throwable;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @property ElementListener $elementListener
 */
class Plugin extends \craft\base\Plugin
{
  /**
   * @inheritDoc
   */
  public $schemaVersion = '2.0.0';

  /**
   * @event events\LinkTypeEvent
   */
  const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';


  /**
   * @return void
   */
  public function init() {
    parent::init();

    $this->setComponents([
      'elementListener' => ElementListener::class,
    ]);

    Event::on(
      Fields::class,
      Fields::EVENT_REGISTER_FIELD_TYPES,
      [$this, 'onRegisterFieldTypes']
    );

    Event::on(
      Plugins::class,
      Plugins::EVENT_AFTER_LOAD_PLUGINS,
      [$this, 'onAfterLoadPlugins']
    );

    Event::on(
      ClearCaches::class,
      ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
      [listeners\CacheListener::class, 'onRegisterCacheOptions']
    );

    Event::on(
      Gql::class,
      Gql::EVENT_REGISTER_GQL_TYPES,
      [$this, 'onRegisterGqlTypes']
    );
  }

  /**
   * @return void
   */
  public function onAfterLoadPlugins() {
    try {
      if (
        Craft::$app->isInstalled &&
        ElementListenerState::getInstance()->isCacheEnabled()
      ) {
        $this->elementListener->processStatusChanges();
      }
    } catch (Throwable $error) {
      Craft::error($error->getMessage());
    }
  }

  /**
   * @param RegisterComponentTypesEvent $event
   */
  public function onRegisterFieldTypes(RegisterComponentTypesEvent $event) {
    $event->types[] = LinkField::class;
  }

  /**
   * @param RegisterGqlTypesEvent $event
   */
  public function onRegisterGqlTypes(RegisterGqlTypesEvent $event) {
    $event->types[] = LinkGqlType::class;
  }
}
