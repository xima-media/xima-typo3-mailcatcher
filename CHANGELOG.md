# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); this extension follows
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Breaking

- Requires TYPO3 12.4, 13.4 or 14.3 and PHP 8.1 or newer. Support for TYPO3 11
  and PHP 7.4 is dropped, including the RequireJS module.

### Changed

- The extension declares its version in `composer.json`, so TYPO3 14 no longer
  reports `ext_emconf.php` as deprecated.
- The Composer and TER packages no longer ship development files such as
  `.ddev/`, `.github/`, `Tests/` and tool configurations.
