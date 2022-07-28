# Changelog

All notable changes to `alleyinteractive/composer-wordpress-autoloader` will be
documented in this file.

## Unreleased

## v0.5.0

- Simplify injection of autoloader.
- Inject autoloader by default to `vendor/autoload.php`.

## v0.4.1

### Updated

* Fix Composer Injection to `vendor/autoload.php` in https://github.com/alleyinteractive/composer-wordpress-autoloader/pull/10

## v0.4.0

### Added

- Bump alleyinteractive/wordpress-autoloader to 0.2 by @srtfisher in https://github.com/alleyinteractive/composer-wordpress-autoloader/pull/7
- Supports PHP 8.1

## 0.3.0

- Remove specific Composer version dependency.

## 0.2.0

- Updates autoloader to use non-hard-coded paths.
- Adds support for dependencies to autoload files as well, fixes [#3](https://github.com/alleyinteractive/composer-wordpress-autoloader/issues/3).

## 0.1.0

- Initial release.
