# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.6.2] - 2021-06-23

### Added

- Implement UI and client handling for CSV import -  [#415](https://github.com/owncloud/customgroups/pull/415)
- feat: export and import group members as csv - [#409](https://github.com/owncloud/customgroups/issues/409)

### Fixed

- Fix wrong UI messages when changing your own group role as admin - [#445](https://github.com/owncloud/customgroups/issues/445)
- Improve UI when trying the change the role of a group admin - [#443](https://github.com/owncloud/customgroups/issues/443)
- Use group id for csv import - [#442](https://github.com/owncloud/customgroups/issues/442)
- Change absolute notification URL to a relative one - [#419](https://github.com/owncloud/customgroups/issues/419)

## [0.6.1] - 2021-03-05

### Changed

- [Security] Bump http-proxy from 1.16.2 to 1.18.1 -  [#367](https://github.com/owncloud/customgroups/issues/367)
- [Security] Bump js-yaml from 3.10.0 to 3.14.1 - [#380](https://github.com/owncloud/customgroups/issues/380)


## [0.6.0] - 2020-02-06

### Changed

- Update owncloud min-version - [#308](https://github.com/owncloud/customgroups/issues/308)

## [0.5.1] - 2020-01-28

### Fixed

- Validation for max allowed number of chars in custom groups name - [#291](https://github.com/owncloud/customgroups/issues/291)
- Allow user to add to group even when enumeration to group is imposed - [#293](https://github.com/owncloud/customgroups/issues/293)

### Changed

- [Security] Bump atob from 2.0.3 to 2.1.2 - [#228](https://github.com/owncloud/customgroups/issues/228)
- [Security] Bump bower from 1.8.2 to 1.8.8 - [#239](https://github.com/owncloud/customgroups/issues/239)
- [Security] Bump handlebars from 4.0.11 to 4.7.2 - [#281](https://github.com/owncloud/customgroups/issues/281), [#295](https://github.com/owncloud/customgroups/issues/295), [#297](https://github.com/owncloud/customgroups/issues/297), [#299](https://github.com/owncloud/customgroups/issues/299)

## [0.5.0] - 2019-12-20

### Added

- Deny admin access when system config is set - [#273](https://github.com/owncloud/customgroups/issues/273)
- Add Support for PHP 7.3 - [#226](https://github.com/owncloud/customgroups/issues/226)

### Fixed

- App should only be shown if it is whitelisted for guests - [#271](https://github.com/owncloud/customgroups/issues/271)
- [Security] Bump tar from 2.2.1 to 2.2.2 - [#238](https://github.com/owncloud/customgroups/issues/238)
- [Security] Bump extend from 3.0.1 to 3.0.2 - [#230](https://github.com/owncloud/customgroups/issues/230)
- [Security] Bump fstream from 1.0.11 to 1.0.12 - [#232](https://github.com/owncloud/customgroups/issues/232)
- [Security] Bump mixin-deep from 1.3.0 to 1.3.2 - [#235](https://github.com/owncloud/customgroups/issues/235)
- [Security] Bump sshpk from 1.13.1 to 1.16.1 - [#236](https://github.com/owncloud/customgroups/issues/236)
- [Security] Bump stringstream from 0.0.5 to 0.0.6 - [#237](https://github.com/owncloud/customgroups/issues/237)

### Changed

- Drop Support for PHP 7.0 - [#275](https://github.com/owncloud/customgroups/issues/275)

## [0.4.1] - 2019-05-16

### Added

- Add validation for group creation - [#197](https://github.com/owncloud/customgroups/issues/197)

## [0.4.0] - 2018-12-03

### Changed

- Set max version to 10 because core platform is switching to Semver

### Fixed

- Sort groups when requested from DB, fixes Oracle - [#187](https://github.com/owncloud/customgroups/issues/187)
- Fix double encoding when displaying group name to delete - [#165](https://github.com/owncloud/customgroups/pull/165)
- PHP 7.2 support - [#164](https://github.com/owncloud/customgroups/pull/164)

## [0.3.6] - 2018-01-11

### Fixed

- restrict autocomplete results when sharing restrictions in place [#117](https://github.com/owncloud/customgroups/pull/117)

## [0.3.5] - 2017-09-15

### Added

- Adding more dispatcher events for the app - [#94](https://github.com/owncloud/customgroups/issues/94) [#103](https://github.com/owncloud/customgroups/issues/103)

### Changed

- Add option to prevent duplicate display names - [#82](https://github.com/owncloud/customgroups/issues/82)
- Use event names with namespace - [#102](https://github.com/owncloud/customgroups/issues/102)
- Set min version to 10.0.3 - [#98](https://github.com/owncloud/customgroups/issues/98)
- Align package.json versions with core - [#101](https://github.com/owncloud/customgroups/issues/101)

### Fixed

- Deleting a custom group now properly deletes associated shares [#92](https://github.com/owncloud/customgroups/pull/92)
- Fix member search in member sidebar to use all search fields - [#106](https://github.com/owncloud/customgroups/issues/106)
- Fix closing sidebar, reset selection - [#96](https://github.com/owncloud/customgroups/issues/96)
- Improve spinners in members view - [#97](https://github.com/owncloud/customgroups/issues/97)
- Prevent registering select event twice on autocomplete - [#95](https://github.com/owncloud/customgroups/issues/95)

## [0.3.4] - 2017-07-19

### Added

- Added events for when administrating groups and members - [#74](https://github.com/owncloud/customgroups/issues/74)

### Changed

- "Group admin" is now renamed to "Group owner" - [#67](https://github.com/owncloud/customgroups/issues/67)
- ownCloud administrators now always see "Administrator" as role - [#67](https://github.com/owncloud/customgroups/issues/67)

### Fixed

- Ellipsize long member names - [#78](https://github.com/owncloud/customgroups/issues/78)
- Fix when avatars are disabled - [#68](https://github.com/owncloud/customgroups/issues/68)
- Implement method that returns users in group - [#73](https://github.com/owncloud/customgroups/issues/73)
- Move restriction checkbox to the sharing section - [#72](https://github.com/owncloud/customgroups/issues/72)
- Allow restricting group creation to subadmins - [#70](https://github.com/owncloud/customgroups/issues/70)
- Show settings section above 'additional' in admin - [#65](https://github.com/owncloud/customgroups/issues/65)
- Added enabling of testing app in the setup. - [#71](https://github.com/owncloud/customgroups/issues/71)

## [0.3.1] - 2017-05-22

### Fixed

- Register section for the app in settings - [#62](https://github.com/owncloud/customgroups/issues/62)

## [0.2.0] - 2017-04-18

### Added

- Added notification for removal of membership - [#56](https://github.com/owncloud/customgroups/issues/56)
- Added notification for role changes - [#56](https://github.com/owncloud/customgroups/issues/56)

### Changed

- Simplified notification subject line - [#56](https://github.com/owncloud/customgroups/issues/56)

## [0.1.1] - 2017-03-27

### Added

- Publish notification when adding user - [#28](https://github.com/owncloud/customgroups/issues/28)
- Added member autocomplete - [#43](https://github.com/owncloud/customgroups/issues/43)
- Use display names for members - [#45](https://github.com/owncloud/customgroups/issues/45)
- Adding app category to app info.xml - [#48](https://github.com/owncloud/customgroups/issues/48)

### Fixed

- Fixes spinner issues - [#47](https://github.com/owncloud/customgroups/issues/47)

[0.6.2]: https://github.com/owncloud/customgroups/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/owncloud/customgroups/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/owncloud/customgroups/compare/v0.5.1...v0.6.0
[0.5.1]: https://github.com/owncloud/customgroups/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/owncloud/customgroups/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/owncloud/customgroups/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/owncloud/customgroups/compare/v0.3.6...v0.4.0
[0.3.6]: https://github.com/owncloud/customgroups/compare/v0.3.5...v0.3.6
[0.3.5]: https://github.com/owncloud/customgroups/compare/v0.3.4...v0.3.5
[0.3.4]: https://github.com/owncloud/customgroups/compare/v0.3.1...v0.3.4
[0.3.1]: https://github.com/owncloud/customgroups/compare/v0.2.0...v0.3.1
[0.2.0]: https://github.com/owncloud/customgroups/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/owncloud/customgroups/compare/v0.1.0...v0.1.1
