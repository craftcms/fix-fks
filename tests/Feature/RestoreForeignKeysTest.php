<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\FixFks\Events\ForeignKeysRestoring;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

const RESTORE_URL = '/admin/actions/fix-fks/restore';

it('requires a login', function () {
    postJson(RESTORE_URL)->assertUnauthorized();
});

it('requires access to the utility', function () {
    // Solo has a single user
    Edition::set(Edition::Pro);
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    postJson(RESTORE_URL)->assertForbidden();
});

it('restores a missing Craft foreign key', function () {
    actingAs(User::find()->admin()->one());
    $count = foreignKeyCount();
    Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->dropForeign(['sectionId']));

    expect(foreignKeyColumns(Table::ENTRIES))->not->toContain('sectionId');

    postJson(RESTORE_URL)
        ->assertOk()
        ->assertJsonPath('message', 'Restored 1 foreign key.');

    expect(foreignKeyColumns(Table::ENTRIES))->toContain('sectionId')
        ->and(foreignKeyCount())->toBe($count);

    if (DB::connection() instanceof PostgresConnection) {
        expect(DB::scalar("select convalidated from pg_constraint where conname = 'entries_sectionid_foreign'"))->toBeTrue();
    }
});

it('leaves existing foreign keys alone', function () {
    actingAs(User::find()->admin()->one());
    $count = foreignKeyCount();

    postJson(RESTORE_URL)
        ->assertOk()
        ->assertJsonPath('message', 'No foreign keys were missing.');

    expect(foreignKeyCount())->toBe($count);
});

it('restores a foreign key even when rows reference missing records', function () {
    actingAs(User::find()->admin()->one());
    Schema::table(Table::CHANGEDATTRIBUTES, fn (Blueprint $table) => $table->dropForeign(['userId']));
    DB::table(Table::CHANGEDATTRIBUTES)->insert([
        'elementId' => User::find()->admin()->one()->id,
        'siteId' => DB::table(Table::SITES)->value('id'),
        'attribute' => 'title',
        'dateUpdated' => now(),
        'propagated' => false,
        'userId' => 999999,
    ]);

    postJson(RESTORE_URL)
        ->assertOk()
        ->assertJsonPath('message', 'Restored 1 foreign key.');

    expect(foreignKeyColumns(Table::CHANGEDATTRIBUTES))->toContain('userId');
});

it('keeps SQLite expression indexes when it rebuilds a table', function () {
    actingAs(User::find()->admin()->one());
    Schema::table(Table::ELEMENTS_SITES, fn (Blueprint $table) => $table->dropForeign(['siteId']));
    // Dropping the key rebuilt the table too, which lost the index’s expression
    Schema::table(Table::ELEMENTS_SITES, fn (Blueprint $table) => $table->dropIndex('sites_uri_siteid_index'));
    Schema::table(Table::ELEMENTS_SITES, fn (Blueprint $table) => $table->rawIndex('lower("uri"), "siteId"', 'sites_uri_siteid_index'));

    postJson(RESTORE_URL)->assertOk();

    expect(foreignKeyColumns(Table::ELEMENTS_SITES))->toContain('siteId')
        ->and(DB::scalar("select sql from sqlite_master where name = 'sites_uri_siteid_index'"))->toContain('lower("uri")');
})->skip(fn () => ! DB::connection() instanceof SQLiteConnection, 'SQLite only');

it('lets plugins restore their own foreign keys', function () {
    actingAs(User::find()->admin()->one());
    Schema::create('fixfks_test', function (Blueprint $table) {
        $table->id();
        $table->integer('elementId');
    });

    Event::listen(ForeignKeysRestoring::class, function () {
        Schema::table('fixfks_test', fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
    });

    postJson(RESTORE_URL)
        ->assertOk()
        ->assertJsonPath('message', 'Restored 1 foreign key.');

    expect(foreignKeyColumns('fixfks_test'))->toBe(['elementId']);

    // Running it again doesn’t add the plugin’s key twice
    postJson(RESTORE_URL)->assertJsonPath('message', 'No foreign keys were missing.');

    expect(foreignKeyColumns('fixfks_test'))->toBe(['elementId']);
});
