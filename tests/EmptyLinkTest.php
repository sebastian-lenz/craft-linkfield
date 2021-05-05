<?php

use craft\base\Field;
use craft\base\FieldInterface;
use craft\models\EntryType;

/**
 * Class EmptyLinkTest
 */
class EmptyLinkTest extends AbstractLinkFieldTest
{
  /**
   * @var EntryType
   */
  private static $entryType;

  /**
   * @var FieldInterface
   */
  private static $field;


  /**
   * @throws Throwable
   */
  public function testUrlLink() {
    $entry = $this->createEntry(self::$entryType, [
      'title' => 'Empty Link',
      'emptyLinkField' => self::$field->normalizeValue([
        'type' => 'url',
        'linkedUrl' => 'https://craftcms.com',
        'ariaLabel' => '@data',
        'customText' => '@data',
        'target' => '@data',
        'title' => '@data',
      ]),
    ]);

    $loadedEntry = Craft::$app->getEntries()->getEntryById($entry->id);
    $link = $loadedEntry->emptyLinkField;
    $this->assertNotEmpty($link->getUrl());
    $this->assertNotEmpty($link->ariaLabel);
    $this->assertNotEmpty($link->customText);
    $this->assertNotEmpty($link->target);
    $this->assertNotEmpty($link->title);

    $link->linkedUrl = '';
    Craft::$app->elements->saveElement($loadedEntry);

    $loadedEntry = Craft::$app->getEntries()->getEntryById($entry->id);
    $link = $loadedEntry->emptyLinkField;
    $this->assertNull($link->getUrl());
    $this->assertNull($link->ariaLabel);
    $this->assertNull($link->customText);
    $this->assertNull($link->target);
    $this->assertNull($link->title);
  }

  /**
   * @inheritdoc
   * @throws Throwable
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    if (isset(self::$field)) {
      return;
    }

    $field = self::createLinkField([
      'name'              => 'Empty Link Field',
      'handle'            => 'emptyLinkField',
      'translationMethod' => Field::TRANSLATION_METHOD_NONE,
      'settings'          => array(
        'allowTarget'     => true,
        'enableAriaLabel' => true,
        'enableTitle'     => true,
      ),
    ]);

    $section = self::createSection([
      'name'   => 'Empty Channel',
      'handle' => 'emptyChannel',
      'type'   => 'channel',
    ]);

    self::$entryType = self::createEntryType($section, $field);
    self::$field = $field;
  }
}
