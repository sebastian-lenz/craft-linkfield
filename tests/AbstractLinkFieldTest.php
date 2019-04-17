<?php

use PHPUnit\Framework\TestCase;

use typedlinkfield\fields\LinkField;

/**
 * Class AbstractLinkFieldTest
 */
abstract class AbstractLinkFieldTest extends TestCase
{
  /**
   * @var \craft\console\Application
   */
  protected static $craft;


  /**
   * @inheritdoc
   * @throws \Throwable
   */
  public static function setUpBeforeClass() {
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
   * @return \craft\base\FieldInterface
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
   * @return \craft\models\Section
   * @throws Throwable
   */
  protected static function createSection($options) {
    $siteSettings = [];
    $section = new \craft\models\Section($options);

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
      $siteSettings[$site->id] = new \craft\models\Section_SiteSettings([
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
   * @param \craft\models\Section $section
   * @param \craft\base\FieldInterface $field
   * @return \craft\models\EntryType
   * @throws Throwable
   */
  protected static function createEntryType(
    \craft\models\Section $section,
    \craft\base\FieldInterface $field
  ) {
    $entryTypes = $section->getEntryTypes();
    $entryType = $entryTypes[0];

    $fields = $entryType->getFieldLayout();
    $fields->setTabs([
      new \craft\models\FieldLayoutTab([
        'name'      => 'Common',
        'fields'    => [ $field ],
        'sortOrder' => 0,
      ])
    ]);

    Craft::$app->getSections()->saveEntryType($entryType);
    return $entryType;
  }

  /**
   * @param \craft\models\EntryType $entryType
   * @return \craft\elements\Entry
   * @throws Throwable
   * @throws \craft\errors\ElementNotFoundException
   * @throws \yii\base\Exception
   */
  protected static function createEntry(\craft\models\EntryType $entryType, $options) {
    $entry = new \craft\elements\Entry([
        'typeId'    => $entryType->id,
        'sectionId' => $entryType->sectionId,
      ] + $options);

    Craft::$app->elements->saveElement($entry);
    return $entry;
  }

  /**
   * @throws \Throwable
   */
  private static function initDatabase() {
    $connection = new mysqli('localhost', getenv('TEST_DB_USER'), getenv('TEST_DB_PASS'));
    $dbName = $connection->escape_string(getenv('TEST_DB_NAME'));

    if (!$connection->query("DROP SCHEMA IF EXISTS $dbName")) {
      throw new Exception('Could not drop database.');
    }

    if (!$connection->query("CREATE SCHEMA IF NOT EXISTS $dbName")) {
      throw new Exception('Could not create database.');
    }

    $connection->close();
  }

  /**
   * @throws \Throwable
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
    $configService = new \craft\services\Config();
    $configService->env = YII_ENV;
    $configService->configDir = $configPath;
    $configService->appDefaultsDir = implode(
      DIRECTORY_SEPARATOR,
      [ $craftSrcPath, 'config', 'defaults' ]
    );

    $config = \craft\helpers\ArrayHelper::merge(
      [
        'vendorPath' => CRAFT_VENDOR_PATH,
        'env'        => YII_ENV,
        'components' => [
          'config' => $configService,
        ],
        'modules' => [
          'typedlinkfield' => \typedlinkfield\Plugin::class,
        ],
        'bootstrap' => [
          'typedlinkfield'
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
   * @throws \Throwable
   */
  private static function installCraft() {
    $username = 'test';
    $email = 'test@test.com';
    $password = 'testtest';
    $siteName = 'Test Site';
    $siteUrl = 'http://localhost';
    $language = 'en-US';

    $site = new \craft\models\Site([
      'name' => $siteName,
      'handle' => 'default',
      'hasUrls' => true,
      'baseUrl' => $siteUrl,
      'language' => $language,
    ]);

    $migration = new \craft\migrations\Install([
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
  }
}
