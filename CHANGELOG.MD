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
