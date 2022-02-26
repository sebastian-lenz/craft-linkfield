<?php

use craft\base\FieldInterface;
use craft\console\Application;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\migrations\Install;
use craft\models\EntryType;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\services\Config;
use lenz\linkfield\fields\LinkField;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractLinkFieldTest
 */
abstract class AbstractLinkFieldTest extends TestCase
{
  /**
   * @var Application
   */
  protected static $craft;


  /**
   * @inheritdoc
   * @throws Throwable
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    if (isset(self::$craft)) {
      return;
    }

    self::initDatabase();
    self::initCraft();
    self::installCraft();
  }

  /**
   * @param array $options
   * @return FieldInterface
   * @throws Throwable
   */
  protected static function createLinkField($options) {
    $fieldsService = Craft::$app->getFields();
    $field = $fieldsService->createField([
      'type'    => LinkField::class,
      'groupId' => 1,
    ] + $options);

    $fieldsService->saveField($field);
    return $field;
  }

  /**
   * @param array $options
   * @return Section
   * @throws Throwable
   */
  protected static function createSection($options) {
    $siteSettings = [];
    $section = new Section($options);

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
      $siteSettings[$site->id] = new Section_SiteSettings([
        'siteId'           => $site->id,
        'enabledByDefault' => true,
        'hasUrls'          => true,
        'uriFormat'        => '{{ slug }}',
      ]);
    }

    $section->setSiteSettings($siteSettings);

    self::$craft->sections->saveSection($section);
    return $section;
  }

  /**
   * @param Section $section
   * @param FieldInterface $field
   * @return EntryType
   * @throws Throwable
   */
  protected static function createEntryType(
    Section $section,
    FieldInterface $field
  ) {
    $entryTypes = $section->getEntryTypes();
    $entryType = $entryTypes[0];

    $layout = $entryType->getFieldLayout();
    $layout->setTabs([
      new FieldLayoutTab([
        'name' => 'Common',
        'sortOrder' => 0,
        'elements' => [[
          'type' => CustomField::class,
          'fieldUid' => $field->uid,
          'required' => false,
        ]],
      ])
    ]);

    Craft::$app->getFields()->saveLayout($layout);
    Craft::$app->getSections()->saveEntryType($entryType);
    return $entryType;
  }

  /**
   * @param EntryType $entryType
   * @param array $options
   * @return Entry
   * @throws Throwable
   */
  protected static function createEntry(EntryType $entryType, array $options) {
    $entry = new Entry(array_merge([
      'typeId'    => $entryType->id,
      'sectionId' => $entryType->sectionId,
    ], $options));

    Craft::$app->elements->saveElement($entry);
    return $entry;
  }

  /**
   * @throws Throwable
   */
  private static function initDatabase() {
    $connection = new mysqli(
      getenv('TEST_DB_HOST'),
      getenv('TEST_DB_USER'),
      getenv('TEST_DB_PASS'),
      null,
      getenv('TEST_DB_PORT')
    );

    $dbName = $connection->escape_string(getenv('TEST_DB_NAME'));
    if (!$connection->query("DROP SCHEMA IF EXISTS $dbName")) {
      throw new Exception('Could not drop database: ' . $connection->error);
    }

    if (!$connection->query("CREATE SCHEMA IF NOT EXISTS $dbName")) {
      throw new Exception('Could not create database: ' . $connection->error);
    }

    $connection->close();
  }

  /**
   * @throws Throwable
   */
  private static function initCraft() {
    define('YII_ENV', 'test');
    defined('YII_DEBUG') || define('YII_DEBUG', true);
    defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', YII_ENV);
    defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
    defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

    $composerPath = realpath(__DIR__ . '/../vendor');
    $projectPath = __DIR__ . '/craft';

    $configPath = realpath($projectPath . '/config');
    $contentMigrationsPath = realpath($projectPath . '/migrations');
    $storagePath = realpath($projectPath . '/storage');
    $templatesPath = realpath($projectPath . '/templates');
    $translationsPath = realpath($projectPath . '/translations');
    $vendorPath = realpath(__DIR__ . '/../vendor');
    $webrootPath = realpath($projectPath . '/web');

    // Use the current installation of Craft
    define('CRAFT_STORAGE_PATH', $storagePath);
    define('CRAFT_TEMPLATES_PATH', $templatesPath);
    define('CRAFT_CONFIG_PATH', $configPath);
    define('CRAFT_VENDOR_PATH', $vendorPath);

    // Log errors to craft/storage/logs/phperrors.log
    ini_set('log_errors', 1);
    ini_set('error_log', $storagePath.'/logs/phperrors.log');
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Load the files
    $craftSrcPath = $composerPath . '/craftcms/cms/src';
    $craftLibPath = $composerPath . '/craftcms/cms/lib';
    require $craftLibPath . '/yii2/Yii.php';
    require $craftSrcPath . '/Craft.php';

    // Set aliases
    Craft::setAlias('@lib', $craftLibPath);
    Craft::setAlias('@craft', $craftSrcPath);
    Craft::setAlias('@config', $configPath);
    Craft::setAlias('@contentMigrations', $contentMigrationsPath);
    Craft::setAlias('@storage', $storagePath);
    Craft::setAlias('@templates', $templatesPath);
    Craft::setAlias('@translations', $translationsPath);
    Craft::setAlias('@webroot', $webrootPath);

    // Override where Yii should find its asset deps
    Craft::setAlias('@bower/bootstrap/dist', $craftLibPath . '/bootstrap');
    Craft::setAlias('@bower/jquery/dist', $craftLibPath . '/jquery');
    Craft::setAlias('@bower/inputmask/dist', $craftLibPath . '/inputmask');
    Craft::setAlias('@bower/punycode', $craftLibPath . '/punycode');
    Craft::setAlias('@bower/yii2-pjax', $craftLibPath . '/yii2-pjax');

    // Load the config
    $appType = 'console';
    $configService = new Config();
    $configService->env = YII_ENV;
    $configService->configDir = $configPath;
    $configService->appDefaultsDir = implode(
      DIRECTORY_SEPARATOR,
      [ $craftSrcPath, 'config', 'defaults' ]
    );

    $config = ArrayHelper::merge(
      [
        'vendorPath' => CRAFT_VENDOR_PATH,
        'env'        => YII_ENV,
        'components' => [
          'config' => $configService,
        ],
      ],
      require "{$craftSrcPath}/config/app.php",
      require "{$craftSrcPath}/config/app.{$appType}.php",
      $configService->getConfigFromFile('app'),
      $configService->getConfigFromFile("app.{$appType}")
    );

    if (defined('CRAFT_SITE') || defined('CRAFT_LOCALE')) {
      $config['components']['sites']['currentSite'] = defined('CRAFT_SITE') ? CRAFT_SITE : CRAFT_LOCALE;
    }

    // Initialize the application
    self::$craft = Craft::createObject($config);
  }

  /**
   * @throws Throwable
   */
  private static function installCraft() {
    $username = 'test';
    $email = 'test@test.com';
    $password = 'testtest';
    $siteName = 'Test Site';
    $siteUrl = 'http://localhost';
    $language = 'en-US';

    $site = new Site([
      'name' => $siteName,
      'handle' => 'default',
      'hasUrls' => true,
      'baseUrl' => $siteUrl,
      'language' => $language,
    ]);

    $migration = new Install([
      'username' => $username,
      'password' => $password,
      'email' => $email,
      'site' => $site,
    ]);

    // Capture all output
    ob_start();

    // Run the install migration
    $migrator = Craft::$app->getMigrator();
    $result = $migrator->migrateUp($migration);
    if ($result === false) {
      throw new Exception('Could not install Craft.');
    }

    // Mark all existing migrations as applied
    foreach ($migrator->getNewMigrations() as $name) {
      $migrator->addMigrationHistory($name);
    }

    // Stop output capture
    ob_end_clean();

    // Reset install info
    $appReflection = new ReflectionClass(Craft::$app);
    $infoProperty = $appReflection->getProperty('_info');
    $infoProperty->setAccessible(true);
    $infoProperty->setValue(Craft::$app, null);

    // Install the plugin
    self::$craft->getPlugins()->installPlugin('typedlinkfield');
  }
}
