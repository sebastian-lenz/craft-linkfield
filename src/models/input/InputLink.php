<?php

namespace lenz\linkfield\models\input;

use Craft;
use craft\helpers\App;
use craft\validators\UrlValidator;
use lenz\linkfield\models\Link;
use yii\validators\EmailValidator;

/**
 * Class InputLink
 *
 * @property InputLinkType $_linkType
 * @method InputLinkType getLinkType()
 */
class InputLink extends Link
{
  /**
   * @var string
   */
  public string $linkedUrl = '';


  /**
   * @inheritDoc
   */
  public function attributes(): array {
    return array_merge(parent::attributes(), [
      'linkedUrl',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicUrl(): ?string {
    if ($this->isEmpty()) {
      return null;
    }

    $url = $this->linkedUrl;
    if ($this->getLinkType()->allowAliases) {
      $url = (string)App::parseEnv($url);
    }

    return match ($this->getLinkType()->inputType) {
      'email' => 'mailto:' . $url,
      'tel' => 'tel:' . $url,
      default => $url,
    };
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(): bool {
    return empty($this->linkedUrl);
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    return array_merge(parent::rules(), [
      ['linkedUrl', 'validateUrl']
    ]);
  }

  /**
   * @param string $attribute
   * @return void
   * @noinspection PhpUnused (Use in model rules)
   */
  public function validateUrl(string $attribute) {
    $linkType = $this->getLinkType();
    if ($this->isEmpty() || $linkType->disableValidation) {
      return;
    }

    switch ($linkType->inputType) {
      case 'email':
        $this->validateEmailUrl($attribute);
        return;
      case 'tel':
        $this->validateTelUrl($attribute);
        return;
      case 'url':
        $this->validateGenericUrl($attribute);
        return;
    }
  }

  // Protected methods
  // -----------------

  /**
   * @return bool
   */
  protected function getEnableIDN(): bool {
    return defined('INTL_IDNA_VARIANT_UTS46');
  }

  /**
   * @param string $attribute
   */
  protected function validateGenericUrl(string $attribute): void {
    $error = null;
    $validator = new UrlValidator([
      'enableIDN' => $this->getEnableIDN(),
    ]);

    $validator->validate($this->$attribute, $error);
    if (!is_null($error)) {
      $this->addError($attribute, $error);
    }
  }

  /**
   * @param string $attribute
   */
  protected function validateEmailUrl(string $attribute): void {
    $error = null;
    $validator = new EmailValidator([
      'enableIDN' => $this->getEnableIDN(),
    ]);

    $validator->validate($this->$attribute, $error);
    if (!is_null($error)) {
      $this->addError($attribute, $error);
    }
  }

  /**
   * @param string $attribute
   */
  protected function validateTelUrl(string $attribute): void {
    $isValid = filter_var($this->$attribute, FILTER_VALIDATE_REGEXP, [
      'options' => [
        'regexp' => '/^[0-9+\(\)#\.\s\/ext-]+$/',
      ]
    ]);

    if (!$isValid) {
      $this->addError($attribute, Craft::t('typedlinkfield', 'Please enter a valid phone number.'));
    }
  }
}
