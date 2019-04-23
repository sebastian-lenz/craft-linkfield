<?php

namespace lenz\linkfield\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\records\LinkRecord;

/**
 * m190417_202153_migrateDataToTable migration.
 */
class m190417_202153_migrateDataToTable extends Migration
{
  /**
   * @inheritdoc
   */
  public function safeUp() {
    if (!$this->db->tableExists(LinkRecord::TABLE_NAME)) {
      (new Install())->safeUp();
    }

    $this->updateFields();
    $this->updateFieldInstances();
  }

  /**
   * @inheritdoc
   */
  public function safeDown() {
    echo "m190417_202153_migrateDataToTable cannot be reverted.\n";
    return false;
  }

  /**
   * @return void
   */
  private function updateFieldInstances() {
    $fields = Craft::$app->getFields();
    $fields->refreshFields();
    $allFields = $fields->getAllFields(false);

    foreach ($allFields as $field) {
      if ($field instanceof LinkField) {
        $this->updateFieldInstance($field);
      }
    }
  }

  /**
   * @param LinkField $field
   */
  private function updateFieldInstance(LinkField $field) {
    $insertRows = [];
    $columnName = 'field_' . $field->handle;
    $rows = (new Query())
      ->select([
        'elementId',
        'siteId',
        $columnName,
      ])
      ->from(Table::CONTENT)
      ->all();

    foreach ($rows as $row) {
      $payload = Json::decode($row[$columnName]);
      if (!is_array($payload)) {
        continue;
      }

      $type  = isset($payload['type']) ? $payload['type'] : null;
      $value = isset($payload['value']) ? $payload['value'] : '';
      unset($payload['type']);
      unset($payload['value']);

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

    $this->batchInsert(LinkRecord::TABLE_NAME, [
      'elementId', 'siteId', 'fieldId', 'linkedId', 'linkedSiteId', 'type', 'linkedUrl', 'payload'
    ], $insertRows);

    $this->dropColumn(Table::CONTENT, $columnName);
  }

  /**
   * @return void
   */
  private function updateFields() {
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
        'settings' => $this->updateFieldSettings($row['settings'])
      ], [
        'id' => $row['id']
      ]);
    }

    Craft::$app->getFields()->refreshFields();
  }

  /**
   * @param string $settings
   * @return string
   */
  private function updateFieldSettings($settings) {
    $settings = Json::decode($settings);
    if (!is_array($settings)) {
      $settings = array();
    }

    if (!array_key_exists('typeSettings', $settings)) {
      $settings['typeSettings'] = array();
    }

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
}
