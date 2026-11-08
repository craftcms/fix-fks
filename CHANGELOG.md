# Release Notes for Fix FKs

## Unreleased

- Fix FKs now requires Craft CMS 6.0 or later and PHP 8.5 or later, and runs natively on Craft 6 without the `craftcms/yii2-adapter` package.
- The plugin’s classes moved from the `craft\fixfks` namespace to `CraftCms\FixFks`. ([UPGRADE.md](UPGRADE.md))
- Added `CraftCms\FixFks\Events\ForeignKeysRestoring`, which replaces `craft\fixfks\controllers\RestoreController::EVENT_AFTER_RESTORE_FKS`.
- The utility now reports how many foreign keys it restored, and which ones it couldn’t restore.
- Foreign keys can now be restored on PostgreSQL when existing rows reference deleted records.
- Added SQLite support.
- Fixed a bug where database triggers could stay disabled on PostgreSQL if restoring foreign keys failed.

## 2.1.0 - 2024-03-19

- Added Craft 5 compatibility.

## 2.0.0 - 2022-05-03

### Added
- Added Craft 4 compatibility.

## 1.0.2.1 - 2018-10-02

### Fixed
- Fixed an error that would occur when running the utility. ([#4](https://github.com/craftcms/fix-fks/issues/4))

## 1.0.2 - 2018-09-04

### Changed
- Improved compatibility with Craft 3.0.23.

## 1.0.1 - 2018-06-18

### Fixed
- Fixed a deprecation error.

## 1.0.0

### Changed
- Initial release.
