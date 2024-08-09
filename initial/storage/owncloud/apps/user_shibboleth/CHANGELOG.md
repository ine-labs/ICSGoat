# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [3.5.0] - 2020-06-18

### Added

- Move to the new licensing management - [#300](https://github.com/owncloud/user_shibboleth/issues/300)

### Changed

- Bump libraries

## [3.4.2] - 2020-03-25

### Fixed

- Make sure correct users are matched in determineBackendFor [#289](https://github.com/owncloud/user_shibboleth/issues/289)
  [This completes [#255](https://github.com/owncloud/user_shibboleth/issues/255)]

## [3.4.1] - 2020-03-06

### Fixed

- Fetch the quota for the first returned userid - [#272](https://github.com/owncloud/user_shibboleth/issues/272)
- Check if all user backends agree on the same user id - [#286](https://github.com/owncloud/user_shibboleth/issues/286)
- Exact matches workaround [incomplete fix] - [#255](https://github.com/owncloud/user_shibboleth/issues/255)
- Fix multi value email and filter from now onwards - [#224](https://github.com/owncloud/user_shibboleth/issues/224)

### Added

- Add quota support to user backend - [#175](https://github.com/owncloud/user_shibboleth/issues/175)

## [3.3.0] - 2018-12-18

### Added

- Add adfs tools to build output - [#251](https://github.com/owncloud/user_shibboleth/issues/251)
- Support for PHP 7.2 - [#250](https://github.com/owncloud/user_shibboleth/issues/250)

### Changed

- Set max version to 10 because core platform is switching to Semver - [#257](https://github.com/owncloud/user_shibboleth/issues/257)
- Use new user backend class - [#252](https://github.com/owncloud/user_shibboleth/issues/252)

## [3.2.0] - 2018-05-03

### Changed

- Synchronisation of Metadata is handled by ownCloud core [#208](https://github.com/owncloud/user_shibboleth/pull/208)

## [3.1.2] - 2017-03-15
### Added

- Configurable uid mapper [#202](https://github.com/owncloud/user_shibboleth/pull/202)

### Changed

- Caching reduced to increase metadata sync frequency [#220](https://github.com/owncloud/user_shibboleth/pull/220)

### Fixed

- Improved error handling in user metadata sync on login [#207](https://github.com/owncloud/user_shibboleth/pull/207) [#218](https://github.com/owncloud/user_shibboleth/pull/218)
- Don't disable app when user backend provides ambiguous uids [#209](https://github.com/owncloud/user_shibboleth/pull/209)
- Migration of shibboleth users when upgrading to ownCloud X (required for proper avatar migrations) [#213](https://github.com/owncloud/user_shibboleth/pull/213)
- Proper Exceptions for mapping errors [#217](https://github.com/owncloud/user_shibboleth/pull/217)

## 3.1.1
### Added

- Added tool to filter AD FS generated metadata - [#176](https://github.com/owncloud/user_shibboleth/pull/176)

### Fixed

- Fixed an endless recursion when trying to use auto-provisioning mode - [#178](https://github.com/owncloud/user_shibboleth/pull/178)
- Fixed smooth migration to ownCloud 10 - [#185](https://github.com/owncloud/user_shibboleth/pull/185)
- Fixed returning null for a not existing user with a cached user mapping - [#198](https://github.com/owncloud/user_shibboleth/pull/198)


[3.5.0]: https://github.com/owncloud/user_shibboleth/compare/v3.4.2...v3.5.0
[3.4.2]: https://github.com/owncloud/user_shibboleth/compare/v3.4.1...v3.4.2
[3.4.1]: https://github.com/owncloud/user_shibboleth/compare/v3.3.0...v3.4.1
[3.3.0]: https://github.com/owncloud/user_shibboleth/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/owncloud/user_shibboleth/compare/v3.1.2...v3.2.0
[3.1.2]: https://github.com/owncloud/user_shibboleth/compare/v3.1.1...v3.1.2
