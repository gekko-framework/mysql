<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Objects;

use Gekko\Database\Objects\ITable;
use Gekko\Database\Objects\IColumn;
use \Gekko\Serialization\JsonSerializable;
use \Gekko\Serialization\JsonDescriptor;

class Table implements ITable
{
    use JsonSerializable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Gekko\Database\MySQL\Objects\Column[]
     */
    private $columns;

    /**
     * @var \Gekko\Database\MySQL\Objects\Column[]
     */
    private $primaryKeys;

    /**
     *
     * @var \Gekko\Database\MySQL\Objects\ForeignKey[]
     */
    private $foreignKeys;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->alter = false;
        $this->drop = false;
        $this->columns = [];
        $this->primaryKeys = [];
        $this->foreignKeys = [];
    }

    public function __get(string $property)
    {
        if (\property_exists($this, $property))
            return $this->{$property};
        throw new \Exception("Unknown property {$property}");
    }

    function name() : string
    {
        return $this->name;
    }

    function column(string $name) : IColumn
    {
        if (isset($this->columns[$name]))
            return $this->columns[$name];

        $column = new Column($name);
        $this->columns[$name] = $column;

        return $column;
    }

    function hasColumn(string $name) : bool
    {
        return isset($this->columns[$name]);
    }

    function hasColumns() : bool
    {
        return !empty($this->columns);
    }

    function primaryKey(IColumn $column) : self
    {
        $this->primaryKeys[] = $column;
        return $this;
    }

    function foreignKey(string ...$columns) : ForeignKey
    {
        $fk = new ForeignKey($this->name, ...$columns);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    public function equals(ITable $table) : bool
    {
        if (!($table instanceof Table) || $this->name != $table->name)
            return false;

        if ($this->columns === null && $table->columns === null)
            return true;

        if ($this->columns === null || $table->columns === null)
            return false;

        if ($this->foreignKeys !== $table->foreignKeys)
            return false;

        foreach ($this->columns as $name => $column)
            if (!$table->hasColumn($name) || !$column->equals($table->column($name)))
                return false;

        return true;
    }

    public function getJsonDescriptor() : JsonDescriptor
    {
        $d = new JsonDescriptor();

        $d->property("name")->string();
        $d->property("columns")->array()->type(\Gekko\Database\MySQL\Objects\Column::class);
        $d->property("primaryKeys")->array()->type(\Gekko\Database\MySQL\Objects\Column::class);
        $d->property("foreignKeys")->array()->type(\Gekko\Database\MySQL\Objects\ForeignKey::class);
        
        return $d;
    }
}