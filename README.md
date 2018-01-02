# Link field plugin for Craft

This plugin adds a new link field type to the Craft CMS. The link field allows content editors to choose from
a list of link types and offers individual input fields for each of them.

## Requirements

This plugin requires Craft CMS 3.0.0-RC1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require sebastianlenz/linkfield

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Link Field.

## Templating

Link fields on your models will return an instance of `linkfield\models\Link`. Rendering a link
field directly within a template will return the url the field is pointing to.

```
<a href="{{ item.myLinkField }}">Link</a>
```

You can use the following accessors to get the different properties of the link:

```
{{ item.myLinkField.getElement() }}
{{ item.myLinkField.getTarget() }}
{{ item.myLinkField.getText() }}
{{ item.myLinkField.getUrl() }}
{{ item.myLinkField.hasElement() }}
{{ item.myLinkField.isEmpty() }}
```

Use the `getLink` utility function to render a full html link:

```
{{ item.myLinkField.getLink() }}
```


## API

You can register additional link types by registering them via the `addLinkType` function of the plugin. If you just 
want to add another element type, you can do it like this:

```php
$plugin = \linkfield\Plugin::getInstance();
$linkType = new \linkfield\models\ElementLinkType('user');
$plugin->addLinkType('user', $linkType);
```


Each link type must have an unique name and a definition object implementing `linkfield\modles\LinkTypeInterface`. 
Take a look at the bundled link types `ElementLinkType` and `InputLinkType` to get an idea of how to write your own 
link type definitions.

## Notice

This plugin is loosely based upon [FruitLinkIt plugin for Craft CMS](https://github.com/fruitstudios/LinkIt).
