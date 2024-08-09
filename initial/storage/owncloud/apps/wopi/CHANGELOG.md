# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [1.6.0] - 2022-03-31

### Added

- Feat: enable business user flow [#171](https://github.com/owncloud/wopi/pull/171)
- Support master key encryption [#184](https://github.com/owncloud/wopi/pull/184])
- Add view and edit to default file click actions [#185](https://github.com/owncloud/wopi/pull/185)


## [1.5.1] - 2021-10-05

### Fixed

- Fix wopi ignores group restriction - [#161](https://github.com/owncloud/wopi/pull/161)


## [1.5.0] - 2021-01-20

### Added

- Add handling for public links -[#108](https://github.com/owncloud/wopi/issues/108)

## [1.4.0] - 2020-06-22

### Added

- Move to the new licensing management - [#94](https://github.com/owncloud/wopi/issues/94)
- Added requirements - [#80](https://github.com/owncloud/wopi/issues/80)
- Support PHP 7.4 - [#91](https://github.com/owncloud/wopi/issues/91)

### Changed

- Set owncloud min-version to 10.5
- Bump libraries

## [1.3.0] - 2020-01-08

### Added

- Add support for Put Relative - [#68](https://github.com/owncloud/wopi/issues/68)
- Enable PHP 7.3 - [#73](https://github.com/owncloud/wopi/issues/73)

### Fixed

- Fix bug when server default is different than English - [#67](https://github.com/owncloud/wopi/issues/67)
- Don't register scripts, if session user is not member of allowed group - [#64](https://github.com/owncloud/wopi/issues/64)

## [1.2.0] - 2019-06-19

### Fixed

- Proper handling of unsupported `Save As` error cases [#51](https://github.com/owncloud/wopi/pull/51)
- Creation of new Excel Files with Safari [#53](https://github.com/owncloud/wopi/pull/53)
- Correctly detect uppercase file extension [#46](https://github.com/owncloud/wopi/issues/46)[#52](https://github.com/owncloud/wopi/pull/52)
- Translation issues with Excel [#51](https://github.com/owncloud/wopi/pull/51)
- Extraction of Content Security Policies [#50](https://github.com/owncloud/wopi/pull/50) [#47](https://github.com/owncloud/wopi/issues/47)
- Properly set application ID in app template [#50](https://github.com/owncloud/wopi/pull/50)

## [1.1.0] - 2019-03-14

### Added

- Add support for different languages - [#42](https://github.com/owncloud/wopi/issues/42)

## 1.0.0 - 2019-02-08

- Initial release

[Unreleased]: https://github.com/owncloud/wopi/compare/v1.6.0..master
[1.6.0]: https://github.com/owncloud/wopi/compare/v1.5.1..v1.6.0
[1.5.1]: https://github.com/owncloud/wopi/compare/v1.5.0..v1.5.1
[1.5.0]: https://github.com/owncloud/wopi/compare/v1.4.0..v1.5.0
[1.4.0]: https://github.com/owncloud/wopi/compare/v1.3.0..v1.4.0
[1.3.0]: https://github.com/owncloud/wopi/compare/v1.2.0..v1.3.0
[1.2.0]: https://github.com/owncloud/wopi/compare/v1.1.0..v1.2.0
[1.1.0]: https://github.com/owncloud/wopi/compare/v1.0.0..v1.1.0
