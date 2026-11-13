# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this extension follows
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.0.0] - 2026-11-13

### Breaking

- Requires TYPO3 12.4, 13.4 or 14.3 and PHP 8.1 or newer. Support for TYPO3 11
  and PHP 7.4 is dropped, including the RequireJS module.

### Added

- Support for TYPO3 14 and PHP 8.5, including a TYPO3 14 module icon and
  styles for the mail list that TYPO3 14 no longer ships
  ([#44](https://github.com/xima-media/xima-typo3-mailcatcher/pull/44)).

### Changed

- The extension declares its version in `composer.json`, so TYPO3 14 no longer
  reports `ext_emconf.php` as deprecated.
- The Composer and TER packages no longer ship development files such as
  `.ddev/`, `.github/`, `Tests/` and tool configurations.
- Development stack: PHPUnit 10 to 13, Playwright acceptance tests instead of
  Codeception, static code analysis via `composer ci:sca`, and a CI matrix for
  TYPO3 12.4, 13.4 and 14.3 on PHP 8.1 to 8.5
  ([#47](https://github.com/xima-media/xima-typo3-mailcatcher/pull/47)).
- The README describes features, requirements and the DDEV setup, with a
  TYPO3 14 screenshot of the backend module
  ([#51](https://github.com/xima-media/xima-typo3-mailcatcher/pull/51)).
- Security issues are reported to the TYPO3 Security Team
  ([#45](https://github.com/xima-media/xima-typo3-mailcatcher/pull/45)).

### Fixed

- Mails whose headers are not in the order To, From, Subject (e.g. from
  EXT:form finishers or EXT:felogin) were merged into the previous mail and
  lost when the log was emptied. The mbox log is now split independent of
  header order
  ([#46](https://github.com/xima-media/xima-typo3-mailcatcher/pull/46)).
- An inline forwarded mail or a `message/rfc822` attachment no longer shows up
  as an extra mail
  ([#50](https://github.com/xima-media/xima-typo3-mailcatcher/pull/50)).
