# Release Notes for Typed Link Field

## 2.1.5 - 2022-11-29
- Fix: Escape SQL query in Migration (see: https://github.com/sebastian-lenz/craft-linkfield/pull/245)

## 2.1.4 - 2022-08-18
- Fix: Fixed a migration error that could occur when updating to Craft 4. (see https://github.com/sebastian-lenz/craft-linkfield/issues/230)
- Fix: Respect the default link type setting in matrix fields (see https://github.com/sebastian-lenz/craft-linkfield/issues/221)
- Fix: Element query executed before Craft is fully initialized (see https://github.com/sebastian-lenz/craft-linkfield/issues/231)
- Fix: Asset source selections are not working as expected (see https://github.com/sebastian-lenz/craft-linkfield/issues/229)
- Update: Dutch translation (see https://github.com/sebastian-lenz/craft-linkfield/pull/224)

## 2.1.3-rc - 2022-04-09
- Fix: Do not use the empty link type for fields on elements that are being created right now, see https://github.com/sebastian-lenz/craft-linkfield/issues/220
- Fix: Adjust link default values, they can cause db errors, see https://github.com/sebastian-lenz/craft-linkfield/issues/219

## 2.1.2-rc - 2022-04-08
- New: Support for 4 byte characters, see https://github.com/sebastian-lenz/craft-linkfield/issues/211
- Fix: Do not try to write all migration rows at once, see https://github.com/sebastian-lenz/craft-linkfield/issues/213
- Fix: Make sure entry titles are a string and not null, see https://github.com/sebastian-lenz/craft-linkfield/issues/212
- Fix: Eager loading might throw an error for certain queries, see https://github.com/sebastian-lenz/craft-linkfield/issues/214
- Fix: Do not use `enabledForSite`, removed in Craft 4 (see https://github.com/sebastian-lenz/craft-linkfield/pull/218)

## 2.1.1-rc - 2022-04-21
- New: Add PHP 8 type annotations
- Fix: Migration error due to the getFields() method, changed to getCustomFields() (see https://github.com/sebastian-lenz/craft-linkfield/pull/210)

## 2.1.0-rc - 2022-03-28
- New: Craft 4 and PHP 8 support, removed support for Craft 3.x
- Fix: Set the direction of url input fields to ltr, see https://github.com/sebastian-lenz/craft-linkfield/issues/171

## 2.0.0-rc.2 - 2022-02-26
- Fix: Modifying mailto: links creates broken links, see https://github.com/sebastian-lenz/craft-linkfield/issues/151
- Fix: Migration fails due to field suffixes introduced in Craft 3.7, see https://github.com/sebastian-lenz/craft-linkfield/issues/164
- Fix: Link cache fails for disabled sites, see https://github.com/sebastian-lenz/craft-linkfield/issues/164

## 2.0.0-rc.1 - 2021-03-06
- New: Add support for PHP 8.0
- New: Asset sources are now stored using their volume ids, see https://github.com/sebastian-lenz/craft-linkfield/issues/144
- New: FeedMe support
- Fix: Custom fragments don't work, see https://github.com/sebastian-lenz/craft-linkfield/issues/142

## 2.0.0-beta.12 - 2021-01-28
- Fix: Migration of matrix fields failed due to missing prefix, see https://github.com/sebastian-lenz/craft-linkfield/pull/134

## 2.0.0-beta.11 - 2020-12-02
- New: Urls can now be modified by passing the desired options to `Link::getUrl()` or with the key `href` when using `Link::getLink()`, see https://github.com/sebastian-lenz/craft-linkfield/issues/120#issuecomment-737531635
- New: The return type now correctly returns the link model class. This enables code completion support
- New: The field input display has been improved for narrow forms
- New: The custom text can now be required and a maximum text length can be specified
- Fix: Correctly check the enabled state of supported third party plugins, see https://github.com/sebastian-lenz/craft-linkfield/issues/117
- Fix: Site links not resolving environment variables correctly, see https://github.com/sebastian-lenz/craft-linkfield/issues/128

## 2.0.0-beta.10 - 2020-08-12
- Update: Make the link type setting apply logic less error prone
- Fix: Fix selected state of the link type dropdown for Craft 3.5, see https://github.com/sebastian-lenz/craft-linkfield/issues/113
- Fix: Remove deprecated call to `ElementQuery::enabledForSite`, see https://github.com/sebastian-lenz/craft-linkfield/issues/114
- Fix: Element select button is being cut off

## 2.0.0-beta.9 - 2020-06-09
- Fix: Integrity constraint violation when migrating existing project to v2, see https://github.com/sebastian-lenz/craft-linkfield/issues/105
- Fix: `enableAllLinkTypes` is always true when migrating existing project to v2, see https://github.com/sebastian-lenz/craft-linkfield/issues/106

## 2.0.0-beta.8 - 2020-06-06
- Added: Norwegian and Spanish translations

## 2.0.0-beta.7 - 2020-01-30
- New: Field now displays the option "No link" if empty, see https://github.com/sebastian-lenz/craft-linkfield/issues/63
- Changed: Adjusted the default enabled states of link types, see https://github.com/sebastian-lenz/craft-linkfield/issues/76
- Changed: `Link::getCustomText` now returns an empty text by default, see https://github.com/sebastian-lenz/craft-linkfield/pull/73
- Fixed: GraphQL issue where Craft sometimes did not register the link type correctly

## 2.0.0-beta.6 - 2019-08-30
- Added: Basic GraphQL support
- Changed: Remove CraftQL support
- Fixed: An error due to empty handles on field settings, see #74

## 2.0.0-beta.5 - 2019-06-27
- Changed: Use foreign field query extension instead of custom eager loading

## 2.0.0-beta.4 - 2019-06-27
- Changed: Update test cases, plugin must now be loaded as such, Craft no longer accepts a module

## 2.0.0-beta.3 - 2019-04-29
- Fixed: An issue when another plugin tries to access a link field before the plugin has been loaded (see https://github.com/sebastian-lenz/craft-linkfield/issues/61)

## 2.0.0-beta.2 - 2019-04-29
- Fixed: Merge hotfix from v1.0.19

## 2.0.0-beta.1 - 2019-04-28
- New: Store all links in a dedicated database table
- New: Eagerloading of linked elements
- New: Cache for linked element urls and titles
- New: Internal links to different sites (see https://github.com/sebastian-lenz/craft-linkfield/issues/44)
- Changed: The php namespace of the plugin has been changed from `typedlinkfield` to `lenz\linkfield`
- Changed: Link types now use a common base class `lenz\linkfield\models\LinkType`
- Changed: Link values now create different models based on the chosen link type

> {warning} This is a major update, please create a database backup before installing, please see https://github.com/sebastian-lenz/craft-linkfield/issues/61

## 1.0.25 - 2021-02-23
- New: Add support for PHP 8.0

## 1.0.24 - 2020-21-07
- Fix detection of supported third party plugins
- Fix site links not resolving environment variables correctly

## 1.0.23 - 2020-06-09
- Fix custom query storage (see https://github.com/sebastian-lenz/craft-linkfield/issues/108)
- Fix display of long element titles in combination with custom queries

## 1.0.22 - 2020-06-06
- Fix display of long element titles
- Update Norwegian translations

## 1.0.21 - 2020-04-01
- Add Dutch translations

## 1.0.20 - 2020-02-07
- Fix error triggered when no owner element is passed when rendering fields
- Fix typo of attribute "aria-label"

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
