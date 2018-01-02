<?php

namespace linkfield\validators;

use linkfield\models\Link;
use yii\validators\Validator;

/**
 * Class LinkFieldValidator
 * @package linkfield
 */
class LinkFieldValidator extends Validator
{
  /**
   * @param mixed $value
   * @return array|null
   */
  protected function validateValue($value) {
    if ($value instanceof Link) {
      $linkType = $value->getLinkType();

      if (!is_null($linkType)) {
        return $linkType->validateValue($value);
      }
    }

    return null;
  }
}
