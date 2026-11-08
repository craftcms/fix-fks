# Fix FKs utility for Craft CMS

This plugin restores foreign key constraints that are missing from your Craft database, such as after restoring a backup that was made without them, or after importing a database dump by hand.

## Requirements

Fix FKs 3.x requires Craft CMS 6.0 or later and PHP 8.5 or later. It works with MySQL, PostgreSQL and SQLite.

For Craft 4 and 5, use Fix FKs 2.x.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s control panel and search for “Fix FKs”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require craftcms/fix-fks

# tell Craft to install the plugin
php artisan craft:plugin:install fix-fks
```

## Usage

Back up your database first. Then go to Utilities → Fix FKs in the control panel and press “Restore foreign keys”.

The utility adds each of Craft’s foreign keys that is missing, and leaves existing ones alone. It reports how many keys it restored, and lists any it couldn’t add, with details in the logs.

Rows that point to records that no longer exist don’t stop a key from being restored:

- On MySQL, the key is added without checking existing rows, as MySQL does when foreign key checks are disabled.
- On PostgreSQL, the key is added as `NOT VALID`, then validated. If existing rows break it, it stays unvalidated: it still applies to new and updated rows.

Run `php artisan craft:gc:run` afterwards to delete rows that point to deleted records.

On SQLite, adding a foreign key rebuilds the table. The utility recreates the table’s indexes and triggers as they were.

## Restoring plugin foreign keys

Plugins can restore their own foreign keys by listening for `CraftCms\FixFks\Events\ForeignKeysRestoring`. It’s dispatched after Craft’s keys are restored. Keys the listener adds with `Schema::table()` are only added if they’re missing, so a plugin can call its install migration’s `addForeignKeys()` method as-is:

```php
use CraftCms\FixFks\Events\ForeignKeysRestoring;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(ForeignKeysRestoring::class, fn () => $this->createInstallMigration()?->addForeignKeys());
}
```

The listener only runs when Fix FKs is installed, so the plugin doesn’t need to require it.
