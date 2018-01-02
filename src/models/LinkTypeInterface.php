<?php

namespace linkfield\models;

use craft\base\ElementInterface;
use linkfield\fields\LinkField;

/**
 * Interface LinkTypeInterface
 * @package linkfield\models
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
   * @param Link $link
   * @return null|ElementInterface
   */
  public function getElement(Link $link);

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
   * @return bool
   */
  public function hasElement(Link $link): bool;

  /**
   * @param Link $link
   * @return bool
   */
  public function isEmpty(Link $link): bool;

  /**
   * @param Link $link
   * @return array|null
   */
  public function validateValue(Link $link);
}
