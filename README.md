# Link field plugin for Craft

This plugin adds a new link field type to the Craft CMS. The link field allows content editors to choose from
a list of link types and offers individual input fields for each of them.

## Requirements

This plugin requires Craft CMS 4.0.0 or later.

## Installation

The plugin can be installed from the integrated plugin store by searching for
"Typed Link Field" or using Composer:

1. Open your terminal and navigate to your Craft project:

       cd /path/to/project

2. Then tell Composer to load the plugin:

       composer require sebastianlenz/linkfield

3. Finally, install and enable the plugin:
   
       ./craft plugin/install typedlinkfield
       ./craft plugin/enable typedlinkfield

## Usage

After the plugin has been installed, link fields can be created using the field
settings within the control panel. All field settings can be found within the
field manager.


## Templating

Link fields can be rendered directly in Twig, they return the url the
link is pointing to, or an empty string if they are unset:

```twig
<a href="{{ entry.myLinkField }}">Link</a>
```

The field value is actually an instance of `lenz\linkfield\models\Link` which
exposes additional properties and methods that can be used in templates.
Depending on the link type, a more specific subclass will be returned.


### Render methods

#### getLink($attributesOrText = null)

Renders a full html link using the attribute and content data of the field.

```twig
{{ entry.myLinkField.getLink() }}
```

To modify the inner text of the link tag, the desired content can be passed:

```twig
{{ entry.myLinkField.getLink('Imprint') }}
```

To modify the attributes of the link, an object can be passed. The special
attribute `text` will be used as the inner text.

```twig
{{ entry.myLinkField.getLink({
  class: 'my-link-class',
  target: '_blank',
  text: 'Imprint',
}) }}
```


#### getLinkAttributes($extraAttributes = null)

Renders only the link attributes. Attributes can be modified or appended by 
passing an object to the first argument.

```twig
<a{{ entry.myLinkField.getLinkAttributes() }}>
  <span>Custom markup</span>
</a>
```


#### getRawLinkAttributes($extraAttributes = null)

Returns the attributes of the link as an array. Can be used in junction with the
`attr` or `tag` helpers exposed by Craft.

```twig
{% tag 'a' with entry.myLinkField.getRawLinkAttributes() %}
  <span>Custom markup</span>
{% endtag %}
```

### Helper methods

#### getAllowCustomText()

Returns whether the field allows users to enter custom texts.

```twig
{{ entry.myLinkField.getAllowCustomText() }}
```

#### getAllowTarget()

Returns whether the field shows the option for opening links in a new window.

```twig
{{ entry.myLinkField.getAllowTarget() }}
```


#### getAriaLabel()

Returns the aria label of the link.

```twig
{{ entry.myLinkField.getAriaLabel() }}
```


#### getCustomText($fallbackText = '')

Returns the custom text of the link. The first non-empty text from the following
possibilities will be picked:

1. Custom text of the link.
2. Default text defined in the field settings.
3. Fallback text as passed to the function.

```twig
{{ entry.myLinkField.getCustomText('My fallback text') }}
```


#### getDefaultText()

Returns the default text set in the link field settings of this link.

```twig
{{ entry.myLinkField.getDefaultText() }}
```


#### getElement($ignoreStatus = false)

Returns the linked element (entry, asset, etc.) or `NULL` if no element is
linked.

By default, only published elements are returned. Set `$ignoreStatus`
to `TRUE` to retrieve unpublished elements.

```twig
{{ entry.myLinkField.getElement() }}
```


#### getEnableAriaLabel()

Returns whether the field allows users to enter aria labels.

```twig
{{ entry.myLinkField.getEnableAriaLabel() }}
```


#### getEnableTitle()

Returns whether the field allows users to enter link titles.

```twig
{{ entry.myLinkField.getEnableTitle() }}
```


#### getIntrinsicText()

Returns the text that is declared by the link itself (e.g. the title of the
linked entry or asset).

```twig
{{ entry.myLinkField.getIntrinsicText() }}
```


#### getIntrinsicUrl()

Returns the url that is declared by the link itself (e.g. the url of the
linked entry or asset). Custom queries or hashes are taken into account and
will be appended to the result.

```twig
{{ entry.myLinkField.getIntrinsicUrl() }}
```


#### getTarget()

Returns the link target (e.g. `_blank`).

```twig
{{ entry.myLinkField.getTarget() }}
```


#### getText($fallbackText = "Learn More")

Returns the link text. The first non-empty text from the following
possibilities will be picked:

1. Custom text of the link.
2. Intrinsic text defined by the linked element.
3. Default text defined in the field settings.
4. Fallback text as passed to the function.

```twig
{{ entry.myLinkField.getText($fallbackText = "Learn More") }}
```


#### getType()

Returns a string that indicates the type of the link. The plugin ships with the
following link types: `asset`, `category`, `custom`, `email`, `entry`, `site`,
`tel`, `url` and `user`.

```twig
{{ entry.myLinkField.getType() }}
```


#### getUrl($options = null)

Returns the url of the link.

```twig
{{ entry.myLinkField.getUrl() }}
```

Allows the link to be modified by overwriting  url attributes as returned by the
php function `parse_url`. The following options are supported: `fragment`, 
`host`, `pass`, `path`, `port`, `query`, `scheme` and `user`.

- All options require a string value to be passed.
- The `query` option accepts an array or hash map. If an array is passed, the
  query parameters of the original url will be merged by default. To disable
  this behaviour, the option `queryMode` must be set to `replace`.

This example enforces the https scheme and replaces all query parameters
of the original url:

```twig
{{ entry.myLinkField.getUrl({
  scheme: 'https',
  queryMode: 'replace',
  query: {
    param: 'value'
  },
}) }}
```


#### hasElement($ignoreStatus = false)

Returns whether the link points to an element (e.g. entry or asset).

```twig
{{ entry.myLinkField.hasElement() }}
```


#### isEmpty()

Returns whether the link is empty. An empty link is a link that has no url.

```twig
{{ entry.myLinkField.isEmpty() }}
```


### Properties

The properties generally expose the raw underlying data of the link.


#### ariaLabel

The aria label as entered in th cp.

```twig
<a aria-label="{{ entry.myLinkField.ariaLabel }}">...</a>
```


#### customText

The custom text as entered in th cp.

```twig
<a>{{ entry.myLinkField.customText }}</a>
```


#### target

The link target as text. Can be either `_blank` or an empty string.

```twig
<a target="{{ entry.myLinkField.target }}">...</a>
```


#### title

The title as entered in th cp.

```twig
<a title="{{ entry.myLinkField.title }}">...</a>
```


## Eager-Loading

Link fields can be eager-loaded using Crafts `with` query parameter. Eager-loading
can greatly improve the performance when fetching many entries, e.g. when
rendering menus.

```twig
{% set entries = craft.entries({
  section: 'pages',
  with: 'myLinkField',
}).all() %}
```


## API

You can register additional link types by listening to the `EVENT_REGISTER_LINK_TYPES` 
event of the plugin. If you just want to add another element type, you can do it like this in
your module:

```php
use craft\commerce\elements\Product;
use lenz\linkfield\Plugin as LinkPlugin;
use lenz\linkfield\events\LinkTypeEvent;
use lenz\linkfield\models\element\ElementLinkType;
use yii\base\Event;

/**
 * Custom module class.
 */
class Module extends \yii\base\Module
{
  public function init() {
    parent::init();
    Event::on(
      LinkPlugin::class,
      LinkPlugin::EVENT_REGISTER_LINK_TYPES,
      function(LinkTypeEvent $event) {
        $event->linkTypes['product'] = new ElementLinkType(Product::class);
      }
    );
  }
}
```

Each link type must have an unique name and a definition object extending `lenz\linkfield\models\LinkType`. 
Take a look at the bundled link types `ElementLinkType` and `InputLinkType` to get an idea of how to write your own 
link type definitions.


## Remarks

### Upgrading from Fruit Link It

If wish to migrate a field created using "Fruit Link It", please follow the
discussion and directions here:

https://github.com/sebastian-lenz/craft-linkfield/issues/51#issuecomment-538782716
