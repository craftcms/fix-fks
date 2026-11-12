<?php

declare(strict_types=1);

use CraftCms\FixFks\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class)->in('Feature');

/**
 * Returns the columns of each foreign key on a table.
 *
 * @return list<string>
 */
function foreignKeyColumns(string $table): array
{
    return array_map(fn (array $foreignKey) => implode(',', $foreignKey['columns']), Schema::getForeignKeys($table));
}

/**
 * Counts the foreign keys on every table in the database.
 */
function foreignKeyCount(): int
{
    return collect(Schema::getTableListing(schemaQualified: false))
        ->sum(fn (string $table) => count(Schema::getForeignKeys($table)));
}
