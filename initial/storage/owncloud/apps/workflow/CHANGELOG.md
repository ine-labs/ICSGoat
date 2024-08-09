# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.5.4] - 2022-03-24

### Fixed

-  Adjust for changes in core. GetPlugins returns plugin and priority now [#280](https://github.com/owncloud/workflow/issues/280)


## [0.5.3] - 2020-07-13

### Fixed

- Always restore agent author [#264](https://github.com/owncloud/workflow/issues/264)


## [0.5.2] - 2020-06-21

### Changed

- Automations in activity stream - [#255](https://github.com/owncloud/workflow/issues/255)
- Enhance ui for save/delete retention periods - [#256](https://github.com/owncloud/workflow/issues/256)

## [0.5.1] - 2020-07-06

### Changed   

- Set owncloud min-version to 10.5
- Remove `default_enable` - [#242](https://github.com/owncloud/workflow/issues/242)

## [0.5.0] - 2020-06-18

### Added

- Move to the new licensing management - [#238](https://github.com/owncloud/workflow/issues/238)

### Changed

- Update upload time property to have a type - [#232](https://github.com/owncloud/workflow/issues/232)
- Bump libraries

## [0.4.0] - 2020-01-29

### Added

- Allow configuring retention property for UserBasedRetention - [#214](https://github.com/owncloud/workflow/issues/214)

## [0.3.1] - 2019-11-21

### Changed

- Rename 'IP Range' and 'Subnet' options - [#199](https://github.com/owncloud/workflow/issues/199)
- Use migrations instead of legacy db schema file - [#196](https://github.com/owncloud/workflow/issues/196)
- Tag based retentions can now work on upload-time as stored in the dav… - [#191](https://github.com/owncloud/workflow/issues/191)
- Drop PHP 5.6 - [#181](https://github.com/owncloud/workflow/issues/181)

## [0.3.0] - 2018-12-03

### Added

- PHP 7.2 support - [#159](https://github.com/owncloud/workflow/issues/159)

### Changed

- Set max version to 10 for Semver in core platform

### Fixed
- Grammar in README.md - [#157](https://github.com/owncloud/workflow/issues/157)

## [0.2.6] - 2018-02-19

### Fixed

- Issue with retention
- Tagging with object store storage
- File size rule and tagging for chunked upload

## [0.2.5] - 2017-09-15

### Fixed

- Issue in packaging - app was not functional at all

## [0.2.4] - 2017-07-20

### First marketplace release

[Unreleased]: https://github.com/owncloud/workflow/compare/v0.5.4...master
[0.5.4]: https://github.com/owncloud/workflow/compare/v0.5.3...v0.5.4
[0.5.3]: https://github.com/owncloud/workflow/compare/v0.5.2...v0.5.3
[0.5.2]: https://github.com/owncloud/workflow/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/owncloud/workflow/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/owncloud/workflow/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/owncloud/workflow/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/owncloud/workflow/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/owncloud/workflow/compare/v0.2.6...v0.3.0
[0.2.6]: https://github.com/owncloud/workflow/compare/v0.2.5...v0.2.6
[0.2.5]: https://github.com/owncloud/workflow/compare/v0.2.4...v0.2.5
[0.2.4]: https://github.com/owncloud/workflow/compare/v10.0.0...v0.2.4
