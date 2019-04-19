<?php

namespace lenz\linkfield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use Exception;
use lenz\linkfield\Plugin;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;
use lenz\linkfield\records\LinkRecord;
use Throwable;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Class LinkField
 * @package lenz\linkfield\fields
 */
class LinkField extends Field
{
  /**
   * @var bool
   */
  public $allowCustomText = true;

  /**
   * @var bool
   */
  public $allowTarget = false;

  /**
   * @var bool
   */
  public $autoNoReferrer = false;

  /**
   * @var string
   */
  public $defaultLinkName = '';

  /**
   * @var string
   */
  public $defaultText = '';

  /**
   * @var bool
   */
  public $enableAriaLabel = false;

  /**
   * @var bool
   */
  public $enableAllLinkTypes = false;

  /**
   * @var bool
   */
  public $enableTitle = false;

  /**
   * @var LinkType[]
   */
  private $_linkTypes;


  /**
   * @inheritDoc
   */
  public function afterElementSave(ElementInterface $element, bool $isNew) {
    parent::afterElementSave($element, $isNew);

    try {
      $link = $element->getFieldValue($this->handle);
    } catch (\Throwable $error) {
      $link = null;
    }

    if ($link instanceof Link) {
      $record = $this->createRecord($element);
      $link->getLinkType()->createRecord($link, $record);
      $record->save();
    }
  }

  /**
   * @inheritDoc
   */
  public function behaviors() {
    return [
      'typecast' => [
        'class' => AttributeTypecastBehavior::class,
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function modifyElementsQuery(ElementQueryInterface $query, $value) {
    LinkFieldLoader::attachTo($this, $query, $value);
    return null;
  }

  /**
   * @param $value
   * @param ElementInterface|null $element
   * @return Link
   */
  public function normalizeValue($value, ElementInterface $element = null) {
    if ($value instanceof Link) {
      return $value;
    }

    if (is_string($value)) {
      try {
        $value = Json::decode($value, true);
      } catch (Exception $e) {}
    }

    if (!is_array($value)) {
      $record = $this->findRecord($element);
      $value = is_null($record) ? [] : $record->getAttributes();
    }

    return $this
      ->resolveLinkType(isset($value['type']) ? $value['type'] : '')
      ->createLink($this, $element, $value);
  }

  /**
   * @return LinkType[]
   */
  public function getAvailableLinkTypes() {
    if (!isset($this->_linkTypes)) {
      $linkTypes = Plugin::getInstance()->getLinkTypes($this);
      foreach ($linkTypes as $name => $linkType) {
        $linkType->name = $name;
      }

      $this->_linkTypes = $linkTypes;
    }

    return $this->_linkTypes;
  }

  /**
   * @return array
   */
  public function getElementValidationRules(): array {
    return [
      [LinkFieldValidator::class],
    ];
  }

  /**
   * @return LinkType[]
   */
  public function getEnabledLinkTypes() {
    if ($this->enableAllLinkTypes) {
      return $this->getAvailableLinkTypes();
    }

    return array_filter(
      $this->getAvailableLinkTypes(),
      function(LinkType $linkType) {
        return $linkType->enabled;
      }
    );
  }

  /**
   * @param Link $value
   * @param ElementInterface|null $element
   * @return string
   * @throws Throwable
   */
  public function getInputHtml($value, ElementInterface $element = null): string {
    return $this->render($value, $element);
  }

  /**
   * @return string
   * @throws Throwable
   */
  public function getSettingsHtml() {
    $namespace = Craft::$app->view->namespaceInputId('linkField');
    $linkTypes = [];
    $linkNames = [];

    foreach ($this->getAvailableLinkTypes() as $name => $linkType) {
      $displayName      = $linkType->getDisplayName();
      $linkNames[$name] = $displayName;
      $html = self::safeRender(
        $linkType,
        function(LinkType $linkType) {
          $linkType->getSettingsHtml($this);
        }
      );

      $linkTypes[] = array(
        'displayName' => $displayName,
        'enabled'     => $linkType->enabled,
        'name'        => $name,
        'group'       => $linkType->getDisplayGroup(),
        'settings'    => $html,
      );
    }

    asort($linkNames);
    usort($linkTypes, function($a, $b) {
      return $a['group'] === $b['group']
        ? strcmp($a['displayName'], $b['displayName'])
        : strcmp($a['group'], $b['group']);
    });

    return Craft::$app->getView()->renderTemplate('typedlinkfield/_settings', [
      'field'     => $this,
      'name'      => 'linkField',
      'nameNs'    => $namespace,
      'linkTypes' => $linkTypes,
      'linkNames' => $linkNames,
    ]);
  }

  /**
   * @inheritdoc
   */
  public function getStaticHtml($value, ElementInterface $element): string {
    return $this->render($value, $element, true);
  }

  /**
   * @return array
   */
  public function getTypeSettings() {
    return array_map(function(LinkType $linkType) {
      return $linkType->getSettings();
    }, $this->getAvailableLinkTypes());
  }

  /**
   * @return boolean
   */
  public function hasSettings() {
    return (
      $this->allowCustomText ||
      $this->enableAriaLabel ||
      $this->enableTitle
    );
  }

  /**
   * @param Link $value
   * @param ElementInterface $element
   * @return bool
   */
  public function isValueEmpty($value, ElementInterface $element): bool {
    if ($value instanceof Link) {
      return $value->isEmpty();
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function rules() {
    return array_merge(parent::rules(), [
      ['allowCustomText', 'boolean'],
      ['allowTarget', 'boolean'],
      ['autoNoReferrer', 'boolean'],
      ['defaultLinkName', 'string'],
      ['defaultText', 'string'],
      ['enableAriaLabel', 'boolean'],
      ['enableAllLinkTypes', 'boolean'],
      ['enableTitle', 'boolean'],
      ['typeSettings', 'validateTypeSettings'],
    ]);
  }

  /**
   * @inheritDoc
   */
  public function serializeValue($value, ElementInterface $element = null) {
    if (!($value instanceof Link)) {
      return null;
    }

    $result = $value->getAttributes();
    $result['type'] = $value->getLinkType()->name;
    return $result;
  }

  /**
   * @inheritDoc
   */
  public function settingsAttributes(): array {
    $attributes = parent::settingsAttributes();
    $attributes[] = 'typeSettings';
    return $attributes;
  }

  /**
   * @param array $value
   */
  public function setTypeSettings(array $value) {
    foreach ($this->getAvailableLinkTypes() as $linkName => $linkType) {
      if (array_key_exists($linkName, $value)) {
        $linkType->setSettings($value[$linkName]);
      }
    }
  }

  /**
   * @return void
   */
  public function validateTypeSettings() {
    foreach ($this->getAvailableLinkTypes() as $name => $linkType) {
      if (!$linkType->validate()) {
        $this->addError('linkTypes', 'Invalid link type settings for link type `' . $name . '`.');
        return;
      }
    }
  }


  // Private methods
  // ---------------

  /**
   * @param ElementInterface $element
   * @return LinkRecord|null
   */
  private function createRecord(ElementInterface $element) {
    $record = $this->findRecord($element);
    if (is_null($record)) {
      $record = new LinkRecord([
        'elementId' => $element->id,
        'siteId'    => $element->siteId,
        'fieldId'   => $this->id,
      ]);
    }

    return $record;
  }

  /**
   * @param ElementInterface $element
   * @return LinkRecord|null
   */
  private function findRecord(ElementInterface $element) {
    return LinkRecord::findOne([
      'elementId' => $element->id,
      'siteId'    => $element->siteId,
      'fieldId'   => $this->id,
    ]);
  }

  /**
   * @return LinkType|null
   */
  private function getDefaultLinkType() {
    $linkName = $this->defaultLinkName;
    if (empty($linkName)) {
      return null;
    }

    $allowedLinkTypes = $this->getEnabledLinkTypes();
    return array_key_exists($linkName, $allowedLinkTypes)
      ? $allowedLinkTypes[$linkName]
      : null;
  }

  /**
   * @param Link $value
   * @param ElementInterface $element
   * @return Link
   */
  private function getEditLink(Link $value, ElementInterface $element = null) {
    $defaultLinkType = $this->getDefaultLinkType();
    if (
      $value->isEmpty() &&
      !is_null($defaultLinkType) &&
      $value->getLinkType() !== $defaultLinkType
    ) {
      $value = $defaultLinkType->createLink($this, $value->getOwner(), $value->getAttributes());
    }

    $value->setOwner($element);
    return $value;
  }

  /**
   * @param Link $value
   * @param ElementInterface|null $element
   * @param bool $disabled
   * @return string
   * @throws Throwable
   */
  private function render(Link $value, ElementInterface $element = null, $disabled = false) {
    $linkTypes  = $this->getEnabledLinkTypes();
    $linkNames  = [];
    $linkInputs = [];
    $singleType = count($linkTypes) === 1 ? array_keys($linkTypes)[0] : null;
    $value      = $this->getEditLink($value, $element);

    foreach ($linkTypes as $linkName => $linkType) {
      $linkNames[$linkName] = $linkType->getDisplayName();
      $linkInputs[] = self::safeRender(
        $linkType,
        function(LinkType $linkType) use ($value, $disabled) {
          return $linkType->getInputHtml($value, $disabled);
        }
      );
    }

    asort($linkNames);

    return Craft::$app->getView()->renderTemplate(
      'typedlinkfield/_input',
      [
        'disabled'    => $disabled,
        'field'       => $this,
        'hasSettings' => $this->hasSettings(),
        'linkInputs'  => implode('', $linkInputs),
        'linkNames'   => $linkNames,
        'name'        => $this->handle,
        'nameNs'      => Craft::$app->view->namespaceInputId($this->handle),
        'singleType'  => $singleType,
        'value'       => $value,
      ]
    );
  }

  /**
   * @param string $linkName
   * @return LinkType
   */
  private function resolveLinkType($linkName) {
    $allowedLinkTypes = $this->getEnabledLinkTypes();
    if (array_key_exists($linkName, $allowedLinkTypes)) {
      return $allowedLinkTypes[$linkName];
    }

    if (array_key_exists($this->defaultLinkName, $allowedLinkTypes)) {
      return $allowedLinkTypes[$this->defaultLinkName];
    }

    if (count($allowedLinkTypes) > 0) {
      return reset($allowedLinkTypes);
    }

    return new LinkType();
  }


  // Static methods
  // --------------

  /**
   * @return string
   */
  static public function displayName(): string {
    return Craft::t('typedlinkfield', 'Link field');
  }

  /**
   * @inheritDoc
   */
  static public function hasContentColumn(): bool {
    return false;
  }

  /**
   * @param LinkType $linkType
   * @param callable $callback
   * @return string
   */
  static private function safeRender(LinkType $linkType, callable $callback) {
    try {
      return $callback($linkType);
    } catch (\Throwable $error) {
      \Craft::error($error->getMessage());
      return Html::tag('p', \Craft::t(
        'typedlinkfield',
        'Error: Could not render the template for the field `{name}`.',
        [ 'name' => $linkType->getDisplayName() ]
      ));
    }
  }
}
