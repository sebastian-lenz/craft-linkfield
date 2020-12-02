<?php

namespace lenz\linkfield\models;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\models\Site;
use Exception;
use lenz\craft\utils\foreignField\ForeignFieldModel;
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
  public $ariaLabel;

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
   * @var LinkType
   */
  protected $_linkType;


  /**
   * Link constructor.
   *
   * @param LinkField $field
   * @param LinkType $linkType
   * @param ElementInterface $owner
   * @param array $config
   */
  public function __construct(
    LinkField $field,
    LinkType $linkType,
    ElementInterface $owner = null,
    $config = []
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
  public function attributes() {
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
    return is_null($this->_field)
      ? false
      : $this->_field->allowCustomText;
  }

  /**
   * @return bool
   */
  public function getAllowTarget(): bool {
    return is_null($this->_field)
      ? false
      : $this->_field->allowTarget;
  }

  /**
   * @return null|string
   */
  public function getAriaLabel() {
    return $this->ariaLabel;
  }

  /**
   * Try to use provided custom text or field default.
   * Allows user to specify a fallback string if the custom text and default are not set.
   *
   * @param string $fallbackText
   * @return string
   */
  public function getCustomText($fallbackText = "") {
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
  public function getDefaultText() {
    return $this->_field->defaultText;
  }

  /**
   * @param bool $ignoreStatus
   * @return null|ElementInterface
   * @noinspection PhpUnusedParameterInspection (is API method)
   */
  public function getElement($ignoreStatus = false) {
    return null;
  }

  /**
   * @return bool
   */
  public function getEnableAriaLabel() {
    return is_null($this->_field)
      ? false
      : $this->_field->enableAriaLabel;
  }

  /**
   * @return bool
   */
  public function getEnableTitle() {
    return is_null($this->_field)
      ? false
      : $this->_field->enableTitle;
  }

  /**
   * @return string
   */
  public function getIntrinsicText() {
    return '';
  }

  /**
   * @return string|null
   */
  public function getIntrinsicUrl() {
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
   * @return Markup
   */
  public function getLinkAttributes($extraAttributes = null) {
    $attributes = $this->getRawLinkAttributes($extraAttributes);
    return Template::raw(is_null($attributes)
      ? ''
      : Html::renderTagAttributes($attributes)
    );
  }

  /**
   * @return LinkType|null
   */
  public function getLinkType() {
    return $this->_linkType;
  }

  /**
   * @return Site
   */
  public function getOwnerSite() {
    if ($this->_owner instanceof Element) {
      try {
        return $this->_owner->getSite();
      } catch (Exception $e) { }
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
  public function getRawLinkAttributes(array $extraAttributes = null) {
    $href = ArrayHelper::getValue($extraAttributes, 'href');
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
  public function getTarget() {
    return $this->getAllowTarget() && !empty($this->target)
      ? $this->target
      : null;
  }

  /**
   * @param string $fallbackText
   * @return string
   */
  public function getText($fallbackText = "Learn More") {
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
  public function getTitle() {
    return $this->title;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->_linkType->name;
  }

  /**
   * @param array|null $options
   * @return string|null
   */
  public function getUrl(array $options = null) {
    $url = $this->getIntrinsicUrl();
    if (!is_null($url) && !is_null($options) && count($options)) {
      $url = Url::modify($url, $options);
    }

    return $url;
  }

  /**
   * @param bool $ignoreStatus
   * @return bool
   * @noinspection PhpUnusedParameterInspection
   */
  public function hasElement($ignoreStatus = false) {
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
  public function rules() {
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

    $this->_linkType = $this->_field->getEnabledLinkTypes()->getByName(
      ArrayHelper::getValue($data, '_linkType')
    );
  }
}
