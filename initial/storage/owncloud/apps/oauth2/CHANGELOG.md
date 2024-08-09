# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.5.2] - 2021-11-02

- [#313](https://github.com/owncloud/oauth2/issues/313) - Add occ oauth2:list command
- [#315](https://github.com/owncloud/oauth2/issues/315) - Sanitize output of command oauth2:list-clients
- [#314](https://github.com/owncloud/oauth2/issues/314) - Add occ oauth2:modify-client command


## [0.5.1] - 2021-10-05

### Fixed

- Fix user lookup in authmodule [#307](https://github.com/owncloud/oauth2/issues/307)


## [0.5.0] - 2020-08-16

### Added

- Add support for trusted clients [#298](https://github.com/owncloud/oauth2/issues/298)
- Support setups with user login name != internal UID [#286](https://github.com/owncloud/oauth2/issues/286)
- Implement the oauth2 pkce extension [#276](https://github.com/owncloud/oauth2/issues/276)

### Changed

- Return proper error description for auth-core/refresh-token errors [#279](https://github.com/owncloud/oauth2/issues/279)
- Adjust PHP dependencies for composer 2.0 [#273](https://github.com/owncloud/oauth2/issues/273)

## [0.4.4] - 2020-03-03

### Fixed

- No exception in case the token is unknown and the openid connect app is enabled - [#255](https://github.com/owncloud/oauth2/issues/255)

## [0.4.3] - 2019-12-20

### Changed

- Improve adding a new client - [#237](https://github.com/owncloud/oauth2/issues/237)
- Drop PHP Support 7.0 - [#238](https://github.com/owncloud/oauth2/issues/238)

## [0.4.2] - 2019-11-21

### Changed

- Handle refresh token of a disabled user - [#225](https://github.com/owncloud/oauth2/issues/225)
- Use a WHATWG URL spec compliant URL parser - see https://url.spec.wha… - [#229](https://github.com/owncloud/oauth2/issues/229)
- Remove back button from error page, add logging and return proper err… - [#220](https://github.com/owncloud/oauth2/issues/220)
- Check user status - [#209](https://github.com/owncloud/oauth2/issues/209)

## [0.4.1] - 2019-05-15

### Fixed

- The redirect_uri needs to be encoded in the logout url - [#197](https://github.com/owncloud/oauth2/issues/197)

## [0.4.0] - 2019-02-13

### Added

- Display currently logged in user display name in authorization page - [#188](https://github.com/owncloud/oauth2/issues/188)
- Added option for switching user in authorization page - [#188](https://github.com/owncloud/oauth2/issues/188)

## [0.3.0] - 2018-12-07

### Added

- Added autofocus to buttons - [#173](https://github.com/owncloud/oauth2/issues/173)
- Add occ oauth2:remove-client to remove a client by its id - [#178](https://github.com/owncloud/oauth2/issues/178)
- Support for PHP 7.1 and 7.2 - [#161](https://github.com/owncloud/oauth2/issues/161)
- Support for implicit grant - [#166](https://github.com/owncloud/oauth2/issues/166)

### Changed

- Set max version to 10 because core platform is switching to Semver - [#180](https://github.com/owncloud/oauth2/issues/180)

### Fixed

- Don't fail if the client was already added - [#176](https://github.com/owncloud/oauth2/issues/176)
- Use markdown properly in description - [#153](https://github.com/owncloud/oauth2/issues/153)

## [0.2.3] - 2018-08-09

### Fixed

- Erroneous ownCloud 2.4.2 client behavior causing service interruptions [#145](https://github.com/owncloud/oauth2/pull/145)
- Initialization for password-less sessions [#129](https://github.com/owncloud/oauth2/pull/129)

## [0.2.2]

### Added

- OpenID Connect UserInfo endpoint [#115](https://github.com/owncloud/oauth2/pull/115)

### Fixed

- Expired token causing server failures  [#118](https://github.com/owncloud/oauth2/pull/118)

## [0.2.1] - 2017-11-28

### Fixed

- OAuth app blocking public uploads [#100](https://github.com/owncloud/oauth2/pull/100)

## [0.2.0] - 2017-10-13

### Added

- Oracle and 4-byte MySQL support - [#42](https://github.com/owncloud/oauth2/pull/42)
- Predefined client ids for mobile and desktop clients - [#38](https://github.com/owncloud/oauth2/pull/38)

### Changed

- Allow multiple tokens per client - [#65](https://github.com/owncloud/oauth2/pull/65)

### Fixed

- Security Hardening - [#71](https://github.com/owncloud/oauth2/pull/71)
- Verify Bearer token even if the session is still valid - [#53](https://github.com/owncloud/oauth2/pull/53)
- Use displayname on switch user screen - [#90](https://github.com/owncloud/oauth2/pull/90)

[Unreleased]: https://github.com/owncloud/oauth2/compare/v0.5.2...master
[0.5.2]: https://github.com/owncloud/oauth2/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/owncloud/oauth2/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/owncloud/oauth2/compare/v0.4.4...v0.5.0
[0.4.4]: https://github.com/owncloud/oauth2/compare/v0.4.3...v0.4.4
[0.4.3]: https://github.com/owncloud/oauth2/compare/v0.4.2...v0.4.3
[0.4.2]: https://github.com/owncloud/oauth2/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/owncloud/oauth2/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/owncloud/oauth2/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/owncloud/oauth2/compare/v0.2.3...v0.3.0
[0.2.3]: https://github.com/owncloud/oauth2/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/owncloud/oauth2/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/owncloud/oauth2/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/owncloud/oauth2/compare/v0.1.0...v0.2.0
