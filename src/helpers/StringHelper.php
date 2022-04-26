<?php

namespace lenz\linkfield\helpers;

/**
 * Class StringHelper
 */
class StringHelper extends \craft\helpers\StringHelper
{
  /**
   * @param string $str
   * @return string
   */
  static public function decodeMb4(string $str): string {
    return preg_replace_callback('/&#x[0-9A-Fa-f]+;/', fn($match) => self::htmlDecode($match[0]), $str);
  }
}
