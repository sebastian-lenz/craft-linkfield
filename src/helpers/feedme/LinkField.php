<?php

namespace lenz\linkfield\helpers\feedme;

use Cake\Utility\Hash;
use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;

/**
 * @property \lenz\linkfield\fields\LinkField $field
 * @property-read string $mappingTemplate
 */
class LinkField extends Field implements FieldInterface
{
  /**
   * @var string
   */
  public static $name = 'LinkField';

  /**
   * @var string
   */
  public static $class = \lenz\linkfield\fields\LinkField::class;


  /**
   * @inheritDoc
   */
  public function getMappingTemplate(): string {
    return 'typedlinkfield/_feedme';
  }

  /**
   * @inheritDoc
   */
  public function parseField(): ?Link {
    $linkType = $this->parseLinkType();
    if (empty($linkType)) {
      return null;
    }

    $link = $linkType->createLink($this->field, $this->element);
    return $this->parseLinkAttributes($link);
  }


  // Private methods
  // ---------------

  /**
   * @return LinkType|null
   */
  private function parseLinkType(): LinkType|null {
    $typeField = Hash::get($this->fieldInfo, ['fields', 'type']);
    if (empty($typeField)) {
      return null;
    }

    $type = DataHelper::fetchValue($this->feedData, $typeField);
    if (empty($type)) {
      return null;
    }

    return $this->field
      ->getEnabledLinkTypes()
      ->getByName($type);
  }

  /**
   * @param Link $link
   * @return Link
   */
  private function parseLinkAttributes(Link $link): Link {
    $allowed = $link->attributes();
    $fields = Hash::get($this->fieldInfo, 'fields');

    foreach ($fields as $handle => $fieldInfo) {
      if (in_array($handle, $allowed)) {
        $link->$handle = DataHelper::fetchValue($this->feedData, $fieldInfo);
      }
    }

    return $link;
  }
}
