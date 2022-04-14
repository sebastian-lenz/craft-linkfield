<?php

namespace lenz\linkfield\models;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class LinkGqlType
 */
class LinkGqlType
{
  /**
   * @return string
   */
  static public function getName(): string {
    return 'linkField_Link';
  }

  /**
   * @return Type
   */
  static public function getType(): Type {
    if ($type = GqlEntityRegistry::getEntity(self::class)) {
      return $type;
    }

    return GqlEntityRegistry::createEntity(self::class, new ObjectType([
      'name'   => static::getName(),
      'fields' => self::class . '::getFieldDefinitions',
      'description' => 'This is the interface implemented by all links.',
    ]));
  }

  /**
   * @return array
   */
  public static function getFieldDefinitions(): array {
    return [
      'ariaLabel' => [
        'name' => 'ariaLabel',
        'type' => Type::string(),
      ],
      'customText' => [
        'name' => 'customText',
        'type' => Type::string(),
      ],
      'element' => [
        'name' => 'element',
        'type' => Element::getType(),
      ],
      'target' => [
        'name' => 'target',
        'type' => Type::string(),
      ],
      'text' => [
        'name' => 'text',
        'type' => Type::string(),
      ],
      'title' => [
        'name' => 'title',
        'type' => Type::string(),
      ],
      'type' => [
        'name' => 'type',
        'type' => Type::string(),
      ],
      'url' => [
        'name' => 'url',
        'type' => Type::string(),
      ],
    ];
  }
}
