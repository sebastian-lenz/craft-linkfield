<?php

namespace lenz\linkfield\models\input;

use Craft;
use lenz\linkfield\models\Link;

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
  public $url;


  /**
   * @return string
   */
  public function getPlainUrl() {
    return $this->url;
  }

  /**
   * @inheritDoc
   */
  public function getUrl() {
    if ($this->isEmpty()) {
      return null;
    }

    $url = $this->url;
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
    return empty($this->url);
  }

  /**
   * @inheritDoc
   */
  public function rules() {
    return array_merge(parent::rules(), [
      ['url', 'validateUrl']
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

    $url = $this->url;
    switch ($linkType->inputType) {
      case 'email':
        if (!filter_var($url, FILTER_VALIDATE_EMAIL)) {
          return [Craft::t('typedlinkfield', 'Please enter a valid email address.'), []];
        }
        break;

      case 'tel':
        $regexp = '/^[0-9+\(\)#\.\s\/ext-]+$/';
        if (!filter_var($url, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $regexp)))) {
          return [Craft::t('typedlinkfield', 'Please enter a valid phone number.'), []];
        }
        break;

      case 'url':
        $url = idn_to_ascii($url,IDNA_NONTRANSITIONAL_TO_ASCII,INTL_IDNA_VARIANT_UTS46);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
          return [Craft::t('typedlinkfield', 'Please enter a valid url.'), []];
        }
        break;
    }

    return null;
  }
}
