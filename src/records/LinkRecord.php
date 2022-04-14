<?php

namespace lenz\linkfield\records;

use craft\db\Migration;
use craft\db\Table;
use craft\records\Element;
use craft\records\Site;
use lenz\craft\utils\foreignField\ForeignFieldRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class LinkRecord
 *
 * @property string $type
 * @property string $linkedUrl
 * @property int $linkedId
 * @property int $linkedSiteId
 * @property string $linkedTitle
 * @property string $payload
 */
class LinkRecord extends ForeignFieldRecord
{
  /**
   * @return ActiveQueryInterface
   * @noinspection PhpUnused
   */
  public function getLinkedElement(): ActiveQueryInterface {
    return $this->hasOne(Element::class, ['id' => 'linkedId']);
  }

  /**
   * @return ActiveQueryInterface
   * @noinspection PhpUnused
   */
  public function getLinkedSite(): ActiveQueryInterface {
    return $this->hasOne(Site::class, ['id' => 'linkedSiteId']);
  }


  // Static methods
  // --------------

  /**
   * @inheritdoc
   */
  public static function createTable(Migration $migration, array $columns = []): void {
    $table = static::tableName();

    parent::createTable($migration, $columns + [
      'type'         => $migration->string(63),
      'linkedUrl'    => $migration->text(),
      'linkedId'     => $migration->integer(),
      'linkedSiteId' => $migration->integer(),
      'linkedTitle'  => $migration->string(255),
      'payload'      => $migration->text(),
    ]);

    $migration->addForeignKey(null, $table, ['linkedId'], Table::ELEMENTS, ['id'], 'SET NULL', 'SET NULL');
    $migration->addForeignKey(null, $table, ['linkedSiteId'], Table::SITES, ['id'], 'SET NULL', 'SET NULL');
  }

  /**
   * @inheritdoc
   */
  public static function tableName(): string {
    return '{{%lenz_linkfield}}';
  }
}
