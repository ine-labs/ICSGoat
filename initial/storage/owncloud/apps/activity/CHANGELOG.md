# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [2.7.0] - 2021-12-22

### Added

- Include federated share activity in sidebar [#972](https://github.com/owncloud/activity/pull/972)
- Implement activities for rename and move actions [#1018](https://github.com/owncloud/activity/pull/1018)


## [2.6.1] - 2021-06-17

### Changed

- Add shareExpire-param to indicate if a share was removed due to its expiry - [#944](https://github.com/owncloud/activity/issues/944)
- Automations in activity stream - [#942](https://github.com/owncloud/activity/issues/942)

### Fixed

- Expired public share has wrong activity message - [#965](https://github.com/owncloud/activity/issues/965)


## [2.6.0] - 2020-10-16

### Added

- Files/folders in HTML emails are now clickable - [#893](https://github.com/owncloud/activity/issues/893)


## [2.5.4] - 2020-08-07

### Fixed

- If object_name empty for file activity, derive file path from filecache info path - [#875](https://github.com/owncloud/activity/issues/875)

### Changed

- Bump libraries


## [2.5.3] - 2020-03-10

### Added

- Add a config option to send notifications ASAP - [#833](https://github.com/owncloud/activity/issues/833)

### Fixed

- Skip disabled users and do not break the cronjob on the first unsent email - [#842](https://github.com/owncloud/activity/issues/842)

## [2.5.2] - 2019-12-23

### Added

- occ command to send all pending notifications - [#811](https://github.com/owncloud/activity/issues/811)

### Removed

- Drop Support for PHP 7.0 - [#806](https://github.com/owncloud/activity/issues/806)

### Fixed

- Avatars in activity stream for public link uploads - [#801](https://github.com/owncloud/activity/issues/801)

## [2.5.1] 2019-09-11

### Added

- Url Formatter [#744](https://github.com/owncloud/activity/pull/744)

## [2.5.0] 2019-06-21

### Fixed

- Error handling when a group no longer exists [#738](https://github.com/owncloud/activity/pull/738)

### Changed

- Icons renamed to be adblock-friendly [#728](https://github.com/owncloud/activity/pull/728)

### Removed

- Dropped php 5.6 support [#718](https://github.com/owncloud/activity/pull/718)

## [2.4.2] - 2019-02-13

- Handle no longer existing groups - [#738](https://github.com/owncloud/activity/pull/738)

### Fixed

- Properly identify user from federation instance in activity entries, requires core 10.1.0 to work - [#672](https://github.com/owncloud/activity/pull/672)

## [2.4.1] - 2018-11-30

### Changed

- Set max version to 10 because core platform switches to Semver

## [2.4.0] - 2018-11-13

### Fixed

- Add missing migrations for longtext and indices - [#654](https://github.com/owncloud/activity/issues/654)

## [2.3.8] - 2018-09-25
### Added

- New mail footers to template files. - [#639](https://github.com/owncloud/activity/issues/639)

### Fixed

- Prepare for jquery 2 update - [#638](https://github.com/owncloud/activity/issues/638)

## [2.3.7] - 2018-07-11

### Fixed

- Adjust preview URL generation based on new endpiont - [#631](https://github.com/owncloud/activity/pull/631)
- Fix grouping for share messages - [#626](https://github.com/owncloud/activity/issues/626)
- Add missing HTML template to activity email notification - [#620](https://github.com/owncloud/activity/issues/620)
- Don't try to delete items when no users in context - [#604](https://github.com/owncloud/activity/issues/604)
- Chunk deleting of rows from the activity table for cluster environments - [#610](https://github.com/owncloud/activity/issues/610)
- Fix for when avatars are disabled - [#600](https://github.com/owncloud/activity/issues/600)

## [2.3.6]

### Fixed

- Don't package vendor folder - [#588](https://github.com/owncloud/activity/issues/588)
- Migrate to bigint - [#584](https://github.com/owncloud/activity/issues/584)
- Show displayName instead of group id - [#582](https://github.com/owncloud/activity/issues/582)
- Catch mail exceptions and still remove sent emails from queue - [#574](https://github.com/owncloud/activity/issues/574)


[Unreleased]: https://github.com/owncloud/activity/compare/v2.7.0...master
[2.7.0]: https://github.com/owncloud/activity/compare/v2.6.0...v2.7.0
[2.6.1]: https://github.com/owncloud/activity/compare/v2.6.0...v2.6.1
[2.6.0]: https://github.com/owncloud/activity/compare/v2.5.4...v2.6.0
[2.5.4]: https://github.com/owncloud/activity/compare/v2.5.3...v2.5.4
[2.5.3]: https://github.com/owncloud/activity/compare/v2.5.2...v2.5.3
[2.5.2]: https://github.com/owncloud/activity/compare/v2.5.1...v2.5.2
[2.5.1]: https://github.com/owncloud/activity/compare/v2.5.0...v2.5.1
[2.5.0]: https://github.com/owncloud/activity/compare/v2.4.2...v2.5.0
[2.4.2]: https://github.com/owncloud/activity/compare/v2.4.1...v2.4.2
[2.4.1]: https://github.com/owncloud/activity/compare/v2.4.0...v2.4.1
[2.4.0]: https://github.com/owncloud/activity/compare/v2.3.8...v2.4.0
[2.3.8]: https://github.com/owncloud/activity/compare/v2.3.7...v2.3.8
[2.3.7]: https://github.com/owncloud/activity/compare/v2.3.6...v2.3.7
[2.3.6]: https://github.com/owncloud/activity/compare/v10.0.2...v2.3.6
