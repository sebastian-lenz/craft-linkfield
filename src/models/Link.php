<?php

namespace typedlinkfield\models;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\helpers\Html;
use craft\helpers\Template;
use typedlinkfield\fields\LinkField;
use typedlinkfield\Plugin;

/**
 * Class Link
 * @package typedlinkfield\models
 */
class Link extends Model
{
  /**
   * @var string|null
   */
  public $ariaLabel;

  /**
   * @var string|null
   */
  public $customQuery;

  /**
   * @var string|null
   */
  public $customText;

  /**
   * @var string
   */
  public $target;

  /**
   * @var string|null
   */
  public $title;

  /**
   * @var string
   */
  public $type;

  /**
   * @var mixed
   */
  public $value;

  /**
   * @var ElementInterface
   */
  private $prefetchedElement;

  /**
   * @var LinkField|null
   */
  private $linkField;

  /**
   * @var ElementInterface|null
   */
  private $owner;


  /**
   * Link constructor.
   * @param array $config
   */
  public function __construct($config = []) {
    $this->linkField = isset($config['linkField'])
      ? $config['linkField']
      : null;

    $this->owner = isset($config['owner'])
      ? $config['owner']
      : null;

    unset($config['linkField']);
    unset($config['owner']);

    parent::__construct($config);
  }

  /**
   * @return bool
   */
  public function getAllowCustomText() {
    return is_null($this->linkField)
      ? false
      : $this->linkField->allowCustomText;
  }

  /**
   * @return bool
   */
  public function getAllowTarget() {
    return is_null($this->linkField)
      ? false
      : $this->linkField->allowTarget;
  }

  /**
   * @return null|string
   */
  public function getAriaLabel() {
    return $this->ariaLabel;
  }

  /**
   * @return string
   */
  public function getDefaultText() {
    return is_null($this->linkField)
      ? ''
      : $this->linkField->defaultText;
  }

  /**
   * @param bool $ignoreStatus
   * @return null|\craft\base\ElementInterface
   */
  public function getElement($ignoreStatus = false) {
    if (
      !isset($this->prefetchedElement) ||
      $this->prefetchedElement->getId() != $this->value
    ) {
      $linkType = $this->getLinkType();
      $element = is_null($linkType)
        ? null
        : $linkType->getElement($this, $ignoreStatus);

      if ($ignoreStatus) {
        return $element;
      } else {
        $this->prefetchedElement = $element;
      }
    }

    return $this->prefetchedElement;
  }

  /**
   * @return bool
   */
  public function getEnableAriaLabel() {
    return is_null($this->linkField)
      ? false
      : $this->linkField->enableAriaLabel;
  }

  /**
   * @return bool
   */
  public function getEnableTitle() {
    return is_null($this->linkField)
      ? false
      : $this->linkField->enableTitle;
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
    $extraAttributes = null;

    if (is_string($attributesOrText)) {
      // If a string is passed, override the text component
      $text = $attributesOrText;

    } elseif (is_array($attributesOrText)) {
      // If an array is passed, use it as tag attributes
      $extraAttributes = $attributesOrText;
      if (array_key_exists('text', $extraAttributes)) {
        $text = $extraAttributes['text'];
        unset($extraAttributes['text']);
      }
    }

    $attributes = $this->getRawLinkAttributes($extraAttributes);
    if (is_null($attributes) || is_null($text)) {
      return null;
    }

    return Template::raw(Html::tag('a', $text, $attributes));
  }

  /**
   * Return the attributes of this link as a rendered html string.
   *
   * @param array|null $extraAttributes
   * @return \Twig_Markup
   */
  public function getLinkAttributes($extraAttributes = null) {
    $attributes = $this->getRawLinkAttributes($extraAttributes);
    return Template::raw(is_null($attributes)
      ? ''
      : Html::renderTagAttributes($attributes)
    );
  }

  /**
   * @return null|LinkField
   */
  public function getLinkField() {
    return $this->linkField;
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
   * @return ElementInterface|null
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * @return \craft\models\Site
   */
  public function getOwnerSite() {
    if ($this->owner instanceof Element) {
      try {
        return $this->owner->getSite();
      } catch (\Exception $e) { }
    }

    return \Craft::$app->sites->currentSite;
  }

  /**
   * Return an array defining the common link attributes (`href`, `target`
   * `title` and `arial-label`) of this link.
   * Returns NULL if this link has no target url.
   *
   * @param null|array $extraAttributes
   * @return array|null
   */
  public function getRawLinkAttributes($extraAttributes = null) {
    $url = $this->getUrl();
    if (is_null($url)) {
      return null;
    }

    $attributes = [ 'href' => $url ];

    $ariaLabel = $this->getAriaLabel();
    if (!empty($ariaLabel)) {
      $attributes['arial-label'] = $ariaLabel;
    }

    $target = $this->getTarget();
    if (!empty($target)) {
      $attributes['target'] = $target;

      if ($target === '_blank' && $this->linkField->autoNoReferrer) {
        $attributes['rel'] = 'noopener noreferrer';
      }
    }

    $title = $this->getTitle();
    if (!empty($title)) {
      $attributes['title'] = $title;
    }

    if (is_array($extraAttributes)) {
      $attributes = $extraAttributes + $attributes;
    }

    return $attributes;
  }

  /**
   * @return null|string
   */
  public function getTarget() {
    return $this->getAllowTarget() && !empty($this->target)
      ? $this->target
      : null;
  }

  /**
   * @return null|string
   */
  public function getText() {
    if ($this->getAllowCustomText() && !empty($this->customText)) {
      return $this->customText;
    }

    $linkType = $this->getLinkType();
    if (!is_null($linkType)) {
      $linkText = $linkType->getText($this);

      if (!is_null($linkText)) {
        return $linkText;
      }
    }

    return \Craft::t('site', $this->getDefaultText());
  }

  /**
   * @return null|string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Try to use provided custom text or field default.
   * Allows user to specify a fallback string if the custom text and default are not set.
   *
   * @param string $fallbackText
   * @return string
   */
  public function getCustomText($fallbackText = "Learn More") {
    if ($this->getAllowCustomText() && !empty($this->customText)) {
      return $this->customText;
    }

    $defaultText = $this->getDefaultText();
    return empty($defaultText)
      ? $fallbackText
      : $defaultText;
  }

  /**
   * @return null|string
   */
  public function getUrl() {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? null : $linkType->getUrl($this);
  }

  /**
   * @param bool $ignoreStatus
   * @return bool
   */
  public function hasElement($ignoreStatus = false) {
    $linkType = $this->getLinkType();
    return is_null($linkType)
      ? false
      : $linkType->hasElement($this, $ignoreStatus);
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? true : $linkType->isEmpty($this);
  }

  /**
   * @internal
   */
  public function setPrefetchedElement($element) {
    $this->prefetchedElement = $element;
  }

  /**
   * @return string
   */
  public function __toString() {
    $url = $this->getUrl();
    return is_null($url) ? '' : $url;
  }
}
