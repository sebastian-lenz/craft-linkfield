<?php

namespace lenz\linkfield\listeners;

use Craft;
use craft\base\ElementInterface;
use craft\queue\BaseJob;
use Exception;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\element\ElementLinkType;
use lenz\linkfield\models\LinkTypeCollection;
use lenz\linkfield\records\LinkRecord;

/**
 * Class CacheListenerJob
 */
class CacheListenerJob extends BaseJob
{
  /**
   * @var int
   */
  public int $fieldId;


  /**
   * @inheritdoc
   * @throws Exception
   */
  public function execute($queue): void {
    $state = ElementListenerState::getInstance();
    $field = $this->getField();
    if (is_null($field)) {
      return;
    }

    $conditions = $state->getFieldElementLinkConditions($field->id);
    if (is_null($conditions)) {
      return;
    }

    LinkRecord::updateAll([
      'linkedTitle' => null,
      'linkedUrl'   => null,
    ], $conditions);

    if (!$field->enableElementCache) {
      return;
    }

    $linkTypes = $field->getEnabledLinkTypes();
    $elementTypes = $this->getElementMap($conditions, $linkTypes);
    if (is_null($elementTypes)) {
      return;
    }

    $index = 0;
    $total = 0;
    foreach ($elementTypes as $sites) {
      foreach ($sites as $elementIds) {
        $total += count($elementIds);
      }
    }

    /** @var ElementInterface $elementType */
    foreach ($elementTypes as $elementType => $sites) {
      foreach ($sites as $siteId => $elementIds) {
        $elements = $elementType::find()
          ->siteId($siteId)
          ->id($elementIds)
          ->all();

        foreach ($elements as $element) {
          ElementListener::updateElement($element);

          $index += 1;
          $this->setProgress($queue, $index / $total);
        }
      }
    }
  }


  // Protected methods
  // -----------------

  /**
   * @inheritdoc
   */
  protected function defaultDescription(): ?string {
    return Craft::t('app', 'Cache {field} element links', [
      'field' => $this->getFieldName(),
    ]);
  }

  /**
   * @param array $conditions
   * @param LinkTypeCollection $linkTypes
   * @return array|null
   */
  protected function getElementMap(array $conditions, LinkTypeCollection $linkTypes): ?array {
    $siteIds = Craft::$app->getSites()->getAllSiteIds(false);
    $links = LinkRecord::find()->where($conditions)->all();
    $result = [];

    /** @var LinkRecord $link */
    foreach ($links as $link) {
      $linkType = $linkTypes->getByName($link->type);
      if (!($linkType instanceof ElementLinkType)) {
        continue;
      }

      $elementType = $linkType->elementType;
      $elementId = $link->linkedId;
      $siteId = empty($link->linkedSiteId)
        ? $link->siteId
        : $link->linkedSiteId;

      if (empty($siteId) || empty($elementId) || !in_array($siteId, $siteIds)) {
        continue;
      }

      if (!isset($result[$elementType])) {
        $result[$elementType] = [];
      }

      if (!isset($result[$elementType][$siteId])) {
        $result[$elementType][$siteId] = [];
      }

      if (!in_array($elementId, $result[$elementType][$siteId])) {
        $result[$elementType][$siteId][] = $elementId;
      }
    }

    return $result;
  }

  /**
   * @return LinkField|null
   */
  protected function getField(): ?LinkField {
    $field = Craft::$app->getFields()->getFieldById($this->fieldId);
    return $field instanceof LinkField ? $field : null;
  }

  /**
   * @return string
   */
  protected function getFieldName(): string {
    $field = $this->getField();
    return is_null($field)
      ? '(unknown)'
      : $field->name;
  }


  // Static methods
  // --------------

  /**
   * @param LinkField $field
   */
  static function createForField(LinkField $field): void {
    Craft::$app->getQueue()->push(new CacheListenerJob([
      'description' => Craft::t('app', 'Cache {field} element links', [
        'field' => $field->name,
      ]),
      'fieldId' => $field->id,
    ]));
  }
}
