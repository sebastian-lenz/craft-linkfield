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
  public function safeUp() {
    LinkRecord::createTable($this);
    return true;
  }

  /**
   * @return bool
   */
  public function safeDown() {
    LinkRecord::dropTable($this);
    return true;
  }
}
