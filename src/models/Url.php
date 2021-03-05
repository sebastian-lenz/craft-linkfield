<?php

namespace lenz\linkfield\models;

use lenz\craft\utils\helpers\ArrayHelper;
use lenz\craft\utils\models\Url as BaseUrl;

/**
 * Class Url
 */
class Url extends BaseUrl
{
  /**
   * @param string $value
   * @param array $options
   * @return string
   */
  public static function modify(string $value, array $options): string {
    $url = new Url($value);

    foreach ($url->attributes() as $attribute) {
      $option = ArrayHelper::get($options, $attribute);

      if (is_string($option)) {
        $url->$attribute = $option;
      } elseif (is_array($option) && $attribute == 'query') {
        if (ArrayHelper::get($options, 'queryMode') != 'replace') {
          $option = array_merge($url->getQuery(), $option);
        }

        $url->setQuery($option);
      }
    }

    return (string)$url;
  }
}
