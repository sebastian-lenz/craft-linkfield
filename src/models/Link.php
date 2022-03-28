<?php

/** @noinspection PhpUnused */

namespace typedlinkfield\models;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\models\Site;
use Exception;
use Twig\Markup;
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
  public ?string $ariaLabel = null;

  /**
   * @var string|null
   */
  public ?string $customQuery = null;

  /**
   * @var string|null
   */
  public ?string $customText = null;

  /**
   * @var string
   */
  public string $target = '';

  /**
   * @var string|null
   */
  public ?string $title = null;

  /**
   * @var string
   */
  public string $type = '';

  /**
   * @var mixed
   */
  public mixed $value = '';

  /**
   * @var ElementInterface
   */
  private ElementInterface $prefetchedElement;

  /**
   * @var LinkField|null
   */
  private ?LinkField $linkField;

  /**
   * @var ElementInterface|null
   */
  private ?ElementInterface $owner;


  /**
   * Link constructor.
   * @param array $config
   */
  public function __construct($config = []) {
    $this->linkField = $config['linkField'] ?? null;
    $this->owner = $config['owner'] ?? null;
    unset($config['linkField']);
    unset($config['owner']);

    parent::__construct($config);
  }

  /**
   * @return bool
   */
  public function getAllowCustomText(): bool {
    return is_null($this->linkField)
      ? false
      : $this->linkField->allowCustomText;
  }

  /**
   * @return bool
   */
  public function getAllowTarget(): bool {
    return is_null($this->linkField)
      ? false
      : $this->linkField->allowTarget;
  }

  /**
   * @return null|string
   */
  public function getAriaLabel(): ?string {
    return $this->ariaLabel;
  }

  /**
   * @return string
   */
  public function getDefaultText(): string {
    return is_null($this->linkField)
      ? ''
      : $this->linkField->defaultText;
  }

  /**
   * @param bool $ignoreStatus
   * @return null|ElementInterface
   */
  public function getElement(bool $ignoreStatus = false): ?ElementInterface {
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
  public function getEnableAriaLabel(): bool {
    return is_null($this->linkField)
      ? false
      : $this->linkField->enableAriaLabel;
  }

  /**
   * @return bool
   */
  public function getEnableTitle(): bool {
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
   * @return null|Markup
   */
  public function getLink(array|string $attributesOrText = null): ?Markup {
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
   * @return Markup
   */
  public function getLinkAttributes(array $extraAttributes = null): Markup {
    $attributes = $this->getRawLinkAttributes($extraAttributes);
    return Template::raw(is_null($attributes)
      ? ''
      : Html::renderTagAttributes($attributes)
    );
  }

  /**
   * @return null|LinkField
   */
  public function getLinkField(): ?LinkField {
    return $this->linkField;
  }

  /**
   * @return LinkTypeInterface|null
   */
  public function getLinkType(): ?LinkTypeInterface {
    $linkTypes = Plugin::getInstance()->getLinkTypes();
    return array_key_exists($this->type, $linkTypes)
      ? $linkTypes[$this->type]
      : null;
  }

  /**
   * @return ElementInterface|null
   */
  public function getOwner(): ?ElementInterface {
    return $this->owner;
  }

  /**
   * @return Site
   */
  public function getOwnerSite(): Site {
    if ($this->owner instanceof Element) {
      try {
        return $this->owner->getSite();
      } catch (Exception) { }
    }

    return Craft::$app->sites->currentSite;
  }

  /**
   * Return an array defining the common link attributes (`href`, `target`
   * `title` and `arial-label`) of this link.
   * Returns NULL if this link has no target url.
   *
   * @param array|null $extraAttributes
   * @return array|null
   */
  public function getRawLinkAttributes(array $extraAttributes = null): ?array {
    $url = $this->getUrl();
    if (is_null($url)) {
      return null;
    }

    $attributes = [ 'href' => $url ];

    $ariaLabel = $this->getAriaLabel();
    if (!empty($ariaLabel)) {
      $attributes['aria-label'] = $ariaLabel;
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
  public function getTarget(): ?string {
    return $this->getAllowTarget() && !empty($this->target)
      ? $this->target
      : null;
  }

  /**
   * @return null|string
   */
  public function getText(): ?string {
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

    return Craft::t('site', $this->getDefaultText());
  }

  /**
   * @return null|string
   */
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * Try to use provided custom text or field default.
   * Allows user to specify a fallback string if the custom text and default are not set.
   *
   * @param string $fallbackText
   * @return string|null
   */
  public function getCustomText(string $fallbackText = "Learn More"): ?string {
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
  public function getUrl(): ?string {
    $linkType = $this->getLinkType();
    return is_null($linkType) ? null : $linkType->getUrl($this);
  }

  /**
   * @param bool $ignoreStatus
   * @return bool
   */
  public function hasElement(bool $ignoreStatus = false): bool {
    $linkType = $this->getLinkType();
    return !is_null($linkType) && $linkType->hasElement($this, $ignoreStatus);
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    $linkType = $this->getLinkType();
    return is_null($linkType) || $linkType->isEmpty($this);
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
