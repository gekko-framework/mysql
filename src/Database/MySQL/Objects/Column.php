<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Objects;

use \Gekko\Database\Objects\IColumn;
use \Gekko\Serialization\JsonSerializable;
use \Gekko\Serialization\JsonDescriptor;

class Column implements IColumn
{
    use JsonSerializable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Gekko\Types\Type
     */
    public $type;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var bool
     */
    public $primaryKey;

    /**
     * @var bool
     */
    public $unique;

    /**
     * @var bool
     */
    public $autoincrement;
    
    /**
     * @var bool
     */
    private $nullable;


    public function __construct(string $name)
    {
        $this->name = $name;
        $this->alter = false;
        $this->drop = false;
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

    function length(int $length) : IColumn
    {
        $this->length = $length;
        return $this;
    }

    function precision(int $precision) : IColumn
    {
        $this->precision = $precision;
        return $this;
    }

    function nullable() : IColumn
    {
        $this->nullable = true;
        return $this;
    }

    public function key() : IColumn
    {
        $this->primaryKey = true;
        return $this;
    }

    public function unique() : IColumn
    {
        $this->unique = true;
        return $this;
    }

    public function autoincrement() : IColumn
    {
        $this->autoincrement = true;
        return $this;
    }

    public function type(string $type) : IColumn
    {
        $this->type = \Gekko\Types\Type::new($type);
        return $this;
    }

    public function byte() : IColumn
    {
        $this->type = \Gekko\Types\Byte::instance();
        return $this;
    }

    public function int16() : IColumn
    {
        $this->type = \Gekko\Types\Int16::instance();
        return $this;
    }

    public function int32() : IColumn
    {
        $this->type = \Gekko\Types\Int32::instance();
        return $this;
    }

    public function int64() : IColumn
    {
        $this->type = \Gekko\Types\Int64::instance();
        return $this;
    }

    public function float() : IColumn
    {
        $this->type = \Gekko\Types\Float32::instance();
        return $this;
    }

    public function double() : IColumn
    {
        $this->type = \Gekko\Types\Double64::instance();
        return $this;
    }

    public function decimal() : IColumn
    {
        $this->type = \Gekko\Types\Decimal::instance();
        return $this;
    }

    public function boolean() : IColumn
    {
        $this->type = \Gekko\Types\Boolean::instance();
        return $this;
    }

    public function string() : IColumn
    {
        $this->type = \Gekko\Types\Varchar::instance();
        return $this;
    }

    public function text() : IColumn
    {
        $this->type = \Gekko\Types\Text::instance();
        return $this;
    }

    public function char() : IColumn
    {
        $this->type = \Gekko\Types\Char::instance();
        return $this;
    }

    public function varchar() : IColumn
    {
        $this->type = \Gekko\Types\Varchar::instance();
        return $this;
    }

    public function binary() : IColumn
    {
        $this->type = \Gekko\Types\Binary::instance();
        return $this;
    }

    public function blob() : IColumn
    {
        $this->type = \Gekko\Types\Blob::instance();
        return $this;
    }

    public function dateTime() : IColumn
    {
        $this->type = \Gekko\Types\DateTime::instance();
        return $this;
    }

    public function time() : IColumn
    {
        $this->type = \Gekko\Types\Time::instance();
        return $this;
    }

    public function timestamp() : IColumn
    {
        $this->type = \Gekko\Types\Timestamp::instance();
        return $this;
    }

    public function equals(IColumn $column) : bool
    {
        return $column instanceof Column
                && $this->name == $column->name
                && $this->type == $column->type
                && $this->length == $column->length
                && $this->precision == $column->precision
                && $this->primaryKey == $column->primaryKey
                && $this->unique == $column->unique
                && $this->autoincrement == $column->autoincrement
                && $this->nullable == $column->nullable;
    }

    public function getJsonDescriptor() : JsonDescriptor
    {
        $d = new JsonDescriptor();

        $d->property("name")->string();
        $d->property("type")->type(\Gekko\Types\Type::class);
        $d->property("length")->int32();
        $d->property("precision")->int32();
        $d->property("primaryKey")->boolean();
        $d->property("unique")->boolean();
        $d->property("autoincrement")->boolean();
        $d->property("nullable")->boolean();
        return $d;
    }
}
