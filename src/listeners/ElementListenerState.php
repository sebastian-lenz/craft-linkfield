<?php

namespace lenz\linkfield\listeners;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Db;
use DateTime;
use Exception;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\element\ElementLinkType;
use Throwable;

/**
 * Class ElementListenerState
 */
class ElementListenerState
{
  /**
   * @var array
   */
  public array $fields;

  /**
   * @var int
   */
  public int $lastChangeDate;

  /**
   * @var int|null
   */
  public ?int $nextEntryChangeDate = null;

  /**
   * @var ElementListenerState
   */
  private static ElementListenerState $_instance;


  /**
   * ElementListener constructor.
   * @throws Exception
   */
  public function __construct() {
    $this->reset();
  }

  /**
   * @return array|null
   */
  public function getCachedElementLinkConditions(): ?array {
    $specs = array_filter(
      $this->fields,
      function ($fieldInfo) {
        return $fieldInfo['enableElementCache'];
      }
    );

    if (count($specs) == 0) {
      return null;
    }

    return $this->getElementLinkConditions($specs);
  }

  /**
   * @param int|string|null $fieldId
   * @return array|null
   */
  public function getFieldElementLinkConditions(int|string|null $fieldId): ?array {
    $specs = array_filter(
      $this->fields,
      function ($key) use ($fieldId) {
        return $key == $fieldId;
      },
      ARRAY_FILTER_USE_KEY
    );

    if (count($specs) == 0) {
      return null;
    }

    return $this->getElementLinkConditions($specs);
  }

  /**
   * @return bool
   */
  public function isCacheEnabled(): bool {
    foreach ($this->fields as $field) {
      if (isset($field['enableElementCache']) && $field['enableElementCache']) {
        return true;
      }
    }

    return false;
  }

  /**
   * @throws Exception
   */
  public function flush(): void {
    $timeMin = Db::prepareDateForDb($this->lastChangeDate);
    $timeMax = Db::prepareDateForDb(new DateTime());
    $query = new Query();
    $rows = $query
      ->from('{{%entries}} entries')
      ->leftJoin('{{%elements}} elements', '[[elements.id]] = [[entries.id]]')
      ->select(['entries.id', 'elements.type'])
      ->where([
        'or',
        [
          'and',
          ['>=', '[[entries.postDate]]', $timeMin],
          ['<=', '[[entries.postDate]]', $timeMax],
        ],
        [
          'and',
          ['>=', '[[entries.expiryDate]]', $timeMin],
          ['<=', '[[entries.expiryDate]]', $timeMax],
        ],
      ])
      ->all();

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
      foreach ($rows as $row) {
        /** @var ElementInterface $elementType */
        $elementType = $row['type'];
        $element = $elementType::findOne([
          'id'   => $row['id'],
          'site' => $site
        ]);

        if ($element) {
          ElementListener::updateElement($element);
        }
      }
    }

    $this->lastChangeDate = time();
    $this->nextEntryChangeDate = $this->loadNextEntryChangeDate();
    $this->save();
  }

  /**
   * @throws Exception
   */
  public function reset(): void {
    $this->fields = $this->loadElementFields();
    $this->lastChangeDate = time();
    $this->nextEntryChangeDate = $this->loadNextEntryChangeDate();
    $this->save();
  }

  /**
   * @throws Exception
   */
  public function updateChangeDate(): void {
    $this->nextEntryChangeDate = $this->loadNextEntryChangeDate();
    $this->save();
  }

  /**
   * @return void
   */
  public function updateFields(): void {
    $this->fields = $this->loadElementFields();
    $this->save();
  }


  // Protected methods
  // -----------------

  /**
   * @param array $specs
   * @return array
   */
  protected function getElementLinkConditions(array $specs): array {
    $conditions = ['or'];
    foreach ($specs as $fieldId => $fieldInfo) {
      $conditions[] = [
        'fieldId' => $fieldId,
        'type'    => $fieldInfo['elementLinkNames']
      ];
    }

    return $conditions;
  }

  /**
   * @return array
   */
  protected function loadElementFields(): array {
    $result = array();
    $allFields = Craft::$app
      ->getFields()
      ->getAllFields(false);

    foreach ($allFields as $field) {
      if (!($field instanceof LinkField)) {
        continue;
      }

      $elementLinkNames = $field
        ->getEnabledLinkTypes()
        ->getAllByClass(ElementLinkType::class)
        ->getNames();

      if (count($elementLinkNames) > 0) {
        $result[$field->id] = [
          'elementLinkNames'   => $elementLinkNames,
          'enableElementCache' => $field->enableElementCache,
        ];
      }
    }

    return $result;
  }

  /**
   * @return int|null
   * @throws Exception
   */
  protected function loadNextEntryChangeDate(): ?int {
    $now = Db::prepareDateForDb(new DateTime());
    $nextPost = Entry::find()
      ->status(null)
      ->postDate("> $now")
      ->orderBy('postDate')
      ->one();

    $nextExpiry = Entry::find()
      ->status(null)
      ->expiryDate("> $now")
      ->orderBy('expiryDate')
      ->one();

    $result = $nextPost instanceof Entry && !is_null($nextPost->postDate)
      ? $nextPost->postDate
      : null;

    if ($nextExpiry instanceof Entry && !is_null($nextExpiry->expiryDate)) {
      if (is_null($result)) {
        $result = $nextExpiry->expiryDate;
      } else {
        $result = $result->getTimestamp() > $nextExpiry->expiryDate->getTimestamp()
          ? $nextExpiry->expiryDate
          : $result;
      }
    }

    return is_null($result)
      ? null
      : $result->getTimestamp();
  }

  /**
   * @return void
   */
  protected function save(): void {
    try {
      Craft::$app->getCache()->set(self::class, $this);
    } catch (Throwable $error) {
      Craft::error($error->getMessage());
    }
  }


  // Static methods
  // --------------

  /**
   * @return ElementListenerState
   * @throws Exception
   */
  public static function getInstance(): ElementListenerState {
    if (!isset(self::$_instance)) {
      $instance = Craft::$app->getCache()->get(self::class);
      if (!($instance instanceof ElementListenerState)) {
        $instance = new ElementListenerState();
      }

      self::$_instance = $instance;
    }

    return self::$_instance;
  }

  /**
   * @throws Exception
   */
  public static function refresh() {
    self::$_instance = new ElementListenerState();
    self::$_instance->save();
  }
}
