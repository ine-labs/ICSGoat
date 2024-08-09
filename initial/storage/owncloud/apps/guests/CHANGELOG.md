# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.9.3] - 2021-08-09

### Changed

- adapt new login form design - [#446](https://github.com/owncloud/guests/pull/446)

## [0.9.2] - 2021-07-29

### Changed

- Add oco_selfservice to default whitelist - [#453](https://github.com/owncloud/guests/issues/453)

## [0.9.1] - 2021-04-14

### Changed 

- Whitelist files_lifecycle app - [#243](https://github.com/owncloud/files_lifecycle/issues/243#issuecomment-675951947)
- Check whether we should also add windows_network_drive - [#4074](https://github.com/owncloud/enterprise/issues/4074#issuecomment-686296482)

## [0.9.0] - 2020-03-17

### Fixed

- Use rawurldecode for allowing "+" in guests emails - [#384](https://github.com/owncloud/guests/issues/384)
- Use model addShare instead of share api while wrapping ShareDialogView - [#369](https://github.com/owncloud/guests/issues/369)

### Changed

- Set core min-version to 10.4 - [#402](https://github.com/owncloud/guests/issues/402)
- Bump libraries - [#403](https://github.com/owncloud/guests/issues/403)

## [0.8.2] - 2019-08-14

### Fixed

- Creation of guest shares are now case insensitive for Upper/lowercased emails  - [#326](https://github.com/owncloud/guests/pull/326)

### Changed

- Added various apps to the default default application whitelist for guests [#315](https://github.com/owncloud/guests/pull/315)
- Removed guest label in drop down to adjust with core display changes - [#337](https://github.com/owncloud/guests/pull/337)


## [0.8.1] - 2019-04-15

### Fixed

- Fix high database load when querying guest members - [#318](https://github.com/owncloud/guests/pull/318)

## [0.8.0] - 2019-03-05

### Changed

- Changes core min-version requirement to 10.1.0 - [#274](https://github.com/owncloud/guests/issues/274)
- Use email validation functions from core, requires core >= 10.1.0 - [#274](https://github.com/owncloud/guests/issues/274)
- Code style cleanup, now PSR-4 - [#305](https://github.com/owncloud/guests/issues/305)

### Fixed

- Fix share with guest_app endless loop, add unit tests - [#290](https://github.com/owncloud/guests/issues/290)

## [0.7.0] - 2018-11-30

### Changed

- Set max version to 10 because core platform is switching to Semver
- Guests now cannot invite new guests any more - [#224](https://github.com/owncloud/guests/pull/224)
- Use new core share API as old one will be removed in OC 11 - [#245](https://github.com/owncloud/guests/pull/245)

### Fixed

- Fix warning about missing user_autofocus attribute - [#264](https://github.com/owncloud/guests/pull/264)

## [0.6.2] - 2018-10-29

### Fixed

- Adjust max-version to 10.1 to be able to release to marketplace

## [0.6.0] - 2018-10-25

### Added

- Add password_policy and oauth2 to default whitelist - [#227](https://github.com/owncloud/guests/issues/227)

### Changed

- Increase max-version because core platform is switching to semver - [#245](https://github.com/owncloud/guests/pull/245)

### Removed

### Fixed

- Fix for events not being triggered properly for password policy app - [#243](https://github.com/owncloud/guests/pull/243)
- Don't remove navigation menu if only a single item is available - [#221](https://github.com/owncloud/guests/issues/221)
- Apply owncloud-coding standard - [#211](https://github.com/owncloud/guests/issues/211)

## [0.5.0] - 2017-11-13

### Changed

- Fix guest autocomplete addition - [#151](https://github.com/owncloud/guests/issues/151)
- Remove modal, guests now only created by email in the share panel - [#149](https://github.com/owncloud/guests/issues/149)
- Update LICENSE to GPL-2.0 - [#155](https://github.com/owncloud/guests/issues/155)

### Fixed

- Detect existing email/user on frontend and backend - [#168](https://github.com/owncloud/guests/issues/168)
- Improve detection of email addresses when typed into share field - [#170](https://github.com/owncloud/guests/issues/170)
- Preserve token when reshowing form after error - [#158](https://github.com/owncloud/guests/issues/158)
- Add event driven password generation - [#160](https://github.com/owncloud/guests/issues/160)
- Apply whitelist to frontend and comments URLs - [#165](https://github.com/owncloud/guests/issues/165)
- Fix style issue on set password page - [#161](https://github.com/owncloud/guests/issues/161)

## [0.4.2] - 2017-09-11

### Added

- Use new registration controller instead of lostpassword functionality [\#64](https://github.com/owncloud/guests/issues/64)
- Add build script [\#24](https://github.com/owncloud/guests/issues/24)

### Changed

- Improve naming of guest accounts [\#69](https://github.com/owncloud/guests/issues/69)
- Permit creation of guest users by their email address in the sharetabview. [\#64](https://github.com/owncloud/guests/issues/64)
- Actual username creation is performed in the backend (is the lowercase email address) [\#64](https://github.com/owncloud/guests/issues/64)

### Fixed

- Whitelist: move "," and core to backend, UI fixes [\#127](https://github.com/owncloud/guests/pull/127)
- Login as a guest is case sensitive [\#133](https://github.com/owncloud/guests/issues/133)
- No error message in UI when duplicate email is used [\#82](https://github.com/owncloud/guests/issues/82)
- Admin Settings fixes [\#107](https://github.com/owncloud/guests/issues/107), [\#113](https://github.com/owncloud/guests/issues/113)

## [0.4.1] - 2017-04-26

### Fixed

- Last release did not contain the mentioned fix due to packaging error

## [0.4] - 2017-04-25

### Fixed

- Fixed breakage when market app is installed

## [0.3] - 2017-04-20

### Added

- Better wording in invite email, user is hinted that he can use his email address to login so he doesn't need to memorize a username.
- Use shortlink in share notification and invite mail.
- Added translatable texts

### Changed

- Removed do not use warning from readme. This app is now considered early beta quality.

### Fixed

- Could not create a guest user as non-admin
- Fixed issue with inconsistent display of guest user's default permissions

## [0.2] - 2017-04-10

This release consists mostly of internal changes to adapt the guest app to ownCloud 10

### Changed

- First version of working jailing code (guest user can't navigate out of his home dir)

### Fixed

- Fixed bug where guest users couldn't be created if ownCloud is installed in a subdirectory
- E-Mail invite could not be sent with ownCloud 10

## 0.1 - 2017-03-27

### Added

- Core functionality

[Unreleased]: https://github.com/owncloud/guests/compare/v0.9.3...master
[0.9.3]: https://github.com/owncloud/guests/compare/v0.9.2...v0.9.3
[0.9.2]: https://github.com/owncloud/guests/compare/v0.9.1...v0.9.2
[0.9.1]: https://github.com/owncloud/guests/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/owncloud/guests/compare/v0.8.2...v0.9.0
[0.8.2]: https://github.com/owncloud/guests/compare/v0.8.1...v0.8.2
[0.8.1]: https://github.com/owncloud/guests/compare/v0.8.0...v0.8.1
[0.8.0]: https://github.com/owncloud/guests/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/owncloud/guests/compare/v0.6.2...v0.7.0
[0.6.2]: https://github.com/owncloud/guests/compare/v0.6.0...v0.6.2
[0.6.0]: https://github.com/owncloud/guests/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/owncloud/guests/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/owncloud/guests/compare/v.0.4...v0.4.1
[0.4]: https://github.com/owncloud/guests/compare/v0.3...v.0.4
[0.3]: https://github.com/owncloud/guests/compare/v0.2...v0.3
[0.2]: https://github.com/owncloud/guests/compare/v0.1...v0.2
