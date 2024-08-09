# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [1.3.2]- 2021-06-23

### Fixed

- Ensure the fileinfo is loaded before moving to the archive - [#362](https://github.com/owncloud/files_lifecycle/issues/362)

## [1.3.1] - 2021-05-27

### Fixed

- Don't create archive folder during file scan [#333](https://github.com/owncloud/files_lifecycle/issues/333)


## [1.3.0] - 2020-09-17

### Added

- UI improvements, new licenseManager - [#270](https://github.com/owncloud/files_lifecycle/issues/270)
- Add a new field to the mapper - [#241](https://github.com/owncloud/files_lifecycle/issues/241)

### Fixed

- Fix mimetype for folders - [#275](https://github.com/owncloud/files_lifecycle/issues/275)

### Changed

- Set owncloud min-version 10.5 because of PR 241 - [#242](https://github.com/owncloud/files_lifecycle/issues/242)
- [Security] Bump lodash from 4.17.15 to 4.17.19 - [#264](https://github.com/owncloud/files_lifecycle/issues/264)
- [Security] Bump handlebars from 4.5.3 to 4.7.6 - [#245](https://github.com/owncloud/files_lifecycle/issues/245)

## [1.2.1] -2020-03-18

### Added

- Add translations support - [#210](https://github.com/owncloud/files_lifecycle/issues/210)

### Changed

- Bump symfony/filesystem from 4.4.4 to 4.4.5 - [#203](https://github.com/owncloud/files_lifecycle/issues/203)

## [1.2.0] - 2020-02-27

### Changed

- Bump libraries

## [1.1.0] - 2019-12-27

### Fixed

- Improve Archive Listing - [#124](https://github.com/owncloud/files_lifecycle/pull/124)
- Fix width of nav entry - [#146](https://github.com/owncloud/files_lifecycle/pull/146)
- Catch not found files when trying to archive and expire - [#105](https://github.com/owncloud/files_lifecycle/pull/105)

### Added

- Dry run mode for CLI commands - [#102](https://github.com/owncloud/files_lifecycle/pull/102)
- Soft Policy - [#100](https://github.com/owncloud/files_lifecycle/pull/100)
- Add colourful  warning if less than 6 days remain - [#120](https://github.com/owncloud/files_lifecycle/pull/120)
- Add config switch to disable UI - [#147](https://github.com/owncloud/files_lifecycle/pull/147)
- Support for PHP 7.3 - [#139](https://github.com/owncloud/files_lifecycle/pull/139)
- Support for Oracle DB and SQlite - [#160](https://github.com/owncloud/files_lifecycle/pull/160)

### Changed

- Library Updates - [All Changes](https://github.com/owncloud/files_lifecycle/compare/v1.0.0...v1.1.0)
- Use single node response for Folder->getById() - [#98](https://github.com/owncloud/files_lifecycle/pull/98)
- Bump handlebars from 4.0.12 to 4.5.3 - [#177](https://github.com/owncloud/files_lifecycle/pull/177)

### Removed

- Drop Support for PHP 5.6 - [#107](https://github.com/owncloud/files_lifecycle/pull/107)
- Drop Support for PHP 7.0 - [#166](https://github.com/owncloud/files_lifecycle/pull/166)

## 1.0.0

- Initial release

[1.3.2]: https://github.com/owncloud/files_lifecycle/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/owncloud/files_lifecycle/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/owncloud/files_lifecycle/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/owncloud/files_lifecycle/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/owncloud/files_lifecycle/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/owncloud/files_lifecycle/compare/v1.0.0...v1.1.0
