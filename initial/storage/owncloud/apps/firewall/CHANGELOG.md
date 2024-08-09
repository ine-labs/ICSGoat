# Changelog

All notable changes to this app will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [2.10.3] - 2021-06-23

### Fixed

- Prevent regex filters from being rendered multiple times in UI - [#679](https://github.com/owncloud/firewall/issues/679)
- Convert regex rules to lowercase when checking mimetypes - [#678](https://github.com/owncloud/firewall/issues/678)
- Fix an issue where files could not be downloaded or viewed… - [#677](https://github.com/owncloud/firewall/issues/677)

## [2.10.2] - 2020-08-04

### Fixed

- Ignore individual chunks - [#650](https://github.com/owncloud/firewall/issues/650)

## [2.10.1] - 2020-07-06

### Changed   

- Set owncloud min-version to 10.5
- Remove `default_enable` - [#647](https://github.com/owncloud/firewall/issues/647)

## [2.10.0] - 2020-06-18

### Added

- Move to the new licensing management - [#640](https://github.com/owncloud/firewall/issues/640)

### Changed

- Bump libraries

## [2.9.0] - 2020-03-12

### Fixed

- Detect mimetype and filesize for public upload case properly - [#613](https://github.com/owncloud/firewall/issues/613)
- Do not update readonly config.php. Notify user about it - [#576](https://github.com/owncloud/firewall/issues/576)
- Detect latest iOS app - [#623](https://github.com/owncloud/firewall/issues/623)

### Changed

- Change 'IP Range' to 'Client IP Subnet' - [#596](https://github.com/owncloud/firewall/issues/596)

## [2.8.0] - 2019-03-05

### Changed

- Updated hoa library - [#566](https://github.com/owncloud/firewall/issues/566)

### Fixed

- Fix warning about deprecated unset cast with PHP 7.2 - [#566](https://github.com/owncloud/firewall/issues/566)

## [2.7.0] - 2018-11-30

### Changed

- Set max version to 10 for Semver

## [2.6.0] - 2018-07-03

### Changed

- Use info and warning log levels for firewall logging - [#404](https://github.com/owncloud/firewall/issues/404)

### Fixed

- File mimetype upload limiter does not work with chunked files - [#359](https://github.com/owncloud/firewall/issues/359)

## [2.5.0] - 2018-03-13

### Fixed

- Bump version compatibility with core ownCloud - [#458](https://github.com/owncloud/firewall/pull/458)
- Block previews - [#451](https://github.com/owncloud/firewall/issues/451)
- Fix encryption app compatibility - [#443](https://github.com/owncloud/firewall/issues/443)
- Remove unused use statements and sort - [#440](https://github.com/owncloud/firewall/issues/440)
- Avoid undefined index cidr6 in log - [#438](https://github.com/owncloud/firewall/issues/438)

## [2.4.2] - 2017-09-14

### Fixed

#### Rule Processing

- Check file uploadsize for MOVE and COPY operations [#411] (https://github.com/owncloud/firewall/issues/411)
- Filesize upload limiter does not work with chunked files [#356] (https://github.com/owncloud/firewall/issues/356)
- Filesize upload limiter does not work if client lies about upload size [#350] (https://github.com/owncloud/firewall/issues/350)
- Request type rule does not block WebDav Uploads using new chunking [#393] (https://github.com/owncloud/firewall/issues/393)
- Regex match to IP Range (IPv6) cannot be defined [#381] (https://github.com/owncloud/firewall/issues/381)
- IP Range rules with mixed IPv4 and IPv6 traffic [#373] (https://github.com/owncloud/firewall/issues/373)
- Request type & system file tag rules do not prevent the preview being shown [#308] (https://github.com/owncloud/firewall/issues/308)

#### UI Related

- Improve confusing log message when firewall blocks a request [#338] (https://github.com/owncloud/firewall/issues/338)
- Not enough explanation about logging function [#337] (https://github.com/owncloud/firewall/issues/337)
- Switching operator in regex rules duplicates the filters [#335] (https://github.com/owncloud/firewall/issues/335)
- Allow deletion of first firewall rule group = [#285] (https://github.com/owncloud/firewall/issues/285)
- Firewall rule groups explanation - [#282] )(https://github.com/owncloud/firewall/issues/282)
- Move firewall admin settings to 'Security' section [#273] (https://github.com/owncloud/firewall/issues/273)

#### App Build and Test

- Firewall doesn't work with stable10 core (include templates folder in appstore distribution) [#339] (https://github.com/owncloud/firewall/issues/339)
- Expand and enhance automated UI test environment - various

### Removed

- [major] Remove not-working subnet functionality until exact use case is decided [#385] (https://github.com/owncloud/firewall/issues/385)

## [2.4.1] - 2017-06-23

- Provide automated UI test environment - various
- Prepare for marketplace - [#303] (https://github.com/owncloud/firewall/pull/303)

[2.10.3]: https://github.com/owncloud/firewall/compare/v2.10.2...v2.10.3
[2.10.2]: https://github.com/owncloud/firewall/compare/v2.10.1...v2.10.2
[2.10.1]: https://github.com/owncloud/firewall/compare/v2.10.0...v2.10.1
[2.10.0]: https://github.com/owncloud/firewall/compare/v2.9.0...v2.10.0
[2.9.0]: https://github.com/owncloud/firewall/compare/v2.8.0...v2.9.0
[2.8.0]: https://github.com/owncloud/firewall/compare/v2.7.0...v2.8.0
[2.7.0]: https://github.com/owncloud/firewall/compare/v2.6.0...v2.7.0
[2.6.0]: https://github.com/owncloud/firewall/compare/v2.5.0...v2.6.0
[2.5.0]: https://github.com/owncloud/firewall/compare/v2.4.2...v2.5.0
[2.4.2]: https://github.com/owncloud/firewall/compare/v2.4.1...v2.4.2
[2.4.1]: https://github.com/owncloud/firewall/compare/v10.0.2...v2.4.1
