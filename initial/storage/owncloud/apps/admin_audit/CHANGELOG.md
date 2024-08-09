 Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [2.1.3] - 2021-06-17

### Changed

- Ensure the fileinfo is loaded before the node is deleted - [#373](https://github.com/owncloud/admin_audit/issues/373)

## [2.1.2] - 2021-03-31

### Changed

- Problem with public shares and external storages [#344](https://github.com/owncloud/admin_audit/issues/344)
- Bump libraries

## [2.1.1] - 2020-07-06

### Changed

- Remove `default_enable` - [#337](https://github.com/owncloud/admin_audit/issues/337)

## [2.1.0] - 2020-06-08

### Added

- Move to the new licensing management - [#291](https://github.com/owncloud/admin_audit/issues/291)
- Add exception handling for trashbin expire occ command/background job - [#288](https://github.com/owncloud/admin_audit/issues/288)

### Removed

- Drop PHP 7.1 - [#312](https://github.com/owncloud/admin_audit/issues/312)

### Changed

- Set owncloud min-version to 10.5
- Bump libraries

## [2.0.0] - 2020-04-08

### Added

- New log format, see README.md for details - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Metadata fields in the JSON log messages - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log filter by user groups - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for user preferences changes - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for failed login attempts - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for assigning/unassigning collaborative tags - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for impersonate app - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for group creation and deletion - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for files_lifecycle app - [#112](https://github.com/owncloud/admin_audit/pull/112)
- Log events for smb_acl app - [#112](https://github.com/owncloud/admin_audit/pull/112)

### Changed

- Improved some log message texts - [#112](https://github.com/owncloud/admin_audit/pull/112)

### Fixed

- Get audit logs for loglevel info - [#289](https://github.com/owncloud/admin_audit/pull/289)
- Fix the undefined fileId for the public link - [#273](https://github.com/owncloud/admin_audit/issues/273)
- Fix the path for the file_copy operation - [#269](https://github.com/owncloud/admin_audit/issues/269)
- Remove audit_raw messages from the log - [#294](https://github.com/owncloud/admin_audit/issues/294)

### Removed

- Drop Support for PHP 7.0 - [#272](https://github.com/owncloud/admin_audit/issues/272)

## [1.0.3] - 2019-05-15

### Added

- Add new events for the smb_acl app - [#101](https://github.com/owncloud/admin_audit/issues/101)

### Fixed

- Use Folder::getById() with single node - [#98](https://github.com/owncloud/admin_audit/issues/98)
- Fix app name interference - [#74](https://github.com/owncloud/admin_audit/issues/74)

## [1.0.2] - 2018-12-03

### Changed

- Set max version to 10 because core platform is switching to Semver

### Fixed

- Use productName instead of ownCloud for logging - [#92](https://github.com/owncloud/admin_audit/issues/92)
- Fix strlen warnings - [#91](https://github.com/owncloud/admin_audit/issues/91)

## [1.0.1] - 2018-10-05

### Fixed

- Issue causing login failures when using shibboleth or app passwords [#83](https://github.com/owncloud/admin_audit/pull/83)

## [1.0.0] - 2018-04-18

### Added

- Logging of configurations changes [#52](https://github.com/owncloud/admin_audit/pull/52)

### Changed

- Log format when promoting / demoting users as (group) admins [#73](https://github.com/owncloud/admin_audit/pull/73)
- Log format for sharing relevant events [#69](https://github.com/owncloud/admin_audit/pull/69)

## [0.9.0] - 2018-02-15

### Added

 - Logging of federated share activity [#23](https://github.com/owncloud/admin_audit/pull/23) [#24](https://github.com/owncloud/admin_audit/pull/24/files)
 - Logging of comment activity [#30](https://github.com/owncloud/admin_audit/pull/30)

### Changed

- Replaced old hooks with new event system [#21](https://github.com/owncloud/admin_audit/pull/21)
- Reduced logging noise [#40](https://github.com/owncloud/admin_audit/pull/40)

### Fixed

- Various minor improvements [#20](https://github.com/owncloud/admin_audit/pull/20)
- Ignore non resolvable absolute paths [#41](https://github.com/owncloud/admin_audit/pull/41)

## 0.8.1 - 2017-09-14

### Added

- Add leaveFromGroup event to log self removal - #16
- Additional check for password field to differntiate whether share is created with or without password - #15
- Adding logs for customgroups - #13
- Enable log for unshare by recipient - #5
- Enable log for ldap users added/removed to oC group - #4
- Add federated share logs for accept/reject by user - #2
- Admin audit enhancements to log user agent, version rollbacks and deletes, link-share access and link-share password updates, enabling and disabling of apps - #1080
- Differ between write and update of file - #628
- Make files_sharing_log & admin_audit compatible with app code check - #615

### Changed

- Change display name of the app - #1785
- Improve log messages for impersonate app
- Make consistent log when public share link is accessed - #10

[2.1.3]: https://github.com/owncloud/admin_audit/compare/v2.1.2...v2.1.3
[2.1.2]: https://github.com/owncloud/admin_audit/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/owncloud/admin_audit/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/owncloud/admin_audit/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/owncloud/admin_audit/compare/v1.0.3...v2.0.0
[1.0.3]: https://github.com/owncloud/admin_audit/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/owncloud/admin_audit/compare/1.0.1...v1.0.2
[1.0.1]: https://github.com/owncloud/admin_audit/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/owncloud/admin_audit/compare/0.9.0...1.0.0
[0.9.0]: https://github.com/owncloud/admin_audit/compare/0.8.1...0.9.0
