# Release Notes for Typed Link Field

## 1.0.19 - 2019-04-29
Yesterdays release unfortunately contains a change to the url validator that causes trouble, we therefore issue a hotfix. 
- Fix url validation not accepting long urls (see https://github.com/sebastian-lenz/craft-linkfield/issues/62)
- Remove element source restriction on element link fields

> {note} Big update ahead! We are working on version 2 and we would be pleased to receive your feedback, please see https://github.com/sebastian-lenz/craft-linkfield/issues/61

## 1.0.18 - 2019-04-28
- Fix an issue when loading empty field values (see https://github.com/sebastian-lenz/craft-linkfield/issues/55, https://github.com/sebastian-lenz/craft-linkfield/pull/56)
- Fix an issue causing the page preview to not work probably (https://github.com/sebastian-lenz/craft-linkfield/issues/53)
- Allow domains with non-ASCII characters (see https://github.com/sebastian-lenz/craft-linkfield/pull/57)

## 1.0.17 - 2019-02-05
- Fix: Rewriting element sources from Craft 3.0 fails (see https://github.com/sebastian-lenz/craft-linkfield/issues/50)

## 1.0.16 - 2019-01-21
- Hotfix: Try to maintain field settings in Craft 3.1 (see https://github.com/sebastian-lenz/craft-linkfield/issues/49)
- Fix non-json strings issue (see https://github.com/sebastian-lenz/craft-linkfield/pull/46)
- Fix Link::getCustomText() returning the wrong value (see https://github.com/sebastian-lenz/craft-linkfield/issues/45)
- Allow custom queries on element links
- Add option to automatically add `rel="noopener noreferrer"` to links opening in a new window

## 1.0.15 - 2018-10-23
- Fix issues when loading models with unexpected model attributes.
- Add method `getLinkAttributes` to model, return rendered link attributes. 

## 1.0.14 - 2018-09-25
- Fix exception when trying to revert to revisions saved with 1.0.12 or before.
- Model methods `getElement` and `hasElement` accept new parameter `ignoreStatus`.

## 1.0.13 - 2018-09-17
- New link type "Site"
- Support for aliases on all input link types

## 1.0.12 - 2018-08-24
- New `aria-label` and `title` fields. Can be turned on in field settings. Will render as attributes in `getLink()` and can be queried using `getAriaLabel()` and `getTitle()`.
- New more compact field display
- GraphQL: Add support for categories 
- GraphQL: New fields `link` and `text`
- GraphQL: Deprecation: The fields `allowCustomText` and `allowTarget` are deprecated as there seems to be no use case for them.
 
## 1.0.11 - 2018-07-30
- New default link type `custom` without any validation, e.g. for relative links
- New getCustomText() method on link model

## 1.0.10 - 2018-07-10
- Fix an issue when creating new fields, see https://github.com/sebastian-lenz/craft-linkfield/pull/22

## 1.0.9 - 2018-05-31
- Add new administration interface.
- Add experimental GraphQL query support.
- Add Craft Commerce product and Solspace Calendar event support.
- Remove global set element type.

## 1.0.8 - 2018-05-02
- Add event `registerLinkTypes`. Use this event to register custom link types.
- Add global set element type.

## 1.0.7 - 2018-03-26
- Fix: Field owner site not respected when resolving urls.

## 1.0.6 - 2018-03-20
- Fix: Change in Craft API, rename field method `isEmpty` to `isValueEmpty`.
- Add plugin icon.

## 1.0.5 - 2018-03-16
- Update field styling, add a border to group link fields.
- Add option to pass additional options to `Link::getLink`.

## 1.0.4 - 2018-02-23
- Fix: Site id not set correctly when showing element select ui.
- Fix: Change field schema to `TEXT`.
- Add option to set default link type.
- Add option to disable validation on url, mail and phone field types.

## 1.0.3 - 2018-01-19
- Fix: add missing `Link::getElement` method.

## 1.0.2 - 2018-01-10
- Fix: Link field does not render correctly if only one link type is enabled.
- Fix: Link type change does not update the select ui.

## 1.0.1 - 2018-01-02
- Change plugin handle, old handle was already in use.

## 1.0.0 - 2018-01-02
- Initial release.
