# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [1.2.0]

### Changed
- Update dependencies - [#70](https://github.com/owncloud/files_external_dropbox/issues/70)
- Composer dependency bumps 20190730 - [#62](https://github.com/owncloud/files_external_dropbox/issues/62)
- Composer dependency bumps 20190529 - [#61](https://github.com/owncloud/files_external_dropbox/issues/61)
- Drop PHP 5.6 - [#55](https://github.com/owncloud/files_external_dropbox/issues/55)

## [1.1.0] - 2018-12-18

### Changed

- Set max-version to 10 because platform is switching to Semver

### Fixed

- Fix packaging to not contain VCS files - [#41](https://github.com/owncloud/files_external_dropbox/pull/41)
- Fix delete functions by invalidating stat cache after successful operation - [#32](https://github.com/owncloud/files_external_dropbox/issues/32)
- Override third party libraries to check for filename casing - [#28](https://github.com/owncloud/files_external_dropbox/issues/28)

## [1.0.1] - 2018-02-08
### Known Issues

- File and folder names are converted to lowercase [#27](https://github.com/owncloud/files_external_dropbox/issues/27)

### Changed

- Use caching mechanism for performance improvements [#29](https://github.com/owncloud/files_external_dropbox/pull/29)
- Use v2 in label to avoid confusion with v1 [#11](https://github.com/owncloud/files_external_dropbox/pull/11)

### Fixed

- Compatibility with PHP 5.6 [#15](https://github.com/owncloud/files_external_dropbox/pull/15)
- Dropbox setup not working in certain circumstances [#21](https://github.com/owncloud/files_external_dropbox/pull/21) [#24](https://github.com/owncloud/files_external_dropbox/pull/24/files)

## [1.0.0] - 2017-10-02

Initial release of the application

[1.2.0]: https://github.com/owncloud/files_external_dropbox/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/owncloud/files_external_dropbox/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/owncloud/files_external_dropbox/compare/v1.0.0...v1.0.1
