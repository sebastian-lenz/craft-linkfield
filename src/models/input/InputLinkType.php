<?php

namespace lenz\linkfield\models\input;

use Craft;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;

/**
 * Class InputLinkType
 */
class InputLinkType extends LinkType
{
  /**
   * @var bool
   */
  public bool $allowAliases = false;

  /**
   * @var bool
   */
  public bool $disableValidation = false;

  /**
   * @var string
   */
  public string $displayName = '';

  /**
   * @var string
   */
  public string $inputType = 'text';

  /**
   * @var string
   */
  public string $placeholder = '';

  /**
   * @inheritDoc
   */
  const MODEL_CLASS = InputLink::class;


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
  public function rules(): array {
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
  protected function getInputField(Link $value, bool $disabled): array {
    $field = [
      'class' => $value->hasErrors('linkedUrl') ? 'error' : '',
      'disabled' => $disabled,
      'id' => 'linkedUrl',
      'inputAttributes' => ['dir' => 'ltr'],
      'name' => 'linkedUrl',
      'value' => $this->isSelected($value) && $value instanceOf InputLink
        ? $value->linkedUrl
        : '',
    ];

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
  protected function prepareLegacyData(mixed $data): ?array {
    return [
      'linkedUrl' => (string)$data
    ];
  }
}
