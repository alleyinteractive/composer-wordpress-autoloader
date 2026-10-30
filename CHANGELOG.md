# Changelog

All notable changes to `alleyinteractive/composer-wordpress-autoloader` will be
documented in this file.

## Unreleased

## v1.4.0

### Changed

- Upgrade minimum PHP version to PHP 8.3, adding testing for PHP 8.5.
- Upgrade to PHPUnit 12.
- Add PHPStan at level max, with strict types and full type coverage across the plugin.
- `AutoloadGenerator::parseAutoloads()` was renamed to `parseWordPressAutoloads()` so it no longer overrides Composer's method with an incompatible return shape.

### Fixed

- Stop loading the project's autoloader inside the Composer process, which let project dependencies such as `symfony/console` replace Composer's own copies and caused fatal errors with other Composer plugins like Pest ([#23](https://github.com/alleyinteractive/composer-wordpress-autoloader/issues/23)).
- Packages that are not installed (such as metapackages) are skipped when reading `extra.wordpress-autoloader` rules.
- Misconfigured `extra.wordpress-autoloader` entries are ignored instead of causing errors.
- Injecting the autoloader no longer reports an error when `vendor/autoload.php` is already up to date, and the inject messages' typos are fixed.
- `composer clear-prepared-test` now clears the `vendor-dir` fixture's install.

## v1.3.0

### Changed

- Upgrade minimum PHP version to PHP 8.2.

## v1.2.0

### Changed

- Dropped PHP 8.0 support, added testing for 8.4. New minimum PHP version is 8.1.
- Dropped support for Composer 1.

## v1.1.0

### Changed

- Dropped PHP 7.4 support, adding PHP 8.2 and 8.3 support explicitly with testing.
- Added testing against Windows.

### Fixed

- Fixed issue with `wordpress-autoload.php` file not properly loading on Windows.

## v1.0.0

No changes, tagging a stable release.

## v0.8.0

- Automatically translate the `vendor-dir` and set the autoloaded files relative to the root directory of the project.

## v0.7.0

- Add APCu autoloader.
- Bump `alleyinteractive/wordpress-autoloader` to `v1.1.0`.

## v0.6.0

- Simplify injection of autoloader.
- Automatically load the autoloader inside of `vendor/autoload.php` without the
  need to load `vendor/wordpress-autoload.php` manually.

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
