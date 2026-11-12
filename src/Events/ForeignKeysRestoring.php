<?php

declare(strict_types=1);

namespace CraftCms\FixFks\Events;

/**
 * Dispatched after Craft’s own foreign keys have been restored, while foreign key checks are still disabled.
 *
 * Listeners can restore their own foreign keys by adding them with `Schema::table()`.
 * Keys that already exist are skipped, so a plugin can call its install migration’s
 * `addForeignKeys()` method as-is:
 *
 * ```php
 * Event::listen(ForeignKeysRestoring::class, fn () => $this->createInstallMigration()?->addForeignKeys());
 * ```
 */
class ForeignKeysRestoring {}
