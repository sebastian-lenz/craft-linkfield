<?php

namespace lenz\linkfield\migrations;

use craft\db\Migration;
use lenz\linkfield\records\LinkRecord;

/**
 * Class Install
 */
class Install extends Migration
{
  /**
   * @return bool
   */
  public function safeUp(): bool {
    LinkRecord::createTable($this);
    return true;
  }

  /**
   * @return bool
   */
  public function safeDown(): bool {
    LinkRecord::dropTable($this);
    return true;
  }
}
