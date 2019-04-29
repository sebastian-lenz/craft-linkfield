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
  public function getUrl() {
    if ($this->isEmpty()) {
      return null;
    }

    $url = $this->linkedUrl;
    if ($this->getLinkType()->allowAliases) {
      $url = Craft::getAlias($url);
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
  public function isEmpty(): bool {
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
   * @return array|void|null
   */
  public function validateUrl() {
    $linkType = $this->getLinkType();
    if ($this->isEmpty() || $linkType->disableValidation) {
      return;
    }

    $url = $this->linkedUrl;
    $enableIDN = (
      Craft::$app->getI18n()->getIsIntlLoaded() &&
      defined('INTL_IDNA_VARIANT_UTS46')
    );

    switch ($linkType->inputType) {
      case 'email':
        (new EmailValidator(['enableIDN' => $enableIDN]))->validate($url, $error);
        if (!is_null($error)) {
          return [$error, []];
        }
        break;

      case 'tel':
        $regexp = '/^[0-9+\(\)#\.\s\/ext-]+$/';
        if (!filter_var($url, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $regexp)))) {
          return [Craft::t('typedlinkfield', 'Please enter a valid phone number.'), []];
        }
        break;

      case 'url':
        (new UrlValidator(['enableIDN' => $enableIDN]))->validate($url, $error);
        if (!is_null($error)) {
          return [$error, []];
        }
        break;
    }

    return null;
  }
}
