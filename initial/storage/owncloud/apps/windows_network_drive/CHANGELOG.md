# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [2.1.1] - 2022-05-09

### Changed

- Return false if we can't decrypt a password - [#388](https://github.com/owncloud/windows_network_drive/pull/388)
- Hide the global password once saved - [#395](https://github.com/owncloud/windows_network_drive/pull/395)
- Fix infinite loop while reading if the connection is cut off - [#397](https://github.com/owncloud/windows_network_drive/pull/397)

## [2.1.0] - 2021-08-16

### Added

- Add support to use the service account's password for WND2 mounts - [#362](https://github.com/owncloud/windows_network_drive/pull/362)
- Ldapmembership cache - [#355](https://github.com/owncloud/windows_network_drive/pull/355)
- Enable listener to work with the service account supplied via UI enterprise - [#4641](https://github.com/owncloud/enterprise/issues/4641)

### Changed

- Reset wnd credentials on invalid password to prevent further retries and lockout - [#225](https://github.com/owncloud/windows_network_drive/issues/225)
- Reset password on invalid login attempt - [#343](https://github.com/owncloud/windows_network_drive/pull/343)
- Adjust WND permissions - [#351](https://github.com/owncloud/windows_network_drive/pull/351)
- Ensure wnd2 cache is cleaned up when actions are performed in the inner - [#349](https://github.com/owncloud/windows_network_drive/pull/349)
- SMB Performance - [#4614](https://github.com/owncloud/enterprise/issues/4614)
- Fix process-queue when rename source or target is outside the storage - [#379](https://github.com/owncloud/windows_network_drive/pull/379)


## [2.0.0] - 2021-03-15

### Added

- Allow sharees to receive activity notifications coming from wnd - [#310](https://github.com/owncloud/windows_network_drive/issues/310)
- Move the post_login hook out of the constructor to reduce memory usage - [#298](https://github.com/owncloud/windows_network_drive/issues/298)
- Add doc link, minor improvements - [#317](https://github.com/owncloud/windows_network_drive/issues/317)
- Collaborative editing of shared documents, WND2 - [#323](https://github.com/owncloud/windows_network_drive/issues/323)

### Changed

- Fix encryption with phpseclib v3 - [#330](https://github.com/owncloud/windows_network_drive/issues/330)
- Throw error if share name contains backslashes - [#316](https://github.com/owncloud/windows_network_drive/issues/316)
- Fix return type of SMB getId due to core 38151 - [#314](https://github.com/owncloud/windows_network_drive/issues/314)
- Use weak references in the wndnotifier for php 7.4 - [#309](https://github.com/owncloud/windows_network_drive/issues/309)
- Fix broken hooks for the login credentials saved in session - [#308](https://github.com/owncloud/windows_network_drive/issues/308)
- Activity from SMB share does not appear - [#299](https://github.com/owncloud/windows_network_drive/issues/299)
- Add icons and adjust messages
- Fix modify before rename


## [1.1.0] - 2020-06-17

### Added

- Move to the new licensing management - [#294](https://github.com/owncloud/windows_network_drive/issues/294)

### Changed

- Set owncloud min-version to 10.5
- Permission manager cache proxy - [#286](https://github.com/owncloud/windows_network_drive/issues/286)
- Include option to skip the "is mount point hidden" check - [#292](https://github.com/owncloud/windows_network_drive/issues/292)
- Bump dependencies

## [1.0.1] - 2020-03-03

### Added

- Acl support - [#234](https://github.com/owncloud/windows_network_drive/issues/234)
- Create new auth mechanism - [#242](https://github.com/owncloud/windows_network_drive/issues/242)

### Changed

- While processing the notification queue, consider the file exists only - [#223](https://github.com/owncloud/windows_network_drive/issues/223)
- Allow reset storage passwords from the process-queue command - [#226](https://github.com/owncloud/windows_network_drive/issues/226)
- Optimize the wnd hook when an ACL changes - [#250](https://github.com/owncloud/windows_network_drive/issues/250)
- Event handling for the ACL changes triggered by the smb_acl app - [#232](https://github.com/owncloud/windows_network_drive/issues/232)
- If the path isn't present in the cache, update the closest parent - [#240](https://github.com/owncloud/windows_network_drive/issues/240)
- Process events triggered during propagation to update the permissions - [#258](https://github.com/owncloud/windows_network_drive/issues/258)
- Ignore the entry if it has the IO flag - [#264](https://github.com/owncloud/windows_network_drive/issues/264)
- Do not blacklist the parent during propagation - [#260](https://github.com/owncloud/windows_network_drive/issues/260)

### Fixed
- Use migrations instead of legacy db schema file - [#267](https://github.com/owncloud/windows_network_drive/issues/267)
- Fix domain usage - [#249](https://github.com/owncloud/windows_network_drive/issues/249)
- Add event logger and check only if the user is in the group instead of - [#247](https://github.com/owncloud/windows_network_drive/issues/247)
- Include config switch to disable the WNDNotifier to reduce memory usage - [#248](https://github.com/owncloud/windows_network_drive/issues/248)
- Remove the default_enable tag, which causes problems with the migrations - [#281](https://github.com/owncloud/windows_network_drive/issues/281)

## [0.7.4] - 2019-01-09

### Fixed

- Fix tar to use GNU format to prevent bug during extraction on installation - [#220](https://github.com/owncloud/windows_network_drive/pull/220)

## [0.7.3] - 2018-12-03

### Changed

- Set max version to 10 because core platform switches to Semver

## [0.7.2] - 2018-11-05

### Added

- Use "samaccountname" as user name for WND storage login credentials when available - [#175](https://github.com/owncloud/windows_network_drive/issues/175)
- PHP 7.2 support - [#190](https://github.com/owncloud/windows_network_drive/issues/190)

## [0.7.1] - 2018-05-03

### Fixed

- Issue when using `wnd:listen` without an interactive terminal session

### Removed

- wnd:listen-service was deprecated in favor of using a system supervisor (i.e. systemd)

## [0.7.0] - 2018-02-21

### Added

- Allow domain / workgroup to be used in the wnd:listen (and wnd:listen-service) commands via 'domain\username' as username parameter
- Allow listener's password to be read from a file or from stdin
- Chunk size validation for the wnd:process queue (must be positive)
- Added wnd:listen-service command to respawn wnd:listen after the idle timeout is reached
- Hide the wnd:listen-service command; it won't be shown with occ list. The command can still be used if needed
- Trim passwords (remove blank chars) [#129](https://github.com/owncloud/windows_network_drive/pull/129)

### Changed

- Improved rename logic
- wnd:listen command rewrite, it won't block while listening for notifications

[Unreleased]: https://github.com/owncloud/windows_network_drive/compare/v2.1.1...master
[2.1.1]: https://github.com/owncloud/windows_network_drive/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/owncloud/windows_network_drive/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/owncloud/windows_network_drive/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/owncloud/windows_network_drive/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/owncloud/windows_network_drive/compare/v0.7.4...v1.0.1
[0.7.4]: https://github.com/owncloud/windows_network_drive/compare/v0.7.3...v0.7.4
[0.7.3]: https://github.com/owncloud/windows_network_drive/compare/v0.7.2...v0.7.3
[0.7.2]: https://github.com/owncloud/windows_network_drive/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/owncloud/windows_network_drive/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/owncloud/windows_network_drive/compare/v0.6.1...v0.7.0
