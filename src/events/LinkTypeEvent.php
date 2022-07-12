<?php

namespace lenz\linkfield\events;

use Craft;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\asset\AssetLinkType;
use lenz\linkfield\models\element\ElementLinkType;
use lenz\linkfield\models\input\InputLinkType;
use lenz\linkfield\models\LinkType;
use lenz\linkfield\models\site\SiteLinkType;
use yii\base\Event;

/**
 * LinkTypeEvent class.
 */
class LinkTypeEvent extends Event
{
  /**
   * @var LinkField
   */
  public LinkField $field;

  /**
   * @var LinkType[]
   */
  public array $linkTypes;


  /**
   * LinkTypeEvent constructor.
   * @param LinkField $field
   */
  public function __construct(LinkField $field) {
    parent::__construct();

    $this->field = $field;

    $linkTypes = [
      'url' => new InputLinkType([
        'displayName'  => 'URL',
        'displayGroup' => 'Input fields',
        'inputType'    => 'url'
      ]),
      'custom' => new InputLinkType([
        'displayName'  => 'Custom',
        'displayGroup' => 'Input fields',
        'inputType'    => 'text'
      ]),
      'email' => new InputLinkType([
        'displayName'  => 'Mail',
        'displayGroup' => 'Input fields',
        'inputType'    => 'email'
      ]),
      'tel' => new InputLinkType([
        'displayName'  => 'Telephone',
        'displayGroup' => 'Input fields',
        'inputType'    => 'tel'
      ]),
      'asset' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => Asset::class
      ]),
      'category' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => Category::class
      ]),
      'entry' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => Entry::class
      ]),
      'user' => new ElementLinkType([
        'displayGroup' => 'Craft CMS',
        'elementType'  => User::class
      ]),
      'site' => new SiteLinkType([
        'displayGroup' => 'Craft CMS',
        'displayName'  => 'Site',
      ]),
    ];

    // Add craft commerce elements
    if (
      Craft::$app->getPlugins()->isPluginEnabled('commerce') &&
      class_exists('craft\commerce\elements\Product')
    ) {
      $linkTypes['craftCommerce-product'] = new ElementLinkType([
        'displayGroup' => 'Craft commerce',
        'elementType'  => 'craft\commerce\elements\Product'
      ]);
    }

    // Add solspace calendar elements
    if (
      Craft::$app->getPlugins()->isPluginEnabled('calendar') &&
      class_exists('Solspace\Calendar\Elements\Event')
    ) {
      $linkTypes['solspaceCalendar-event'] = new ElementLinkType([
        'displayGroup' => 'Solspace calendar',
        'elementType'  => 'Solspace\Calendar\Elements\Event'
      ]);
    }

    $this->linkTypes = $linkTypes;
  }
}
