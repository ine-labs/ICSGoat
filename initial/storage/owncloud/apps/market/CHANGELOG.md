# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.6.3] - 2022-02-16

### Fixed

- replace marketplace storage urls in csp - [#913](https://github.com/owncloud/market/pull/913)


## [0.6.2] - 2021-12-29

### Fixed

- Fix issues when the market API key is an empty string - [#870](https://github.com/owncloud/market/issues/870)

### Changed

- Change way adding nav entry to allow l10n - [#864](https://github.com/owncloud/market/issues/864)
- drop PHP 7.2 support - [#773](https://github.com/owncloud/market/issues/773)

## [0.6.1] - 2021-06-18

### Fixed

- Use relative url instead of absoulute url - [#650](https://github.com/owncloud/market/issues/650)

## [0.6.0] - 2020-07-10

### Removed

- Drop login button - [#573](https://github.com/owncloud/market/issues/573)
- Drop start trial functionality - [#572](https://github.com/owncloud/market/issues/572)

### Changed

- Set owncloud min-version to 10.5
- Only enable enterprise_key for versions < 10.5.0
- Bump libraries

### Security

- [Snyk] Security upgrade easygettext from 2.7.0 to 2.8.0 - [#533](https://github.com/owncloud/market/issues/533)
- [Security] Bump lodash from 4.17.11 to 4.17.15 - [#518](https://github.com/owncloud/market/issues/518)
- [Security] Bump https-proxy-agent from 2.2.1 to 2.2.4 - [#505](https://github.com/owncloud/market/issues/505)
- [Security] Bump mixin-deep from 1.3.1 to 1.3.2 - [#497](https://github.com/owncloud/market/issues/497)
- [Security] Bump jquery from 3.4.1 to 3.5.0 - [#556](https://github.com/owncloud/market/issues/556)
- [Synk] Security upgrade snyk from 1.269.0 to 1.290.1 - [#527](https://github.com/owncloud/market/issues/527)
- [Synk] Security upgrade node-sass from 4.12.0 to 4.13.1 - [#523](https://github.com/owncloud/market/issues/523)
- [Synk] Security upgrade sass-loader from 6.0.6 to 6.0.7 - [#524](https://github.com/owncloud/market/issues/524)

## [0.5.1] - 2020-07-28

### Added

- Notification about new trial - [#586](https://github.com/owncloud/market/issues/586)

## [0.5.0] - 2019-06-24

### Changed
- Provided information on how/where to retrieve API keys for the market app [#468](https://github.com/owncloud/market/pull/468)
- Library updates [#465](https://github.com/owncloud/market/pull/465) [#466](https://github.com/owncloud/market/pull/466) [#467](https://github.com/owncloud/market/pull/467) [#474](https://github.com/owncloud/market/pull/474) [#476](https://github.com/owncloud/market/pull/476) [#478](https://github.com/owncloud/market/pull/478) [#482](https://github.com/owncloud/market/pull/482)

## [0.4.0] - 2019-03-14

### Added

- Login directly from market-app to auto-install api-key - [#443](https://github.com/owncloud/market/issues/443)

### Changed

- Bump npm-watch from 0.1.9 to 0.5.0 - [#429](https://github.com/owncloud/market/issues/429)
- Bump vue-router from 2.7.0 to 3.0.2 - [#431](https://github.com/owncloud/market/issues/431)
- Bump vue-gettext from 2.0.23 to 2.1.2 - [#440](https://github.com/owncloud/market/issues/440)
- Bump vue and vue-template-compiler - [#428](https://github.com/owncloud/market/issues/428)
- Bump uikit from 3.0.0-beta.34 to 3.0.0-rc.26 - [#441](https://github.com/owncloud/market/issues/441)
- Bump uglify-js from 3.1.3 to 3.4.9 - [#394](https://github.com/owncloud/market/issues/394)
- Library updates to resolve vulnerabilities - [#377](https://github.com/owncloud/market/issues/377) [#445](https://github.com/owncloud/market/issues/445)

### Fixed

- Hide api key in xhr-responses if key is configured in config.php - [#454](https://github.com/owncloud/market/issues/454)
- Hide 'Edit/Add API Key' button if not changeable - [#185](https://github.com/owncloud/market/issues/185)
- Fix "Logged In" is shown despite being logged out - [#450](https://github.com/owncloud/market/issues/450)
- Fix typo "Logged In" - [#448](https://github.com/owncloud/market/issues/448)
- Prevent uninstalling market app from within market app - [#145](https://github.com/owncloud/market/issues/145)

## [0.3.0] - 2018-12-20

### Changed

- Set max version to 10.1 because core platform is switching to Semver
- Admins can now choose between minor or major update of an app - [#391](https://github.com/owncloud/market/issues/391)
- Bugfix/update dependencies breaking - [#407](https://github.com/owncloud/market/issues/407) [#408](https://github.com/owncloud/market/issues/408)
- Bump jakub-onderka/php-console-highlighter from 0.3.2 to 0.4 - [#392](https://github.com/owncloud/market/issues/392)
- Bump constantinople from 3.0.2 to 3.1.2 - [#412](https://github.com/owncloud/market/issues/412)

### Fixed

- Fix "--all" switch for occ command - [#388](https://github.com/owncloud/market/issues/388)
- Set max node version - [#381](https://github.com/owncloud/market/issues/381)

## [0.2.5] - 2018-07-25

### Fixed

- Rebuild top-left navigation if app was (de)installed - [#359](https://github.com/owncloud/market/issues/359)
- Check license of latest marketplace release - [#362](https://github.com/owncloud/market/pull/362)

## [0.2.4] - 2018-04-17

### Fixed
- Automatic cache invalidation when starting a enterprise trail [#282](https://github.com/owncloud/market/pull/282)
- Prevent update notifications for uninstalled apps [#285](https://github.com/owncloud/market/pull/285)

## [0.2.3] - 2017-11-14
### Fixed

- Show more detailed update information - [#159](https://github.com/owncloud/market/pull/159)
- Darken card box-shadow - [#136](https://github.com/owncloud/market/issues/136)
- Handle cluster setups better - [#125](https://github.com/owncloud/market/issues/125) [#184](https://github.com/owncloud/market/pull/184)

## [0.2.2] - 2017-09-15
### Added

- Added market:uninstall command - [#125](https://github.com/owncloud/market/pull/125)
- Added background job to notify admins about app updates - [#108](https://github.com/owncloud/market/pull/108)

### Changed

- `occ market:list` will return a alphabetical sorted list - [#122](https://github.com/owncloud/market/pull/112)
- `occ market` commands will return non-zero exit codes on failure - [#143](https://github.com/owncloud/market/pull/143)
- Provide more detailed information when marketplace could not be reached - [#141](https://github.com/owncloud/market/pull/141)


### Fixed

- Better handling for cluster setups - [#125](https://github.com/owncloud/market/pull/125)
- Only show enterprise trail button when no license key is set - [#142](https://github.com/owncloud/market/pull/142)
- Top right menu will no longer be condensed - [#149](https://github.com/owncloud/market/pull/149)
- Only show links to publisher pages that are active - [#157](https://github.com/owncloud/market/pull/157)

## [0.2.1] - 2017-07-06

## Added

- Ability to start an enterprise trail from within owncloud - [#107](https://github.com/owncloud/market/issues/107)

## [0.2.0] - 2017-06-30

### Added

- Checking if internet connection is disabled for owncloud - [#91](https://github.com/owncloud/market/pull/91)
- Ability to download bundles - [#89](https://github.com/owncloud/market/pull/89)

### Changed

- If apps are not downloadable, a link to marketplace is provided - [#93](https://github.com/owncloud/market/pull/93)

### Fixed

- Translations have been updated - [#75](https://github.com/owncloud/market/pull/78)
- Erroneous sorting of releases - [#90](https://github.com/owncloud/market/pull/90)

## [0.1.0] - 2017-06-23

### Fixed

- Skip migrations when reinstalling missing code - [#76](https://github.com/owncloud/market/issues/76)
- Reset overwritten core css styles - [#73](https://github.com/owncloud/market/issues/73)

[Unreleased]: https://github.com/owncloud/market/compare/v0.6.3...master
[0.6.3]: https://github.com/owncloud/market/compare/v0.6.2...v0.6.3
[0.6.2]: https://github.com/owncloud/market/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/owncloud/market/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/owncloud/market/compare/v0.5.1...v0.6.0
[0.5.1]: https://github.com/owncloud/market/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/owncloud/market/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/owncloud/market/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/owncloud/market/compare/v0.2.5...v0.3.0
[0.2.5]: https://github.com/owncloud/market/compare/v0.2.4...v0.2.5
[0.2.4]: https://github.com/owncloud/market/compare/v0.2.3...v0.2.4
[0.2.3]: https://github.com/owncloud/market/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/owncloud/market/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/owncloud/market/compare/v0.2.0...v0.2.1
