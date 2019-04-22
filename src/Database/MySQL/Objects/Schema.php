<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Objects;

use Gekko\Model\ModelDescriptor;
use Gekko\Collections\Collection;
use Gekko\Database\Objects\ITable;
use Gekko\Model\PackageDescriptor;
use Gekko\Database\Objects\ISchema;
use Gekko\Model\PropertyDescriptor;
use \Gekko\Serialization\JsonDescriptor;
use Gekko\Model\ModelRelationDescriptor;
use \Gekko\Serialization\JsonSerializable;

class Schema implements ISchema
{
    use JsonSerializable;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Gekko\Database\MySQL\Objects\Table[]
     */
    private $tables;

    /**
     * @var bool
     */
    private $alter;

    /**
     * @var bool
     */
    private $drop;


    public function __construct(string $name)
    {
        $this->name = $name;
        $this->alter = false;
        $this->drop = false;
        $this->tables = [];
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

    function table(string $name) : ITable
    {
        if (isset($this->tables[$name]))
            return $this->tables[$name];

        $table = new Table($name);
        $this->tables[$name] = $table;

        return $table;
    }
    
    function hasTable(string $name) : bool
    {
        return isset($this->tables[$name]);
    }

    function hasTables() : bool
    {
        return !empty($this->tables);
    }

    function equals(ISchema $schema) : bool
    {
        if (!($schema instanceof Schema))
            return false;

        if ($this->name != $schema->name)
            return false;

        if ($this->tables === null && $schema->tables === null)
            return true;

        if ($this->tables === null || $schema->tables === null)
            return false;

        foreach ($this->tables as $name => $table)
            if (!$schema->hasTable($name) || !$table->equals($schema->table($name)))
                return false;

        return true;
    }

    public function getJsonDescriptor() : JsonDescriptor
    {
        $d = new JsonDescriptor();

        $d->property("name")->string();
        $d->property("tables")->array()->type(\Gekko\Database\MySQL\Objects\Table::class);

        return $d;
    }

    public static function createSchemaFromPackageDescriptor(PackageDescriptor $package) : Schema
    {
        $schema = new \Gekko\Database\MySQL\Objects\Schema($package->schema);

        $models = Collection::of($package->models);

        foreach ($models as $model)
        {
            $table = $schema->table($model->tableName);

            foreach ($model->properties as $property)
            {
                $column = $table->column($property->columnName);

                $column->type($property->type->__toString());

                if ($property->length)
                    $column->length($property->length);

                if ($property->primaryKey)
                {
                    $column->key();
                    $table->primaryKey($column);
                }

                if ($property->unique)
                    $column->unique();

                if ($property->autoincrement)
                    $column->autoincrement();

                if ($property->nullable)
                    $column->nullable();
            }

            foreach ($model->relationships as $relation)
            {
                // The HasOne and the HasMany relationships do not create FK constraints
                if ($relation->kind !== ModelRelationDescriptor::BelongsTo)
                    continue;

                $columns = $model->properties()->where(function (PropertyDescriptor $p) use (&$relation) { 
                    return $relation->properties()->any(function ($propertyRelation) use ($p) {
                        return $propertyRelation->local === $p->propertyName;
                    });
                })
                ->select(function (PropertyDescriptor $p) {
                    return $p->columnName;
                })
                ->toArray();

                $foreignModel = $models->first(function (ModelDescriptor $m) use (&$relation) { return $m->fullname() == $relation->foreignModel; });
                    
                $foreignColumns = $foreignModel->properties()->where(function (PropertyDescriptor $p) use (&$relation) {
                    return $relation->properties()->any(function ($propertyRelation) use ($p) {
                        return $propertyRelation->foreign === $p->propertyName;
                    });
                })
                ->select(function (PropertyDescriptor $p) {
                    return $p->columnName;
                })
                ->toArray();
                            
                $table->foreignKey(...$columns)->references($foreignModel->tableName, ...$foreignColumns);
            }
        }

        return $schema;
    }
}