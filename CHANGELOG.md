# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2022-09-23
### Added
- Method `isExplicitlyNotAllowedFor()` that ignores rules for wildcard user-agent (`*`) and checks if some path is explicitly not allowed for a certain user-agent.

## [1.0.0] - 2022-09-22
### Changed
- Required PHP version is now 8.0.

### Added
- It now also parses `Sitemap:` lines. You can get all referenced sitemaps via the `sitemaps()` method of the `RobotsTxt` class.

## [0.1.2] - 2022-09-16
### Fixed
- Also allow usage of crwlr/url 1.0 as it's not a problem at all and the PHP version requirement of this package is still `^7.4|^8.0`.

## [0.1.1] - 2022-09-16
### Changed
- Upgraded crwlr/url package version constraint.
