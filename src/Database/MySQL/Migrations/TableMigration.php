<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Migrations;

use Gekko\Collections\Collection;
use Gekko\Database\Objects\ITable;
use Gekko\Database\Objects\IForeignKey;
use Gekko\Database\Migrations\ITableMigration;

class TableMigration implements ITableMigration
{
    public function migrate(?ITable $target, ?ITable $from = null) : string
    {
        if ($target === null && $from === null)
            throw new \Exception("Both \$target and \$from cannot be null");

        if ($from !== null && $target !== null && $from->name !== $target->name)
            throw new \Exception("Cannot make migration between different tables {$from->name} and {$target->name}");

        if ($target === null)
            return "DROP TABLE `{$from->name}`";

        $columnMigration = new \Gekko\Database\MySQL\Migrations\ColumnMigration($from !== null && $target !== null);
        $columns = [];

        foreach ($target->columns as $name => $column)
        {
            $fromColumn = $from !== null && $from->hasColumn($name) ? $from->column($name) : null;

            if ($fromColumn !== null && $column->equals($fromColumn))
                continue;

            $columns[] = $columnMigration->migrate($column, $fromColumn);
        }

        if ($from !== null)
        {
            foreach ($from->columns as $name => $column)
            {
                if ($target !== null && $target->hasColumn($name))
                    continue;
                $columns[] = $columnMigration->migrate(null, $column);
            }
        }

        $targetPks = [];

        if (count($target->primaryKeys) > 0)
        {
            $targetPks = Collection::of($target->primaryKeys)->select(function ($pk) {
                return $pk->name;
            })->toArray();
        }

        $fromPks = [];

        if ($from !== null && count($from->primaryKeys) > 0)
        {
            $fromPks = Collection::of($from->primaryKeys)->select(function ($pk) {
                return $pk->name;
            })->toArray();
        }

        $fks = [];

        foreach ($target->foreignKeys ?? [] as $foreignKey)
        {
            $fromFk = $from !== null && $from->foreignKeys !== null ? Collection::of($from->foreignKeys)->first(function (IForeignKey $fk) use ($foreignKey) {
                return $fk->equals($foreignKey);
            }) : null;

            // The FK is already present in the previous version, nothing to do with it
            if ($fromFk !== null)
                continue;

            $fromColumns = \implode("`, `", $foreignKey->fromColumns);
            $toColumns = \implode("`, `", $foreignKey->toColumns);

            
            if ($fromFk !== null)
            {
                $fks[] = "ALTER TABLE `{$target->name}` DROP CONSTRAINT `{$foreignKey->name()}`";
            }
            
            $fks[] = "ALTER TABLE `{$target->name}` ADD CONSTRAINT `{$foreignKey->name()}` FOREIGN KEY (`{$fromColumns}`) REFERENCES `{$foreignKey->toTable}` (`{$toColumns}`)";
        }

        foreach ($from->foreignKeys ?? [] as $foreignKey)
        {
            $targetFk = $target->foreignKeys !== null ? Collection::of($target->foreignKeys)->first(function (IForeignKey $fk) use ($foreignKey) {
                return $fk->equals($foreignKey);
            }) : null;

            if ($targetFk === null)
                $fks[] = "ALTER TABLE `{$target->name}` DROP CONSTRAINT {$foreignKey->name()}";
        }

        $script = "";

        if ($from === null)
        {
            $script = "CREATE TABLE IF NOT EXISTS `{$target->name}`(\n\t" . \implode(",\n\t", $columns) . "\n";
            
            if ($targetPks !== [])
                $script .= "\t, PRIMARY KEY (`" . implode("`, `", $targetPks) . "`)";
            
            $script .= ");";
        }
        else if (count($columns) > 0 || $targetPks !== $fromPks)
        {
            $script = "ALTER TABLE `{$target->name}`\n\t";
            
            if (count($columns) > 0);
            {
                $script .= \implode(",\n\t", $columns);
            }

            if ($targetPks !== $fromPks)
            {
                if (count($columns) > 0)
                    $script .= ", ";

                if ($fromPks !== [])
                    $script .= "DROP PRIMARY KEY, ";

                $script .= "ADD PRIMARY KEY (`" . implode("`, `", $targetPks) . "`);";
            }

            $script .= ";";
        }

        if (!empty($fks))
            $script .= "\n" . \implode(";\n", $fks) . ";";
        
        return $script;
    }
}