<?php

namespace lenz\linkfield\models\input;

use Craft;
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
  public $linkedUrl;


  /**
   * @inheritDoc
   */
  public function attributes() {
    return array_merge(parent::attributes(), [
      'linkedUrl',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getIntrinsicUrl() {
    if ($this->isEmpty()) {
      return null;
    }

    $url = $this->linkedUrl;
    if ($this->getLinkType()->allowAliases) {
      $url = (string)Craft::parseEnv($url);
    }

    switch ($this->getLinkType()->inputType) {
      case 'email':
        return 'mailto:' . $url;
      case 'tel':
        return 'tel:' . $url;
      default:
        return $url;
    }
  }

  /**
   * @inheritDoc
   */
  public function isEmpty() {
    return empty($this->linkedUrl);
  }

  /**
   * @inheritDoc
   */
  public function rules() {
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
  protected function getEnableIDN() {
    return (
      Craft::$app->getI18n()->getIsIntlLoaded() &&
      defined('INTL_IDNA_VARIANT_UTS46')
    );
  }

  /**
   * @param string $attribute
   */
  protected function validateGenericUrl(string $attribute) {
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
   * @param $attribute
   */
  protected function validateEmailUrl($attribute) {
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
   * @param $attribute
   */
  protected function validateTelUrl($attribute) {
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
