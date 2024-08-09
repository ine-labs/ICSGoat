# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [2.1.4] - 2022-01-14

### Fixed

- Fix: Api requests even work if user needs to reset the password - [#355](https://github.com/owncloud/password_policy/issues/355)


## [2.1.3] - 2021-07-05

### Changed

- Show only hints if numeric value is gt 0 - [#345](https://github.com/owncloud/password_policy/issues/345)
- Adapt ui to new login design - [#343](https://github.com/owncloud/password_policy/issues/343)
- Add password requirement hints and Capabilities API - [#335](https://github.com/owncloud/password_policy/issues/335)
- Use relative url instead of absoulute url - [#330](https://github.com/owncloud/password_policy/issues/330)

## [2.1.2] - 2020-03-20

### Fixed

- Validate expiration date for public links only - [#293](https://github.com/owncloud/password_policy/issues/293)

### Changed

- Adjust Symfony eventDispatcher->dispatch calls - [#279](https://github.com/owncloud/password_policy/issues/279)

## [2.1.1] - 2020-02-04

### Fixed

- Check if first time password change is necessary in postLogin - [#275](https://github.com/owncloud/password_policy/issues/275)

## [2.1.0] - 2019-12-06

### Changed

- Drop PHP 7.0 Support  - [#267](https://github.com/owncloud/password_policy/issues/267)
- Use CHAR_SYMBOLS to generate password when no special chars are specified - [#233](https://github.com/owncloud/password_policy/issues/233)
- Drop PHP 5.6 Support - [#211](https://github.com/owncloud/password_policy/issues/211)
- Implement phpstan and phan - [#169](https://github.com/owncloud/password_policy/issues/169)

### Fixed

- Special characters provided shouldn't be empty - [#234](https://github.com/owncloud/password_policy/issues/234)
- Increase width of numeric fields to display 3 digits - [#231](https://github.com/owncloud/password_policy/issues/231)
- Adjust link expiry UI text adding 'maximum' - [#226](https://github.com/owncloud/password_policy/issues/226)

## [2.0.2] - 2018-12-03

### Changed

- Set max version to 10 because core platform is switching to Semver

### Fixed

- Don't enforce first login password for non-local user backends like LDAP - [#173](https://github.com/owncloud/password_policy/issues/173)
- Don't enforce first login password when password already set through other means like the guests app - [#171](https://github.com/owncloud/password_policy/issues/171)

## [2.0.1] - 2018-09-28

### Added

- Add options 'all' and 'group' to occ expire-password command - [#77](https://github.com/owncloud/password_policy/issues/77) [#122](https://github.com/owncloud/password_policy/issues/122) [#144](https://github.com/owncloud/password_policy/issues/144)
- Support for PHP 7.2 - [#118](https://github.com/owncloud/password_policy/issues/118)

### Changed

- More user-friendly message in first time login page - [#81](https://github.com/owncloud/password_policy/issues/81)

### Fixed

- Disable occ user:expire-password if no expiration rule was configured - [#115](https://github.com/owncloud/password_policy/issues/115)
- Password expiration now work with Oracle databases - [#114](https://github.com/owncloud/password_policy/issues/114)
- Password history of user is now cleared when user is deleted - [#83](https://github.com/owncloud/password_policy/issues/83)
- Improve config messages and field grouping in settings - [#80](https://github.com/owncloud/password_policy/issues/80)
- Characters "<" and ">" can now be added in special chars rule - [#82](https://github.com/owncloud/password_policy/issues/82)
- Correct doc link and add notification features to description - [#75](https://github.com/owncloud/password_policy/issues/75)
- Fix off by one error where history would apply to one more history entry than configured - [#64](https://github.com/owncloud/password_policy/issues/64)
- Return a constant if the user cannot change their password - [#62](https://github.com/owncloud/password_policy/issues/62)
- Return a constant not a magic number if the user is null - [#60](https://github.com/owncloud/password_policy/issues/60)

## 2.0.0 - 2018-07-17

### Added

- Password history rule - [#10](https://github.com/owncloud/password_policy/pull/10) [#34](https://github.com/owncloud/password_policy/issues/34)
- Password expiration - [#15](https://github.com/owncloud/password_policy/pull/15) [#27](https://github.com/owncloud/password_policy/issues/27) [#31](https://github.com/owncloud/password_policy/issues/31) [#51](https://github.com/owncloud/password_policy/issues/51) [#56](https://github.com/owncloud/password_policy/issues/56)

### Changed

- Relicensed as GPLv2 starting with 2.0.0

### Fixed

- Allow empty passwords for public links even when rules are set - [#36](https://github.com/owncloud/password_policy/issues/36)
- Remove wrong placeholder on special character input field - [#6](https://github.com/owncloud/password_policy/issues/6)
- Spelling errors - [#5](https://github.com/owncloud/password_policy/issues/5)

## 1.1.0

### Added

- Add compliant password generation event handler for other apps that need it - #109

## 1.0.3

### Changed

- Change description, add docs - #105
- Translate save button - #102
- Change headline and subheadlines - #92

[Unreleased]: https://github.com/owncloud/password_policy/compare/v2.1.4...master
[2.1.4]: https://github.com/owncloud/password_policy/compare/v2.1.3...v2.1.4
[2.1.3]: https://github.com/owncloud/password_policy/compare/v2.1.2...v2.1.3
[2.1.2]: https://github.com/owncloud/password_policy/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/owncloud/password_policy/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/owncloud/password_policy/compare/v2.0.2...v2.1.0
[2.0.2]: https://github.com/owncloud/password_policy/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/owncloud/password_policy/compare/v2.0.0...v2.0.1
