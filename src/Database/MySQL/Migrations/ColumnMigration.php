<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Migrations;

use Gekko\Database\Migrations\IColumnMigration;
use Gekko\Database\Objects\IColumn;

class ColumnMigration implements IColumnMigration
{
    /**
     * @var string If true it means the table the columns belong to is doing an ALTER TABLE
     */
    private $alter;

    public function __construct(bool $alter)
    {
        $this->alter = $alter;
    }

    public function migrate(?IColumn $target, ?IColumn $from = null) : string
    {
        if ($target === null && $from === null)
            throw new \Exception("Both \$target and \$from cannot be null");

        if ($from !== null && $target !== null && $from->name !== $target->name)
            throw new \Exception("Cannot make migration between different columns {$from->name} and {$target->name}");

        if ($target === null)
            return "DROP COLUMN `{$from->name}`";

        $mapper = new \Gekko\Types\Mappers\MySQLTypeMapper();

        $def = $from !== null ? "MODIFY COLUMN " : ($this->alter ? "ADD COLUMN " : "") ;

        return  $def
                . "`$target->name` "
                . $mapper->mapFrom($target->type) . $this->getModifiers($target)
                . ($target->nullable ? " NULL" : "")
                //. ($target->primaryKey ? " PRIMARY KEY" : "") 
                . ($target->autoincrement ? " AUTO_INCREMENT" : "");
        
        return "";
    }

    private function getModifiers(IColumn $column) : string
    {
        $modifiers = [];

        if ($column->length)
            $modifiers[] = $column->length;

        if ($column->precision)
            $modifiers[] = $column->precision;

        return empty($modifiers)
            ? ""
            : "(" . \implode(",", $modifiers) . ")";
    }

    /*function getDefinition(bool $isTableAlter = false) : string
    {
        return "";

        if ($this->alter && !$isTableAlter)
            throw new \Exception("Column `{$this->name}` has a wrong configuration");

        if ($this->drop && $this->alter)
            throw new \Exception("Column `{$this->name}` has a wrong configuration");

        if ($this->autoincrement && !$this->primaryKey)
            throw new \Exception("Column `{$this->name}` has a wrong configuration: there can be only one auto column and it must be defined as a key");

        if ($this->drop)
            return "DROP COLUMN `{$this->name}`";

        $mapper = new \Gekko\Types\Mappers\MySQLTypeMapper();

        $startdef = "";

        if ($this->alter)
            $startdef .= "MODIFY COLUMN ";
        else if ($isTableAlter)
            $startdef .= "ADD COLUMN ";

        return  $startdef
                . "`$this->name` "
                . $mapper->mapFrom($this->type) . $this->getModifiers()
                . ($this->nullable ? " NULL" : "")
                . ($this->primaryKey ? " PRIMARY KEY" : "") 
                . ($this->autoincrement ? " AUTO_INCREMENT" : "");
    }
*/
/*
    public function migrateFrom(IColumn $oldcolumn) : void
    {
        if ($this->drop || $oldcolumn->drop)
            return;

        if ($this != $oldcolumn)
            $this->alter();
    }*/
}

