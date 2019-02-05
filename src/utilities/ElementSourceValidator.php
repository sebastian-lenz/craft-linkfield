<?php

namespace typedlinkfield\utilities;

use craft\base\ElementInterface;
use yii\helpers\ArrayHelper;

/**
 * Class ElementSourceValidator
 */
class ElementSourceValidator
{
  /**
   * @var array
   */
  private $availableSources;

  /**
   * @var ElementSourceValidator[]
   */
  private static $validators = array();


  /**
   * ElementSourceValidator constructor.
   * @param ElementInterface $elementType
   * @throws \Exception
   */
  public function __construct($elementType) {
    $idPath = self::getElementIdPath($elementType);
    if (is_null($idPath)) {
      throw new \Exception('Unsupported element type: ' . (string)$elementType);
    }

    $availableSources = array();
    foreach ($elementType::sources() as $source) {
      if (!array_key_exists('key', $source)) {
        continue;
      }

      $id = ArrayHelper::getValue($source, $idPath);
      if (is_null($id)) {
        continue;
      }

      $availableSources[] = array(
        'key' => $source['key'],
        'id'  => $id,
      );
    }

    $this->availableSources = $availableSources;
  }

  /**
   * @param array $originalSources
   * @return array
   */
  public function validate($originalSources) {
    $resolvedSources = array();

    foreach ($originalSources as $originalSource) {
      $resolvedSource = $this->validateSource($originalSource);
      if (!is_null($resolvedSource)) {
        $resolvedSources[] = $resolvedSource;
      }
    }

    return $resolvedSources;
  }

  /**
   * @param string $originalSource
   * @return null|string
   */
  private function validateSource($originalSource) {
    $maybeSource = null;

    // Fetch id from source. If we don't find one, this is not referring
    // to an actual source (e.g. `*`) so leave it untouched.
    $originalId = self::getIdFromSource($originalSource);
    if (is_null($originalId)) {
      return $originalSource;
    }

    // Check all sources
    foreach ($this->availableSources as $availableSource) {
      // Perfect key match, just resolve
      if ($availableSource['key'] == $originalSource) {
        return $originalSource;
      }

      // Check for section id match
      if ($availableSource['id'] == $originalId) {
        $maybeSource = $availableSource;
      }
    }

    // Did not find a perfect match, return the maybe hit
    return is_null($maybeSource)
      ? null
      : $maybeSource['key'];
  }

  /**
   * @param ElementInterface $elementType
   * @param array $sources
   * @return array
   */
  public static function apply($elementType, $sources) {
    try {
      if (!array_key_exists($elementType, self::$validators)) {
        self::$validators[(string)$elementType] = new ElementSourceValidator($elementType);
      }
      return self::$validators[(string)$elementType]->validate($sources);
    } catch (\Throwable $e) { }

    return $sources;
  }

  /**
   * @param ElementInterface $elementType
   * @return array|null
   */
  public static function getElementIdPath($elementType) {
    switch ($elementType) {
      case 'craft\\elements\\Asset':
        return array('criteria', 'folderId');
      case 'craft\\elements\\Category':
        return array('criteria', 'groupId');
      case 'craft\\elements\\Entry':
        return array('criteria', 'sectionId');
    }

    return null;
  }

  /**
   * @param string $originalSource
   * @return null|string
   */
  public static function getIdFromSource($originalSource) {
    $idOffset = strpos($originalSource, ':');
    if ($idOffset === false) {
      return null;
    }

    return substr($originalSource, $idOffset + 1);
  }
}
