<?php

namespace lenz\linkfield\models;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\SiteNotFoundException;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\models\Site;
use Exception;
use lenz\craft\utils\foreignField\ForeignFieldModel;
use lenz\craft\utils\helpers\ArrayHelper;
use lenz\linkfield\fields\LinkField;
use Twig\Markup;

/**
 * Class Link
 *
 * @method LinkField getField()
 * @property LinkField $_field
 */
class Link extends ForeignFieldModel
{
  /**
   * @var string|null
   */
  public ?string $ariaLabel = null;

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
   * @var LinkType
   */
  protected LinkType $_linkType;


  /**
   * Link constructor.
   *
   * @param LinkField $field
   * @param LinkType $linkType
   * @param ElementInterface|null $owner
   * @param array $config
   */
  public function __construct(
    LinkField $field,
    LinkType $linkType,
    ElementInterface $owner = null,
    array $config = []
  ) {
    $this->_linkType = $linkType;
    $attributes = $this->attributes();

    parent::__construct($field, $owner, array_filter(
      $config,
      function ($key) use ($attributes) {
        return in_array($key, $attributes);
      },
      ARRAY_FILTER_USE_KEY
    ));
  }

  /**
   * @inheritDoc
   */
  public function attributes(): array {
    return [
      'ariaLabel',
      'customText',
      'target',
      'title',
    ];
  }

  /**
   * @return bool
   */
  public function getAllowCustomText(): bool {
    return $this->_field->allowCustomText;
  }

  /**
   * @return bool
   */
  public function getAllowTarget(): bool {
    return $this->_field->allowTarget;
  }

  /**
   * @return null|string
   */
  public function getAriaLabel(): ?string {
    return $this->ariaLabel;
  }

  /**
   * Try to use provided custom text or field default.
   * Allows user to specify a fallback string if the custom text and default are not set.
   *
   * @param string $fallbackText
   * @return string|null
   * @noinspection PhpUnused (Public API)
   */
  public function getCustomText(string $fallbackText = ''): ?string {
    if ($this->getAllowCustomText() && !empty($this->customText)) {
      return $this->customText;
    }

    $defaultText = $this->getDefaultText();
    return empty($defaultText)
      ? $fallbackText
      : Craft::t('site', $defaultText);
  }

  /**
   * @return string
   */
  public function getDefaultText(): string {
    return $this->_field->defaultText;
  }

  /**
   * @param bool $ignoreStatus
   * @return null|ElementInterface
   */
  public function getElement(bool $ignoreStatus = false): ?ElementInterface {
    return null;
  }

  /**
   * @return bool
   * @noinspection PhpUnused (API)
   */
  public function getEnableAriaLabel(): bool {
    return $this->_field->enableAriaLabel;
  }

  /**
   * @return bool
   */
  public function getEnableTitle(): bool {
    return $this->_field->enableTitle;
  }

  /**
   * @return string
   */
  public function getIntrinsicText(): string {
    return '';
  }

  /**
   * @return string|null
   */
  public function getIntrinsicUrl(): ?string {
    return null;
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
  public function getLink(array|string|null $attributesOrText = null): ?Markup {
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
   * @noinspection PhpUnused (Public API)
   */
  public function getLinkAttributes(array $extraAttributes = null): Markup {
    $attributes = $this->getRawLinkAttributes($extraAttributes);
    return Template::raw(is_null($attributes)
      ? ''
      : Html::renderTagAttributes($attributes)
    );
  }

  /**
   * @return LinkType|null
   */
  public function getLinkType(): ?LinkType {
    return $this->_linkType;
  }

  /**
   * @return Site
   * @throws SiteNotFoundException
   */
  public function getOwnerSite(): Site {
    if ($this->_owner instanceof Element) {
      try {
        return $this->_owner->getSite();
      } catch (Exception) {
        // Ignore
      }
    }

    return Craft::$app->getSites()->getCurrentSite();
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
    $href = ArrayHelper::get($extraAttributes, 'href');
    $url = $this->getUrl(is_array($href) ? $href : null);
    if (is_null($url)) {
      return null;
    }

    $attributes = ['href' => is_string($href) ? $href : $url];
    $ariaLabel = $this->getAriaLabel();
    if (!empty($ariaLabel)) {
      $attributes['aria-label'] = $ariaLabel;
    }

    $target = $this->getTarget();
    if (!empty($target)) {
      $attributes['target'] = $target;

      if ($target === '_blank' && $this->_field->autoNoReferrer) {
        $attributes['rel'] = 'noopener noreferrer';
      }
    }

    $title = $this->getTitle();
    if (!empty($title)) {
      $attributes['title'] = $title;
    }

    if (is_array($extraAttributes)) {
      unset($extraAttributes['href']);
      $attributes = array_merge($attributes, $extraAttributes);
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
   * @param string $fallbackText
   * @return string
   */
  public function getText(string $fallbackText = "Learn More"): string {
    if ($this->getAllowCustomText() && !empty($this->customText)) {
      return $this->customText;
    }

    $text = $this->getIntrinsicText();
    if (!empty($text)) {
      return $text;
    }

    $text = $this->getDefaultText();
    return empty($text)
      ? $fallbackText
      : Craft::t('site', $text);
  }

  /**
   * @return null|string
   */
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * @return string
   */
  public function getType(): string {
    return $this->_linkType->name;
  }

  /**
   * @param array|null $options
   * @return string|null
   */
  public function getUrl(array $options = null): ?string {
    $url = $this->getIntrinsicUrl();
    if (!is_null($url) && !is_null($options) && count($options)) {
      $url = Url::modify($url, $options);
    }

    return $url;
  }

  /**
   * @param bool $ignoreStatus
   * @return bool
   */
  public function hasElement(bool $ignoreStatus = false): bool {
    return false;
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    return true;
  }

  /**
   * @return bool
   */
  public function isEditorEmpty(): bool {
    return $this->isEmpty();
  }

  /**
   * @param ElementInterface|null $value
   */
  public function setOwner(ElementInterface $value = null) {
    $this->_owner = $value;
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    $rules = [
      [['ariaLabel', 'target', 'title'], 'string'],
    ];

    if ($this->_field->customTextRequired && !$this->isEmpty()) {
      $rules[] = ['customText', 'required'];
    }

    $maxLength = $this->_field->customTextMaxLength;
    if ($maxLength > 0) {
      $rules[] = ['customText', 'string', 'max' => $maxLength];
    } else {
      $rules[] = ['customText', 'string'];
    }

    return array_merge(parent::rules(), $rules);
  }

  /**
   * @return string
   */
  public function __toString() {
    $url = $this->getUrl();
    return is_null($url) ? '' : $url;
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function getSerializedData() : array {
    return array_merge(parent::getSerializedData(), [
      '_linkType' => $this->_linkType->name,
    ]);
  }

  /**
   * @inheritDoc
   */
  protected function setSerializedData(array $data) {
    parent::setSerializedData($data);

    // This should not happen, but it was reported in #225
    if (!isset($this->_field)) {
      return;
    }

    $this->_linkType = $this->_field->getEnabledLinkTypes()->getByName(
      ArrayHelper::get($data, '_linkType')
    );
  }
}
