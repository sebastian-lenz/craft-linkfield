<?php

namespace lenz\linkfield\fields;

use lenz\linkfield\fields\LinkField;
use lenz\linkfield\models\Link;
use yii\validators\Validator;

/**
 * Class LinkFieldValidator
 */
class LinkFieldValidator extends Validator
{
  /**
   * @param mixed $value
   * @return array|null
   */
  protected function validateValue($value) {
    if (!($value instanceof Link)) {
      return ['The given value is not a valid link.'];
    }

    return $value->validate()
      ? null
      : $value->errors;
  }
}
