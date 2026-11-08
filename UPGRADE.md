# Upgrading to Fix FKs 3.0 (Craft 6)

Fix FKs 3.0 is a native Craft 6 plugin. This guide lists what changed from 2.x.

## Requirements

- Craft CMS 6.0 or later
- PHP 8.5 or later
- The `craftcms/yii2-adapter` package is not needed.

## Upgrade steps

1. Upgrade your project to Craft 6 first.
2. Update the plugin: `composer require craftcms/fix-fks:^3.0`.

The plugin has no settings or database tables, so there’s nothing else to migrate.

## Breaking changes

### Restoring plugin foreign keys

The Yii event on the controller is replaced with a Laravel event.

```php
// Before
use craft\fixfks\controllers\RestoreController;
use yii\base\Event;

Event::on(RestoreController::class, RestoreController::EVENT_AFTER_RESTORE_FKS, function() {
    (new Install())->addForeignKeys();
});

// After
use CraftCms\FixFks\Events\ForeignKeysRestoring;
use Illuminate\Support\Facades\Event;

Event::listen(ForeignKeysRestoring::class, fn () => $this->createInstallMigration()?->addForeignKeys());
```

Listeners no longer need to avoid adding keys that already exist: keys added with `Schema::table()` while the event is dispatched are skipped when the table already has a foreign key on the same columns.

### Classes and namespaces

| Before | After |
| --- | --- |
| `craft\fixfks\Plugin` | `CraftCms\FixFks\Plugin` |
| `craft\fixfks\Utility` | `CraftCms\FixFks\Utilities\FixFks` |
| `craft\fixfks\controllers\RestoreController` | `CraftCms\FixFks\Http\Controllers\RestoreForeignKeysController` |
| `craft\fixfks\controllers\RestoreController::EVENT_AFTER_RESTORE_FKS` | `CraftCms\FixFks\Events\ForeignKeysRestoring` |
| `craft\fixfks\Command` | Removed. Use `CraftCms\FixFks\ForeignKeyRestorer::restore()`. |

The `fix-fks/restore` action URL is unchanged.
