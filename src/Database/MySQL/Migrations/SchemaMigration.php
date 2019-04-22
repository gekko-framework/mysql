<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Migrations;

use Gekko\Database\Migrations\ISchemaMigration;
use Gekko\Database\Objects\ISchema;

class SchemaMigration implements ISchemaMigration
{
    public function migrate(?ISchema $target, ?ISchema $from = null) : string
    {
        if ($target === null && $from === null)
            throw new \Exception("Both \$target and \$from cannot be null");

        if ($from !== null && $target !== null && $from->name !== $target->name)
            throw new \Exception("Cannot make migration between different schemas {$from->name} and {$target->name}");

        if ($target === null)
            return "DROP SCHEMA `{$from->name}`";

        $tableMigration = new \Gekko\Database\MySQL\Migrations\TableMigration();
        $tables = [];

        foreach ($target->tables as $name => $table)
        {
            $fromTable = $from !== null && $from->hasTable($name) ? $from->table($name) : null;

            if ($fromTable !== null && $fromTable->equals($table))
                continue;

            $tables[] = $tableMigration->migrate($table, $fromTable);
        }

        if ($from !== null)
        {
            foreach ($from->tables as $name => $table)
            {
                if ($target !== null && $target->hasTable($name))
                    continue;

                $tables[] = $tableMigration->migrate(null, $table);
            }
        }

        if ($from === null)
            return "CREATE DATABASE IF NOT EXISTS `{$target->name}`; USE `{$target->name}`; " . implode("\n", $tables);
            
        return "USE `{$target->name}`; " . implode("\n", $tables);
    }
}