## 1.0.16
- Allow custom queries on element links
- Add option to automatically add `rel="noopener noreferrer"` to links opening in a new window

## 1.0.15
- Fix issues when loading models with unexpected model attributes.
- Add method `getLinkAttributes` to model, return rendered link attributes. 

## 1.0.14
- Fix exception when trying to revert to revisions saved with 1.0.12 or before.
- Model methods `getElement` and `hasElement` accept new parameter `ignoreStatus`.

## 1.0.13
- New link type "Site"
- Support for aliases on all input link types

## 1.0.12
- New `aria-label` and `title` fields. Can be turned on in field settings. Will render as attributes in `getLink()` and can be queried using `getAriaLabel()` and `getTitle()`.
- New more compact field display
- GraphQL: Add support for categories 
- GraphQL: New fields `link` and `text`
- GraphQL: Deprecation: The fields `allowCustomText` and `allowTarget` are deprecated as there seems to be no use case for them.
 
## 1.0.11
- New default link type `custom` without any validation, e.g. for relative links
- New getCustomText() method on link model

## 1.0.10
- Fix an issue when creating new fields, see https://github.com/sebastian-lenz/craft-linkfield/pull/22

## 1.0.9
- Add new administration interface.
- Add experimental GraphQL query support.
- Add Craft Commerce product and Solspace Calendar event support.
- Remove global set element type.

## 1.0.8
- Add event `registerLinkTypes`. Use this event to register custom link types.
- Add global set element type.

## 1.0.7
- Fix: Field owner site not respected when resolving urls.

## 1.0.6
- Fix: Change in Craft API, rename field method `isEmpty` to `isValueEmpty`.
- Add plugin icon.

## 1.0.5
- Update field styling, add a border to group link fields.
- Add option to pass additional options to `Link::getLink`.

## 1.0.4
- Fix: Site id not set correctly when showing element select ui.
- Fix: Change field schema to `TEXT`.
- Add option to set default link type.
- Add option to disable validation on url, mail and phone field types.

## 1.0.3
- Fix: add missing `Link::getElement` method.

## 1.0.2
- Fix: Link field does not render correctly if only one link type is enabled.
- Fix: Link type change does not update the select ui.

## 1.0.1
- Change plugin handle, old handle was already in use.

## 1.0.0
- Initial release.
