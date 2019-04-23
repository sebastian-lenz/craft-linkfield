<?php

namespace lenz\linkfield\migrations;

use craft\db\Migration;
use craft\db\Table;
use lenz\linkfield\records\LinkRecord;

/**
 * Class Install
 */
class Install extends Migration
{
  /**
   * @return bool
   */
  public function safeUp() {
    $this->createTable(
      LinkRecord::TABLE_NAME,
      [
        'id'           => $this->primaryKey(),
        'elementId'    => $this->integer()->notNull(),
        'siteId'       => $this->integer()->notNull(),
        'fieldId'      => $this->integer()->notNull(),
        'type'         => $this->string(63),
        'linkedUrl'    => $this->text(),
        'linkedId'     => $this->integer(),
        'linkedSiteId' => $this->integer(),
        'linkedTitle'  => $this->string(255),
        'payload'      => $this->text(),
        'dateCreated'  => $this->dateTime()->notNull(),
        'dateUpdated'  => $this->dateTime()->notNull(),
        'uid'          => $this->uid()->notNull(),
      ]
    );

    $this->createIndex(null,
      LinkRecord::TABLE_NAME, ['elementId', 'siteId', 'fieldId'],
      true
    );

    $this->addForeignKey(null,
      LinkRecord::TABLE_NAME, ['elementId'],
      Table::ELEMENTS, ['id'],
      'CASCADE', null
    );

    $this->addForeignKey(null,
      LinkRecord::TABLE_NAME, ['siteId'],
      Table::SITES, ['id'],
      'CASCADE', 'CASCADE'
    );

    $this->addForeignKey(null,
      LinkRecord::TABLE_NAME, ['fieldId'],
      Table::FIELDS, ['id'],
      'CASCADE', 'CASCADE'
    );

    $this->addForeignKey(null,
      LinkRecord::TABLE_NAME, ['linkedId'],
      Table::ELEMENTS, ['id'],
      'SET NULL', 'SET NULL'
    );

    $this->addForeignKey(null,
      LinkRecord::TABLE_NAME, ['linkedSiteId'],
      Table::SITES, ['id'],
      'SET NULL', 'SET NULL'
    );

    return true;
  }

  /**
   * @return bool
   */
  public function safeDown() {
    $this->dropTableIfExists(LinkRecord::TABLE_NAME);
    return true;
  }
}
