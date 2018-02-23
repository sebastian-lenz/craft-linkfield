<?php

namespace typedlinkfield\validators;

use typedlinkfield\fields\LinkField;
use typedlinkfield\models\Link;
use yii\validators\Validator;

/**
 * Class LinkFieldValidator
 * @package typedlinkfield
 */
class LinkFieldValidator extends Validator
{
  /**
   * @var LinkField
   */
  public $field;

  /**
   * @param mixed $value
   * @return array|null
   */
  protected function validateValue($value) {
    if ($value instanceof Link) {
      $linkType = $value->getLinkType();

      if (!is_null($linkType)) {
        return $linkType->validateValue($this->field, $value);
      }
    }

    return null;
  }
}
