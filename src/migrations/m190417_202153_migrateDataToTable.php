<?php

namespace lenz\linkfield\migrations;

use Craft;
use craft\base\FieldInterface;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Matrix;
use craft\helpers\Json;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\records\LinkRecord;
use verbb\supertable\fields\SuperTableField;

/**
 * m190417_202153_migrateDataToTable migration.
 * @noinspection PhpUnused
 */
class m190417_202153_migrateDataToTable extends Migration
{
  /**
   * @inheritdoc
   */
  public function safeUp() {
    if (!$this->db->tableExists(LinkRecord::tableName())) {
      (new Install())->safeUp();
    }

    $this->updateAllSettings();
    $this->updateAllFields();
  }

  /**
   * @inheritdoc
   */
  public function safeDown() {
    echo "m190417_202153_migrateDataToTable cannot be reverted.\n";
    return false;
  }


  // Private methods
  // ---------------

  /**
   * @return void
   */
  private function updateAllFields() {
    $service = Craft::$app->getFields();
    $service->refreshFields();

    foreach ($service->getAllFields() as $field) {
      $this->updateField($field, Table::CONTENT);
    }
  }

  /**
   * @return void
   */
  private function updateAllSettings() {
    $this->update(Table::FIELDS, [
      'type' => 'lenz\linkfield\fields\LinkField',
    ], [
      'type' => 'typedlinkfield\fields\LinkField'
    ]);

    $rows = (new Query())
      ->select(['id', 'settings'])
      ->from(Table::FIELDS)
      ->where(['type' => 'lenz\linkfield\fields\LinkField'])
      ->all();

    foreach ($rows as $row) {
      $this->update(Table::FIELDS, [
        'settings' => $this->updateSettings($row['settings'])
      ], [
        'id' => $row['id']
      ]);
    }
  }

  /**
   * @param FieldInterface $field
   * @param string $table
   */
  private function updateField(FieldInterface $field, string $table) {
    if ($field instanceof LinkField) {
      $this->updateLinkField($field, $table);
    } elseif ($field instanceof Matrix) {
      $this->updateMatrixField($field);
    } elseif ($field instanceof SuperTableField) {
      $this->updateSuperTable($field);
    }
  }

  /**
   * @param Matrix $matrixField
   */
  private function updateMatrixField(Matrix $matrixField) {
    $table = $matrixField->contentTable;
    $blockTypes = Craft::$app
      ->getMatrix()
      ->getBlockTypesByFieldId($matrixField->id);

    foreach ($blockTypes as $blockType) {
      foreach ($blockType->getFields() as $field) {
        $this->updateField($field, $table);
      }
    }
  }

  /**
   * @param LinkField $field
   * @param string $table
   */
  private function updateLinkField(LinkField $field, string $table) {
    $insertRows = [];
    $columnName = ($field->columnPrefix ?: 'field_') . $field->handle;
    $rows = (new Query())
      ->select([
        'elementId',
        'siteId',
        $columnName,
      ])
      ->from($table)
      ->all();

    foreach ($rows as $row) {
      $payload = Json::decode($row[$columnName]);
      if (!is_array($payload)) {
        continue;
      }

      $type  = isset($payload['type'])  ? $payload['type']  : null;
      $value = isset($payload['value']) ? $payload['value'] : '';
      unset($payload['type']);
      unset($payload['value']);

      if ($value && is_numeric($value)) {
        $doesExist = (new Query())
          ->select('id')
          ->where(['id' => $value])
          ->from('{{%elements}}')
          ->exists();

        if (!$doesExist) {
          $value = null;
        }
      }

      $insertRows[] = [
        $row['elementId'],                          // elementId
        $row['siteId'],                             // siteId
        $field->id,                                 // fieldId
        is_numeric($value) ? $value : null,         // linkedId
        is_numeric($value) ? $row['siteId'] : null, // linkedSiteId
        $type,                                      // type
        is_numeric($value) ? null : $value,         // linkedUrl
        Json::encode($payload)                      // payload
      ];
    }

    $this->batchInsert(LinkRecord::tableName(), [
      'elementId', 'siteId', 'fieldId', 'linkedId', 'linkedSiteId', 'type', 'linkedUrl', 'payload'
    ], $insertRows);

    $this->dropColumn($table, $columnName);
  }

  /**
   * @param string $settings
   * @return string
   */
  private function updateSettings(string $settings) {
    $settings = Json::decode($settings);
    if (!is_array($settings)) {
      $settings = array();
    }

    if (!array_key_exists('typeSettings', $settings)) {
      $settings['typeSettings'] = array();
    }

    $settings['enableAllLinkTypes'] = false;

    if (isset($settings['allowedLinkNames'])) {
      $allowedLinkNames = $settings['allowedLinkNames'];
      if (!is_array($allowedLinkNames)) {
        $allowedLinkNames = [$allowedLinkNames];
      }

      foreach ($allowedLinkNames as $linkName) {
        if ($linkName == '*') {
          $settings['enableAllLinkTypes'] = true;
        } else {
          $settings['typeSettings'][$linkName]['enabled'] = true;
        }
      }
    }

    unset($settings['allowedLinkNames']);
    return Json::encode($settings);
  }

  /**
   * @param SuperTableField $superTableField
   */
  private function updateSuperTable(SuperTableField $superTableField) {
    foreach ($superTableField->getBlockTypeFields() as $field) {
      $this->updateField($field, $superTableField->contentTable);
    }
  }
}
