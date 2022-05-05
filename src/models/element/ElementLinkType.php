<?php

namespace lenz\linkfield\models\element;

use Craft;
use craft\base\ElementInterface;
use craft\errors\SiteNotFoundException;
use lenz\linkfield\fields\LinkField;
use lenz\linkfield\listeners\ElementListener;
use lenz\linkfield\models\Link;
use lenz\linkfield\models\LinkType;

/**
 * Class ElementLinkType
 */
class ElementLinkType extends LinkType
{
  /**
   * @var bool
   */
  public bool $allowCrossSiteLink = false;

  /**
   * @var bool
   */
  public bool $allowCustomQuery = false;

  /**
   * @var class-string<ElementInterface>
   */
  public string $elementType;

  /**
   * @var string|string[]
   */
  public string|array $sources = '*';

  /**
   * @inheritDoc
   */
  const MODEL_CLASS = ElementLink::class;


  /**
   * @inheritDoc
   */
  public function createLink(LinkField $field, ElementInterface $owner = null, array $value = []): Link {
    $link = parent::createLink($field, $owner, $value);

    // If the link is created from post data, we mus update cached element data
    // immediately as the validation might fail otherwise
    // @see https://github.com/sebastian-lenz/craft-linkfield/issues/126
    if (isset($value['cpForm']) && $field->enableElementCache) {
      $link->setAttributes($this->getCachedElementAttributes($link));
    }

    return $link;
  }

  /**
   * @return array
   */
  public function getAvailableSources(): array {
    $elementType = $this->elementType;
    $options = array();

    foreach ($elementType::sources('settings') as $source) {
      if (array_key_exists('key', $source) && $source['key'] !== '*') {
        $options[$source['key']] = $source['label'];
      }
    }

    return $options;
  }

  /**
   * @inheritDoc
   */
  public function getDisplayName(): string {
    return $this->elementType::displayName();
  }

  /**
   * @inheritDoc
   */
  public function getInputHtml(Link $value, bool $disabled): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_input-element',
      [
        'disabled'     => $disabled,
        'elementField' => $this->getElementField($value),
        'linkType'     => $this,
        'queryField'   => $this->getQueryField($value),
        'siteField'    => $this->getSiteField($value),
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function getSettingsHtml(LinkField $field): string {
    return Craft::$app->view->renderTemplate(
      'typedlinkfield/_settings-element',
      [
        'linkType' => $this,
        'sources' => $this->sources,
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function rules(): array {
    return array_merge(parent::rules(), [
      ['allowCustomQuery', 'boolean'],
      ['allowCrossSiteLink', 'boolean'],
      ['elementType', 'validateElementType'],
      ['sources', 'validateSources'],
    ]);
  }

  /**
   * @inheritDoc
   */
  public function settingsAttributes(): array {
    return array_merge(parent::settingsAttributes(), [
      'allowCustomQuery',
      'allowCrossSiteLink',
      'sources',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function toRecordAttributes(Link $model): array {
    $attributes = parent::toRecordAttributes($model);

    if (
      $model->getField()->enableElementCache &&
      $model instanceof ElementLink
    ) {
      $attributes += $this->getCachedElementAttributes($model);
    }

    return $attributes;
  }

  /**
   * @return void
   * @noinspection PhpUnused (Validator)
   */
  public function validateElementType() {
    if (!is_subclass_of($this->elementType, ElementInterface::class)) {
      $this->addError('elementType', 'Element type must be a class implementing ElementInterface');
    }
  }

  /**
   * @return void
   * @noinspection PhpUnused (Validator)
   */
  public function validateSources(): void {
    $validSources = $this->getValidSources();
    $sources = $this->sources;
    if (!is_array($sources)) {
      $sources = [$sources];
    }

    $resolvedResources = [];
    foreach ($sources as $source) {
      if ($source == '*') {
        $this->sources = '*';
        return;
      }

      if (in_array($source, $validSources)) {
        $resolvedResources[] = $source;
      }
    }

    $this->sources = $resolvedResources;
  }


  // Protected methods
  // -----------------

  /**
   * @param Link $model
   * @return array
   */
  protected function getCachedElementAttributes(Link $model): array {
    $element = $model->getElement();

    if ($element && ElementListener::isElementPublished($element)) {
      return [
        'linkedTitle' => (string)$element,
        'linkedUrl' => $element->getUrl(),
      ];
    }

    return [
      'linkedTitle' => null,
      'linkedUrl' => null,
    ];
  }

  /**
   * @param Link $value
   * @return array
   * @throws SiteNotFoundException
   */
  protected function getElementField(Link $value): array {
    if ($this->isSelected($value) && $value instanceof ElementLink) {
      $linkedElements = array_filter([ $value->getElement() ]);
      $linkedSiteId   = $value->getSiteId();
    } else {
      $linkedElements = null;
      $linkedSiteId   = $value->getOwnerSite()->id;
    }

    $criteria = [
      'status'         => null,
    ];

    if (!$this->allowCrossSiteLink) {
      $criteria['siteId'] = $linkedSiteId;
    }

    $fieldHandle = $value->getField()->handle;
    $typeHandle = $this->name;

    return [
      'criteria'        => $criteria,
      'elementType'     => $this->elementType,
      'elements'        => $linkedElements,
      'id'              => 'linkedId',
      'limit'           => 1,
      'name'            => 'linkedId',
      'showSiteMenu'    => $this->allowCrossSiteLink,
      'sources'         => $this->getEnabledSources(),
      'storageKey'      => "linkfield.$fieldHandle.$typeHandle",
    ];
  }

  /**
   * @return array|string|null
   */
  protected function getEnabledSources(): array|string|null {
    return $this->sources === '*' ? null : $this->sources;
  }

  /**
   * @param Link $value
   * @return array|null
   */
  protected function getQueryField(Link $value): ?array {
    if (!$this->allowCustomQuery) {
      return null;
    }

    $queryValue = '';
    if ($value instanceof ElementLink && !empty($value->customQuery)) {
      $queryValue = $value->customQuery;
    }

    return [
      'id'    => 'customQuery',
      'name'  => 'customQuery',
      'value' => $queryValue,
      'placeholder' => Craft::t('typedlinkfield', 'Query, starts with "#" or "?"'),
    ];
  }

  /**
   * @param Link $value
   * @return array|null
   * @throws SiteNotFoundException
   */
  protected function getSiteField(Link $value): ?array {
    if (!$this->allowCrossSiteLink) {
      return null;
    }

    if ($value instanceof ElementLink) {
      $siteId = $value->getSiteId();
    } else {
      $siteId = $value->getOwnerSite()->id;
    }

    return [
      'id'    => 'linkedSiteId',
      'name'  => 'linkedSiteId',
      'value' => $siteId,
    ];
  }

  /**
   * @return array
   */
  protected function getValidSources(): array {
    return array_keys($this->getAvailableSources());
  }

  /**
   * @inheritDoc
   */
  protected function prepareFormData(array $data): array {
    if (isset($data['linkedId']) && is_array($data['linkedId'])) {
      $data['linkedId'] = reset($data['linkedId']);
    }

    return $data;
  }

  /**
   * @inheritDoc
   */
  protected function prepareLegacyData($data): ?array {
    if (!is_numeric($data)) {
      return null;
    }

    return [
      'linkedId' => $data
    ];
  }
}
