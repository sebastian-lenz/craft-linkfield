<?php

use \typedlinkfield\models\Link;

/**
 * Class BasicTest
 */
class BasicTest extends AbstractLinkFieldTest
{
  /**
   * @var \craft\models\EntryType
   */
  private static $entryType;

  /**
   * @var \craft\base\FieldInterface
   */
  private static $field;

  /**
   * Test data
   */
  const TEST_ARIA_LABEL = 'My "Aria Label"';
  const TEST_CAPTION = 'My "<strong>Link</strong> Caption"';
  const TEST_DEFAULT_TEXT = 'My "<strong>Default</strong> Caption"';
  const TEST_TITLE = 'My "Link Title"';
  const TEST_URL = 'http://www.google.de?value="Test"';


  /**
   * @throws Throwable
   */
  public function testUrlLink() {
    $link = $this->createAndFetchEntry([
      'title'          => 'URL Link',
      'basicLinkField' => self::$field->normalizeValue([
        'type'  => 'url',
        'value' => self::TEST_URL,
      ]),
    ]);

    $this->assertEquals($link->getUrl(), self::TEST_URL);
    $this->assertEquals($link->getText(), self::TEST_DEFAULT_TEXT);
    $this->assertNull($link->getAriaLabel());
    $this->assertNull($link->getElement());
    $this->assertNull($link->getTitle());

    // Simple link output
    $this->assertEquals(
      '<a href="http://www.google.de?value=&quot;Test&quot;">My "<strong>Default</strong> Caption"</a>',
      (string)$link->getLink()
    );

    $this->assertEquals(
      '<a href="http://www.google.de?value=&quot;Test&quot;">Caption</a>',
      (string)$link->getLink('Caption')
    );

    // Complex link output
    $this->assertEquals(
      '<a href="http://www.google.de?value=&quot;Test&quot;" target="_blank">Caption</a>',
      (string)$link->getLink([
        'target' => '_blank',
        'text' => 'Caption',
      ])
    );

    // Just check whether methods work
    $html = self::$field->getInputHtml($link, $link->getOwner());
    $this->assertIsString($html);

    $html = self::$field->getSettingsHtml();
    $this->assertIsString($html);
  }

  /**
   * @throws Throwable
   */
  public function testEntryLink() {
    $otherEntry = $this->createEntry(self::$entryType, [
      'title'          => 'Linked Entry',
      'basicLinkField' => [
        'type'  => 'url',
        'value' => '',
      ]
    ]);

    $link = $this->createAndFetchEntry([
      'title'          => 'Entry Link',
      'basicLinkField' => self::$field->normalizeValue([
        'type'  => 'entry',
        'value' => $otherEntry->id,
      ]),
    ]);

    $this->assertEquals($link->getUrl(), 'http://localhost/phpunit?p=linked-entry');
    $this->assertEquals($link->getText(), 'Linked Entry');
    $this->assertNull($link->getAriaLabel());
    $this->assertNull($link->getTitle());

    $loadedOtherEntry = $link->getElement();
    $this->assertInstanceOf(\craft\elements\Entry::class, $loadedOtherEntry);
    $this->assertEquals($otherEntry->id, $loadedOtherEntry->id);

    $this->assertEquals(
      '<a href="http://localhost/phpunit?p=linked-entry">Linked Entry</a>',
      (string)$link->getLink()
    );

    // Just check whether methods work
    $html = self::$field->getInputHtml($link, $link->getOwner());
    $this->assertIsString($html);

    $html = self::$field->getSettingsHtml();
    $this->assertIsString($html);
  }

  /**
   * @throws Throwable
   */
  public function testAriaLabel() {
    $link = $this->createAndFetchEntry([
      'title'          => 'Aria/Title Link',
      'basicLinkField' => self::$field->normalizeValue([
        'ariaLabel'   => self::TEST_ARIA_LABEL,
        'customText'  => self::TEST_CAPTION,
        'title'       => self::TEST_TITLE,
        'type'        => 'url',
        'value'       => 'http://www.google.de',
      ]),
    ]);

    $this->assertEquals($link->getAriaLabel(), self::TEST_ARIA_LABEL);
    $this->assertEquals($link->getText(), self::TEST_CAPTION);
    $this->assertEquals($link->getTitle(), self::TEST_TITLE);

    $this->assertEquals(
      '<a href="http://www.google.de" title="My &quot;Link Title&quot;" arial-label="My &quot;Aria Label&quot;">My "<strong>Link</strong> Caption"</a>',
      (string)$link->getLink()
    );
  }

  /**
   * @param array $options
   * @return Link
   * @throws Throwable
   */
  private function createAndFetchEntry($options) {
    $entry = $this->createEntry(self::$entryType, $options);

    $loadedEntry = Craft::$app->getEntries()->getEntryById($entry->id);
    $this->assertNotEquals($entry, $loadedEntry);
    $this->assertInstanceOf(\craft\elements\Entry::class, $loadedEntry);

    /** @var Link $loadedLink */
    $loadedLink = $loadedEntry->basicLinkField;
    $this->assertInstanceOf(Link::class, $loadedLink);

    return $loadedLink;
  }

  /**
   * @inheritdoc
   * @throws Throwable
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    if (isset(self::$field)) {
      return;
    }

    $field = self::createLinkField([
      'name'              => 'Basic Link Field',
      'handle'            => 'basicLinkField',
      'translationMethod' => \craft\base\Field::TRANSLATION_METHOD_NONE,
      'settings'          => array(
        'allowTarget'     => true,
        'defaultText'     => self::TEST_DEFAULT_TEXT,
        'enableAriaLabel' => true,
        'enableTitle'     => true,
      ),
    ]);

    $section = self::createSection([
      'name'   => 'Basic Channel',
      'handle' => 'basicChannel',
      'type'   => 'channel',
    ]);

    self::$entryType = self::createEntryType($section, $field);
    self::$field = $field;
  }
}
