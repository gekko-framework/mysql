<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Objects;

use Gekko\Database\Objects\IForeignKey;
use \Gekko\Serialization\JsonSerializable;
use \Gekko\Serialization\JsonDescriptor;

class ForeignKey implements IForeignKey
{
    use JsonSerializable;

    /**
     * @var string[]
     */
    private $fromColumns;

    /**
     * @var string
     */
    private $fromTable;

    /**
     * @var string[]
     */
    private $toColumns;

    /**
     * @var string
     */
    private $toTable;

    public function __construct(string $fromTable, string ...$fromColumns)
    {
        $this->fromTable = $fromTable;
        $this->fromColumns = $fromColumns;
    }

    public function __get(string $property)
    {
        if (\property_exists($this, $property))
            return $this->{$property};
        throw new \Exception("Unknown property {$property}");
    }

    function name() : string
    {
        return $this->fromTable . "_" . \implode("_", $this->fromColumns) . "__" . $this->toTable . "_" . \implode("_", $this->toColumns);
    }

    function references(string $table, string ...$columns) : IForeignKey
    {
        $this->toTable = $table;
        $this->toColumns = $columns;

        return $this;
    }

    public function equals(IForeignKey $foreignKey) : bool
    {
        return $foreignKey instanceof ForeignKey 
                && $this->fromColumns == $foreignKey->fromColumns
                && $this->toColumns == $foreignKey->toColumns
                && $this->toTable == $foreignKey->toTable;
    }

    public function getJsonDescriptor() : JsonDescriptor
    {
        $d = new JsonDescriptor();

        $d->property("fromTable")->string();
        $d->property("fromColumns")->array()->string();
        $d->property("toColumns")->array()->string();
        $d->property("toTable")->string();
        
        return $d;
    }
}