<?php

namespace lenz\linkfield\models\input;

use Craft;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;
use lenz\linkfield\records\LinkRecord;

/**
 * Class InputLinkType
 */
class InputLinkType extends LinkType
{
  /**
   * @var bool
   */
  public $allowAliases = false;

  /**
   * @var bool
   */
  public $disableValidation = false;

  /**
   * @var string
   */
  public $displayName;

  /**
   * @var string
   */
  public $inputType = 'text';

  /**
   * @var string
   */
  public $placeholder;

  /**
   * @inheritDoc
   */
  const MODEL_CLASS = InputLink::class;


  /**
   * @inheritDoc
   */
  public function copyToRecord(Link $link, LinkRecord $record) {
    parent::copyToRecord($link, $record);

    $record->url = $link instanceof InputLink
      ? $link->getPlainUrl()
      : null;
  }

  /**
   * @inheritDoc
   */
  public function getDisplayName(): string {
    return Craft::t('typedlinkfield', $this->displayName);
  }

  /**
   * @inheritDoc
   */
  public function getInputHtml(Link $value, bool $disabled): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_input-input',
      [
        'inputField' => $this->getInputField($value, $disabled),
        'linkType'   => $this,
        'selected'   => $this->isSelected($value),
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function getSettingsHtml(LinkField $field): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_settings-input',
      [
        'linkType' => $this,
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function rules() {
    return array_merge(parent::rules(), [
      ['allowAliases', 'boolean'],
      ['disableValidation', 'boolean'],
    ]);
  }

  /**
   * @inheritDoc
   */
  public function settingsAttributes(): array {
    return array_merge(parent::settingsAttributes(), [
      'allowAliases',
      'disableValidation',
    ]);
  }


  // Protected methods
  // -----------------

  /**
   * @param Link $value
   * @param bool $disabled
   * @return array
   */
  protected function getInputField(Link $value, bool $disabled) {
    $field = $this->getFieldSettings(
      $value,
      'url',
      [
        'disabled' => $disabled,
        'value'    => $this->isSelected($value) && $value instanceOf InputLink
          ? $value->getPlainUrl()
          : '',
      ]
    );

    if (isset($this->inputType) && !$this->disableValidation) {
      $field['type'] = $this->inputType;
    }

    if (isset($this->placeholder)) {
      $field['placeholder'] = Craft::t('typedlinkfield', $this->placeholder);
    }

    return $field;
  }

  /**
   * @inheritDoc
   */
  protected function prepareLegacyData($data) {
    return [
      'url' => (string)$data
    ];
  }
}
