# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).


## [0.5.4] - 2021-06-30

### Fixed

- Provide get/list api link resource as absolute url - [#342](https://github.com/owncloud/notifications/issues/342)

## [0.5.3] - 2021-06-21

### Added

- Add command to repair notifications and properly handle mail sending … - [#333](https://github.com/owncloud/notifications/issues/333)
- Add Mail sender name - [#338](https://github.com/owncloud/notifications/issues/338)

## [0.5.2] - 2020-07-15

### Fixed

- Use language code to correctly translate mail body of notifications - [#322](https://github.com/owncloud/notifications/issues/322)

### Added

- Add `Hello` as translatable string to the mail templates - [#320](https://github.com/owncloud/notifications/issues/320)

### Changed

- Bump libraries

## [0.5.0] - 2019-04-25

### Added

- Added bell icon in black - [#185](https://github.com/owncloud/notifications/pull/185)

### Changed

- Drop php 5.6 - [#267](https://github.com/owncloud/notifications/issues/267)

### Fixes

- Only set icon in case an icon is available - [#275](https://github.com/owncloud/notifications/issues/275)

## [0.4.1]

### Added

- Notifications can now have an icon - [#104](https://github.com/owncloud/notifications/issues/104)
- Added occ command to send notification to a user or a group - [#104](https://github.com/owncloud/notifications/issues/104)

### Fixed

- Make sure buttons stays in place even with long messages - [#114](https://github.com/owncloud/notifications/issues/114)
- Don't escape link text title - [#111](https://github.com/owncloud/notifications/issues/111)
- Fix actions and escaping - [#109](https://github.com/owncloud/notifications/issues/109)
- Move OCS calls to app framework - consumes less resources - [#98](https://github.com/owncloud/notifications/pull/98)
- Don't use escaped message for browser notification - [#100](https://github.com/owncloud/notifications/pull/100)

[0.5.4]: https://github.com/owncloud/notifications/compare/v0.5.3...v0.5.4
[0.5.3]: https://github.com/owncloud/notifications/compare/v0.5.2...v0.5.3
[0.5.2]: https://github.com/owncloud/notifications/compare/v0.5.0...v0.5.2
[0.5.0]: https://github.com/owncloud/notifications/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/owncloud/notifications/compare/v0.4.0...v0.4.1
