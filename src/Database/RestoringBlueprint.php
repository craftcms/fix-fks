<?php

declare(strict_types=1);

namespace CraftCms\FixFks\Database;

use Closure;
use CraftCms\FixFks\ForeignKeyRestorer;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Fluent;
use Override;

/**
 * A blueprint that only adds foreign keys that are missing, and records which ones it added.
 *
 * @internal
 */
class RestoringBlueprint extends Blueprint
{
    public function __construct(
        Connection $connection,
        string $table,
        ?Closure $callback,
        private readonly ForeignKeyRestorer $restorer,
    ) {
        parent::__construct($connection, $table, $callback);
    }

    #[Override]
    public function foreign($columns, $name = null)
    {
        $columns = (array) $columns;

        if ($this->hasForeignKey($columns)) {
            // Not added to the blueprint’s commands, so nothing gets built for it
            return new ForeignKeyDefinition(['name' => 'foreign', 'columns' => $columns]);
        }

        $foreignKey = parent::foreign($columns, $name);

        if ($this->connection instanceof PostgresConnection) {
            // Add it without checking existing rows, as MySQL does with foreign key checks disabled,
            // so orphaned rows can’t keep the key from being restored. It gets validated after.
            $foreignKey->set('notValid', true);
        }

        return $foreignKey;
    }

    #[Override]
    public function build()
    {
        $commands = array_filter($this->commands, fn (Fluent $command) => $command->get('name') === 'foreign');
        $foreignKeys = array_values(array_map(
            fn (Fluent $command) => sprintf('%s.%s', $this->table, implode(', ', $command->get('columns'))),
            $commands,
        ));

        if ($foreignKeys === []) {
            parent::build();

            return;
        }

        // SQLite adds foreign keys by rebuilding the table
        $sqliteObjects = $this->connection instanceof SQLiteConnection ? $this->sqliteObjects() : [];

        try {
            if ($this->connection instanceof PostgresConnection) {
                // In its own transaction, so a key that can’t be added doesn’t abort one the caller started
                $this->connection->transaction(fn () => parent::build());
            } else {
                parent::build();
            }
        } catch (QueryException $e) {
            report($e);
            array_walk($foreignKeys, fn (string $foreignKey) => $this->restorer->recordFailed($foreignKey));

            return;
        }

        if ($this->connection instanceof PostgresConnection) {
            $this->validateForeignKeys($commands);
        }

        // The rebuild recreates indexes from their columns, so expression indexes keep their name but lose the expression
        if ($sqliteObjects !== []) {
            $rebuiltObjects = $this->sqliteObjects();

            foreach ($sqliteObjects as $name => $object) {
                if (($rebuiltObjects[$name]->sql ?? null) === $object->sql) {
                    continue;
                }

                if (isset($rebuiltObjects[$name])) {
                    $this->connection->statement(sprintf('drop %s %s', $object->type, $this->connection->getSchemaGrammar()->wrap($name)));
                }

                $this->connection->statement($object->sql);
            }
        }

        array_walk($foreignKeys, fn (string $foreignKey) => $this->restorer->recordRestored($foreignKey));
    }

    /**
     * Validates foreign keys added with `NOT VALID`, leaving any that existing rows violate unvalidated.
     *
     * @param  array<Fluent>  $commands
     */
    private function validateForeignKeys(array $commands): void
    {
        $grammar = $this->connection->getSchemaGrammar();

        foreach ($commands as $command) {
            try {
                // In its own transaction, so a failure can’t abort one the caller started
                $this->connection->transaction(fn () => $this->connection->statement(sprintf(
                    'alter table %s validate constraint %s',
                    $grammar->wrapTable($this),
                    $grammar->wrap($command->get('index')),
                )));
            } catch (QueryException) {
                // Rows reference missing records. The key still applies to new and updated rows.
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasForeignKey(array $columns): bool
    {
        $columns = array_map(strtolower(...), $columns);

        foreach ($this->connection->getSchemaBuilder()->getForeignKeys($this->table) as $foreignKey) {
            if (array_map(strtolower(...), $foreignKey['columns']) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the table’s indexes and triggers, indexed by name.
     *
     * @return array<string, object{type: string, sql: string}>
     */
    private function sqliteObjects(): array
    {
        if (! $this->connection instanceof SQLiteConnection) {
            return [];
        }

        $rows = $this->connection->select(
            "select name, type, sql from sqlite_master where type in ('index', 'trigger') and tbl_name = ? and sql is not null",
            [$this->connection->getTablePrefix().$this->table],
        );

        return array_column($rows, null, 'name');
    }
}
