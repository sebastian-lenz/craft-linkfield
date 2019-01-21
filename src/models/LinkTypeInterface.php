<?php

namespace typedlinkfield\models;

use craft\base\ElementInterface;
use typedlinkfield\fields\LinkField;

/**
 * Interface LinkTypeInterface
 * @package typedlinkfield\models
 */
interface LinkTypeInterface
{
  /**
   * @return array
   */
  public function getDefaultSettings(): array;

  /**
   * @return string
   */
  public function getDisplayName(): string;

  /**
   * @return string
   */
  public function getDisplayGroup(): string;

  /**
   * @param Link $link
   * @param bool $ignoreStatus
   * @return null|ElementInterface
   */
  public function getElement(Link $link, $ignoreStatus = false);

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @param Link $value
   * @param ElementInterface $element
   * @return string
   */
  public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element): string;

  /**
   * @param mixed $value
   * @return mixed
   */
  public function getLinkValue($value);

  /**
   * @param string $linkTypeName
   * @param LinkField $field
   * @return string
   */
  public function getSettingsHtml(string $linkTypeName, LinkField $field): string;

  /**
   * @param Link $link
   * @return null|string
   */
  public function getText(Link $link);

  /**
   * @param Link $link
   * @return null|string
   */
  public function getUrl(Link $link);

  /**
   * @param Link $link
   * @param bool $ignoreStatus
   * @return bool
   */
  public function hasElement(Link $link, $ignoreStatus = false): bool;

  /**
   * @param Link $link
   * @return bool
   */
  public function isEmpty(Link $link): bool;

  /**
   * @param array $settings
   * @return array
   */
  public function validateSettings(array $settings): array;

  /**
   * @param LinkField $field
   * @param Link $link
   * @return array|null
   */
  public function validateValue(LinkField $field, Link $link);
}
