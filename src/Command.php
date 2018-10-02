<?php

namespace craft\fixfks;

use craft\helpers\MigrationHelper;

class Command extends \craft\db\Command
{
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        // Only add it if it doesn't exist yet
        if (!MigrationHelper::doesForeignKeyExist($table, $columns)) {
            return parent::addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
        }

        return $this;
    }
}
