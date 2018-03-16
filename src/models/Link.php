<?php

namespace typedlinkfield\models;

use craft\helpers\Html;
use craft\helpers\Template;
use typedlinkfield\Plugin;
use yii\base\Model;

/**
 * Class Link
 * @package typedlinkfield\models
 */
class Link extends Model
{
  /**
   * @var bool
   */
  public $allowCustomText;

  /**
   * @var bool
   */
  public $allowTarget;

  /**
   * @var string
   */
  public $customText;

  /**
   * @var string
   */
  public $defaultText;

  /**
   * @var string
   */
  public $target;

  /**
   * @var string
   */
  public $type;

  /**
   * @var mixed
   */
  public $value;


  /**
   * @return null|\craft\base\ElementInterface
   */
  public function getElement() {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? null : $linkType->getElement($this);
  }

  /**
   * Renders a complete link tag.
   *
   * You can either pass the desired content of the link as a string, e.g.
   * ```
   * {{ entry.linkField.link('Imprint') }}
   * ```
   *
   * or you can pass an array of attributes which can contain the key `text`
   * which will be used as the link text. When doing this you can also override
   * the default attributes `href` and `target` if you want to.
   * ```
   * {{ entry.linkField.link({
   *   class: 'my-link-class',
   *   text: 'Imprint',
   * }) }}
   * ```
   *
   * @param array|string|null $attributesOrText
   * @return null|\Twig_Markup
   */
  public function getLink($attributesOrText = null) {
    $text = $this->getText();
    $url = $this->getUrl();
    if (is_null($text) || is_null($url)) {
      return null;
    }

    $attr = [ 'href' => $url ];
    $target = $this->getTarget();
    if (!is_null($target)) {
      $attr['target'] = $target;
    }

    // If a string is passed, override the text component
    if (is_string($attributesOrText)) {
      $text = $attributesOrText;

    // If an array is passed, use it as tag attributes
    } elseif (is_array($attributesOrText)) {
      if (array_key_exists('text', $attributesOrText)) {
        $text = $attributesOrText['text'];
        unset($attributesOrText['text']);
      }

      $attr = $attributesOrText + $attr;
    }

    return Template::raw(Html::tag('a', $text, $attr));
  }

  /**
   * @return LinkTypeInterface|null
   */
  public function getLinkType() {
    $linkTypes = Plugin::getInstance()->getLinkTypes();
    return array_key_exists($this->type, $linkTypes)
      ? $linkTypes[$this->type]
      : null;
  }

  /**
   * @return null|string
   */
  public function getTarget() {
    return $this->allowTarget && !empty($this->target) ? $this->target : null;
  }

  /**
   * @return null|string
   */
  public function getText() {
    if ($this->allowCustomText && !empty($this->customText)) {
      return $this->customText;
    }

    $linkType = $this->getLinkType();
    if (!is_null($linkType)) {
      $linkText = $linkType->getText($this);

      if (!is_null($linkText)) {
        return $linkText;
      }
    }

    return \Craft::t('site', $this->defaultText);
  }

  /**
   * @return null|string
   */
  public function getUrl() {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? null : $linkType->getUrl($this);
  }

  /**
   * @return bool
   */
  public function hasElement() {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? false : $linkType->hasElement($this);
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? true : $linkType->isEmpty($this);
  }

  /**
   * @return string
   */
  public function __toString() {
    $url = $this->getUrl();
    return is_null($url) ? '' : $url;
  }
}
