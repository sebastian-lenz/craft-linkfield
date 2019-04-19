<?php

namespace lenz\linkfield\records;

use craft\db\ActiveRecord;
use craft\helpers\Json;
use craft\records\Element;
use craft\records\Field;
use craft\records\Site;
use lenz\linkfield\models\Link;
use yii\db\ActiveQueryInterface;

/**
 * Class LinkRecord
 *
 * @property int $id
 * @property int $elementId
 * @property int $siteId
 * @property int $fieldId
 * @property string $type
 * @property string $url
 * @property int $linkedId
 * @property int $linkedSiteId
 * @property string $linkedTitle
 * @property string $payload
 */
class LinkRecord extends ActiveRecord
{
  /**
   * @var string
   */
  const TABLE_NAME = '{{%linkfield}}';


  /**
   * @return ActiveQueryInterface
   */
  public function getElement(): ActiveQueryInterface {
    return $this->hasOne(Element::class, ['id' => 'elementId']);
  }

  /**
   * @return ActiveQueryInterface
   */
  public function getField(): ActiveQueryInterface {
    return $this->hasOne(Field::class, ['id' => 'fieldId']);
  }

  /**
   * @return ActiveQueryInterface
   */
  public function getLinkedElement() {
    return $this->hasOne(Element::class, ['id' => 'linkedId']);
  }

  /**
   * @return ActiveQueryInterface
   */
  public function getLinkedSite() {
    return $this->hasOne(Site::class, ['id' => 'linkedSiteId']);
  }

  /**
   * @return ActiveQueryInterface
   */
  public function getSite(): ActiveQueryInterface {
    return $this->hasOne(Site::class, ['id' => 'siteId']);
  }

  /**
   * @inheritdoc
   */
  public static function tableName(): string {
    return self::TABLE_NAME;
  }
}
