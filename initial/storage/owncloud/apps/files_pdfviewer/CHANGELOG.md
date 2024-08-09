# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [1.0.1] - 2021-10-05

### Fixes

- Links doesnt come up in new Tab or Window [#296](https://github.com/owncloud/files_pdfviewer/issues/296)
- [Android] links don't work in pdf_viewer v1.0.0rc1 [#298](https://github.com/owncloud/files_pdfviewer/issues/298)
- Menu bar is not translated [#305](https://github.com/owncloud/files_pdfviewer/issues/305)


## [1.0.0] - 2021-08-05

### Changed
- Update mozilla/pdfj.js lib to 2.9 (support digital signatues) [#288](https://github.com/owncloud/files_pdfviewer/issues/288)
- Dropped support for IE11 and (old) non-chromium-based Edge

## [0.12.2] - 2021-07-20

### Fixed

- fix close l10n - [#285](https://github.com/owncloud/files_pdfviewer/issues/285)
- provide translations in app release - [#280](https://github.com/owncloud/files_pdfviewer/issues/280)
- Fix typo - [#271](https://github.com/owncloud/files_pdfviewer/issues/271)
- Added PublicPage and NoCSRFRequired to allow canDownload for users without a session (public share) - [#269](https://github.com/owncloud/files_pdfviewer/issues/269)

## [0.12.1] - 2021-04-12

### Fixed

- Fix PDF files not viewable in shared folders via a public link [#266](https://github.com/owncloud/files_pdfviewer/issues/266)

## [0.12.0] - 2021-04-08

### Changed

- Update pdfjs to 2.5  - [#228](https://github.com/owncloud/files_pdfviewer/issues/228)
- Add transifex - [#248](https://github.com/owncloud/files_pdfviewer/pull/248)

### Fixed

- Show Notification if downloading pdf is forbidden - [#234](https://github.com/owncloud/files_pdfviewer/issues/234)
- Enhance canDownload notification text - [#237](https://github.com/owncloud/files_pdfviewer/issues/237)
- CSS fix close button -  [#262](https://github.com/owncloud/files_pdfviewer/pull/262)
- Fix wrong locale - [#247](https://github.com/owncloud/files_pdfviewer/pull/247)


## [0.11.2] - 2020-08-04

### Fixed

- Fix load of character maps to allow proper rendering of some fonts - [#217](https://github.com/owncloud/files_pdfviewer/issues/217)

### Changed

- Update libraries

## [0.11.1] - 2019-12-06

### Changed

- Drop PHP 7.0 - [#198](https://github.com/owncloud/files_pdfviewer/issues/198)
- Allow opening pdf attachements - [#196](https://github.com/owncloud/files_pdfviewer/issues/196)

### Fixed

- Fix close button for attachments - [#205](https://github.com/owncloud/files_pdfviewer/issues/205)

## [0.11.0] - 2019-04-12

### Changed

- Update pdfjs to 1.10 - [#177](https://github.com/owncloud/files_pdfviewer/pull/177)

## [0.10.0] - 2018-11-30

### Added

- add PHP 7.2 to stable10 branch - [#159](https://github.com/owncloud/files_pdfviewer/issues/159)

### Changed

- set max version to 10 because core platform switches to Semver


## [0.9.0] - 2018-07-19

### Added
- Apply current locale to PDF viewer - [#149](https://github.com/owncloud/files_pdfviewer/pull/149)

### Changed
- Update pdfjs to the most recent stable 1.9.426 - [#146](https://github.com/owncloud/files_pdfviewer/issues/146) [#152](https://github.com/owncloud/files_pdfviewer/issues/152)

### Removed
- Removed IE8 support - [#149](https://github.com/owncloud/files_pdfviewer/pull/149)
- Removed "Open document" from within the viewer - [#149](https://github.com/owncloud/files_pdfviewer/pull/149)

### Fixed
- Fix missing tool buttons - [#116](https://github.com/owncloud/files_pdfviewer/issues/116)
- Fix closing of PDF viewer - [#142](https://github.com/owncloud/files_pdfviewer/issues/142)

## 0.8.2
### Changed
- First marketplace release


[Unreleased]: https://github.com/owncloud/files_pdfviewer/compare/v1.0.0..master
[1.0.1]: https://github.com/owncloud/files_pdfviewer/compare/v1.0.0..v1.0.1
[1.0.0]: https://github.com/owncloud/files_pdfviewer/compare/v0.12.2..v1.0.0
[0.12.2]: https://github.com/owncloud/files_pdfviewer/compare/v0.12.1..v0.12.2
[0.12.1]: https://github.com/owncloud/files_pdfviewer/compare/v0.12.0..v0.12.1
[0.12.0]: https://github.com/owncloud/files_pdfviewer/compare/v0.11.2..v0.12.0
[0.11.2]: https://github.com/owncloud/files_pdfviewer/compare/v0.11.1..v0.11.2
[0.11.1]: https://github.com/owncloud/files_pdfviewer/compare/v0.11.0..v0.11.1
[0.11.0]: https://github.com/owncloud/files_pdfviewer/compare/v0.10.0..v0.11.0
[0.10.0]: https://github.com/owncloud/files_pdfviewer/compare/v0.9.0..v0.10.0
[0.9.0]: https://github.com/owncloud/files_pdfviewer/compare/v0.8.2..v0.9.0
