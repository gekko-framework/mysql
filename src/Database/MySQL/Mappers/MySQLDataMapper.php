<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Mappers;

use Gekko\Env;
use Gekko\Config\ConfigProvider;
use Gekko\Model\ModelDescriptor;
use Gekko\Collections\Collection;
use Gekko\Database\Mappers\IDataMapper;
use Gekko\Model\ModelRelationDescriptor;
use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Database\Mappers\DataMapperProvider;

class MySQLDataMapper implements IDataMapper
{
    /**
     * MySQL's Connection object
     *
     * @var \Gekko\Database\MySQL\MySQLConnection
     */
    private $connection;

    /**
     * Model's descriptor
     *
     * @var \Gekko\Model\ModelDescriptor
     */
    private $descriptor;

    /**
     * DataMapper provider
     *
     * @var \Gekko\Database\Mappers\DataMapperProvider
     */
    private $dataMapperProvider;

    private static $tracked = [];


    public function __construct(MySQLConnection $connection, ModelDescriptor $descriptor)
    {
        $this->connection = $connection;
        $this->descriptor = $descriptor;
        $this->dataMapperProvider = new DataMapperProvider($this->connection);
        self::$tracked[$this->descriptor->fullname()] = [];
    }


    public function get(...$ids) : ?object
    {
        $select = $this->getSelectClause();

        // Create the WHERE clause using the primary keys
        $where = $this->buildWhere(
            ...$this->descriptor
                ->properties()
                ->where(function ($p) { return $p->primaryKey; })
                ->select(function ($pkProperty) { 
                    return [ $this->getTableName() . "." . $this->escape($pkProperty->columnName), "=", "?"]; 
                })
                ->toArray()
        );

        $pstmt = $this->connection->prepare("{$select} {$where}");

        $stmtResult = $pstmt->execute($ids);

        $result = $pstmt->fetch();

        return $result !== false 
            ? $this->hydrate($result) 
            : null;
    }

    public function getAll() : array
    {
        $select = $this->getSelectClause();

        $pstmt = $this->connection->prepare($select);

        $stmtResult = $pstmt->execute();

        $objects = [];
        while ($result = $pstmt->fetch())
        {
            $objects[] = $this->hydrate($result);
        }
        
        return $objects;
    }

    public function where(array ...$conditions) : ?object
    {
        $select = $this->getSelectClause();

        $values = [];
        // Create the WHERE clause using the primary keys
        $where = $this->buildWhere(
            ...Collection::of($conditions)
                ->select(function ($condition) use (&$values) { 
                    $values[] = $condition[2];
                    
                    $d = (new \Gekko\Model\ModelDescriptorProvider())->resolve($condition[0][0]);

                    return [ $this->escape($d->tableName) . "." . $this->escape($d->getProperty($condition[0][1])->columnName), $condition[1], "?"]; 
                })
                ->toArray()
        );

        $pstmt = $this->connection->prepare("{$select} {$where}");

        $stmtResult = $pstmt->execute($values);

        $objects = [];
        while ($result = $pstmt->fetch())
        {
            $objects[] = $this->hydrate($result);
        }
        
        return $objects;
    }

    public function add(object $object) : bool
    {
        // Get all the columns' names of properties that don't have the autoincrement flag
        $columns = $this->descriptor->properties()
                    ->where(function ($p) { return !$p->autoincrement; })
                    ->select(function ($p) { return $p->columnName; })
                    ->toArray();

        // Build the INSERT command
        $sqlstmt = $this->buildInsert($this->descriptor->tableName, $columns);
        
        // Extract the object's values
        $values = $this->dehydrate($object);

        // Execute the INSERT
        $result = $this->connection->prepare($sqlstmt)->execute($values);

        // If everything is ok, update the autoincrement PK if it exists
        if ($result)
        {
            $pkAiProperty = $this->descriptor
                ->properties()
                ->first(function ($p) { return $p->primaryKey && $p->autoincrement; });

            if ($pkAiProperty != null)
                $this->hydrateProperty($object, $pkAiProperty->propertyName, $this->connection->lastInsertId());
        }

        return $result;
    }

    public function save(object $object) : bool
    {
        // Get all the columns' names of properties that don't have the autoincrement flag
        $columns = $this->descriptor->properties()
                    ->where(function ($p) { return !$p->autoincrement; })
                    ->select(function ($p) { return $p->columnName; })
                    ->toArray();

        // Build the INSERT command
        $sqlstmt = $this->buildUpdate($this->descriptor->tableName, $columns);
        
        // Create the WHERE clause using the primary keys
        $where = $this->buildWhere(
            ...$this->descriptor
                ->properties()
                ->where(function ($p) { return $p->primaryKey; })
                ->select(function ($pkProperty) { 
                    return [ $this->getTableName() . "." . $this->escape($pkProperty->columnName), "=", "?"]; 
                })
                ->toArray()
        );

        $sqlstmt .= " {$where}";

        // Get the PKs values for the WHERE clause
        $pksValues = $this->descriptor->properties()
                ->where(function ($p) { return $p->primaryKey; })
                ->select(function ($p) use (&$object) { return $this->dehydrateProperty($object, $p->propertyName); })
                ->toArray();

        // Extract the object's values to update and merge them with the WHERE clause fields
        $values = \array_merge($this->dehydrate($object), $pksValues);

        // Execute the UPDATE
        return $this->connection->prepare($sqlstmt)->execute($values);
    }

    public function delete(object $object) : bool
    {
        // Create the DELETE statement using the primary keys for the WHERE clause
        $sqlstmt = "DELETE FROM {$this->getTableName()} " . $this->buildWhere(
            ...$this->descriptor->properties()
                ->where(function ($p) { return $p->primaryKey; })
                ->select(function ($pk) { 
                    return [ $this->getTableName() . "." . $this->escape($pk->columnName), "=", "?"]; 
                })
                ->toArray()
        );

        // Get the PKs values
        $values = $this->descriptor->properties()
                ->where(function ($p) { return $p->primaryKey; })
                ->select(function ($p) use (&$object) { return $this->dehydrateProperty($object, $p->propertyName); })
                ->toArray();

        // Execute the DELETE
        return $this->connection->prepare($sqlstmt)->execute($values);
    }

    protected function escape(string $name) : string
    {
        return "`$name`";
    }

    protected function getTableName() : string
    {
        return $this->escape($this->descriptor->tableName);
    }

    protected function getColumnAlias(string $column)
    {
        return "{$this->descriptor->tableName}_{$column}";
    }

    protected function getSelectClause() : string
    {
        // Get the escaped table name
        $tableName = $this->getTableName();

        // Get the table name and the columns' names (including the foreign fields)
        $fields = $this->getSelectFields();

        // Get all the JOIN clauses that are needed to properly instantiate the BelongsTo and HasOne relationships
        $joins = $this->getForeignTablesJoinClause();

        // Create the SELECT clause
        return $this->buildSelect($tableName, ...$fields) . (!empty($joins) ? implode(" ", $joins) : "");
    }

    protected function getSelectFields(array &$resolvedModels = []) : array
    {
        if (in_array($this->descriptor->className, $resolvedModels))
            return [];

        // Build the SELECT clause with the table name and the columns' names
        $tableName = $this->getTableName();

        // Get all the columns' names
        $fields = $this->descriptor->properties()->select(function ($p) use ($tableName) { 
            return $tableName . "." . $this->escape($p->columnName) . " AS " . $this->escape($this->getColumnAlias($p->columnName));
        })->toArray();

        foreach ($this->descriptor->namedRelationships() as $relationship)
        {
            // HasMany do not add fields to the SELECT clause
            if ($relationship->kind === ModelRelationDescriptor::HasMany)
                continue;

            // Get the foreign data mapper
            $foreignDataMapper = $this->dataMapperProvider->resolve($relationship->foreignModel);
            // Get all the foreign fields to retrieve in the SELECT query to use them on the hydrate method
            $resolvedModels[] = $this->descriptor->className;
            $fields = array_merge($fields, $foreignDataMapper->getSelectFields($resolvedModels));
        }

        return $fields;
    }

    /**
     * Return all the JOIN clauses of the current ModelDescriptor to properly load the
     * BelongsTo and HasOne relationships
     *
     * @param string ...$joinedTables List of already processed tables in order to avoid duplication
     * @return array list of JOIN clauses
     */
    function getForeignTablesJoinClause(array &$joinedTables = []) : array
    {
        // Get the escaped table name
        $tableName = $this->getTableName();
        
        $joinedTables[] = $tableName;

        // Relationships
        $joins = [];

        foreach ($this->descriptor->namedRelationships() as $relationship)
        {
            // HasMany do not add JOINs to the SELECT clause
            if ($relationship->kind === ModelRelationDescriptor::HasMany)
                continue;

            // Get the foreign data mapper
            $foreignDataMapper = $this->dataMapperProvider->resolve($relationship->foreignModel);
            // Get foreign model table and column names
            $foreignTableName = $foreignDataMapper->getTableName();

            // If we previously included fields of the foreign model, avoid including them again.
            if (in_array($foreignTableName, $joinedTables))
                continue;

            $clauses = [];
            foreach ($relationship->properties as $property) {
                $columnName = $this->escape($this->descriptor->getProperty($property->local)->columnName);
                $foreignColumnName = $this->escape($foreignDataMapper->descriptor->getProperty($property->foreign)->columnName);

                $clauses[] = "{$tableName}.{$columnName} = {$foreignTableName}.{$foreignColumnName}";
            }

            $joins[] = " LEFT JOIN {$foreignTableName} ON " . implode(" AND ", $clauses);

            // Retrieve the JOIN clauses of the foreign model
            $joinedTables[] = $foreignTableName;
            $joins = array_merge($joins, $foreignDataMapper->getForeignTablesJoinClause($joinedTables));
        }

        return $joins;
    }

    protected function buildSelect(string $table, string ...$fields) : string
    {
        // Escape the table name if needed
        if (strpos(trim($table), '`') !== 0)
            $table = $this->escape($table);

        // Get the escaped fields to retrieve
        $fields = Collection::of($fields)
                    ->select(function ($f) {
                        if (strpos(trim($f), '`') !== 0)
                            $f = $this->escape($f); 
                        return $f;
                    })
                    ->join(", ");

        // Return the SELECT clause
        return "SELECT {$fields} FROM {$table}";
    }

    protected function buildWhere(array ...$conditions) : string
    {
        $props = $this->descriptor->properties();

        // Parts of the WHERE clause
        $parts = [];
        foreach ($conditions as $condition)
        {
            // Each conditions must contain 3 indexes
            //  0: Field
            //  1: Operator
            //  2: Value
            $field = $condition[0];
            $operator = $condition[1];
            $value = $condition[2];

            // Check if the field needs to be escaped            
            if (\strpos($field, '`') !== 0 
                    && $props->any(function ($p) use ($field) { return ($p->columnName) == $field; }))
                $field = $this->escape($condition[0]);

            // Build this part
            $parts[] = $this->buildWhereCondition($field, $operator, $value);
        }

        return "WHERE" . \implode(" AND ", $parts);
    }

    // TODO: Add support for other operators
    protected function buildWhereCondition(string $field, string $operator, $value) : string
    {
        return " {$field} {$operator} {$value}";
    }

    protected function buildInsert(string $table, array $columns) : string
    {
        // Escape the table name
        $table = $this->escape($table);
        
        // Escape each column and join them with the column separator
        $columnsstr = Collection::of($columns)->select(function ($c) { return $this->escape($c); })->join(", ");
        $valuesstr = \implode(", ", \str_split(\str_repeat("?", count($columns))));

        // Return the INSERT clause
        return "INSERT INTO {$table} ({$columnsstr}) VALUES ({$valuesstr})";
    }

    protected function buildUpdate(string $table, array $columns) : string
    {
        // Escape the table name
        $table = $this->escape($table);
        
        $fields = Collection::of($columns)->select(function ($column) {
            return $this->getTableName() . "." . $this->escape($column) . " = " . "?"; 
        })->join(", ");

        // Return the INSERT clause
        return "UPDATE {$table} SET {$fields}";
    }

    protected function hydrate(array $values, ?string $cyclicModel = null, ?object $cyclicInstance = null) : ?object
    {
        // Get the model's class name
        $model = $this->descriptor->fullname();

        $isNull = true;

        $pkkey = $this->descriptor->properties()
            ->where(function ($p) { return $p->primaryKey; })
            ->select(function ($p) use (&$values) { return $values[$this->getColumnAlias($p->columnName)]; })
            ->join("_");

        // Create an instance of the model, if it is not being tracked
        if (isset(self::$tracked[$model][$pkkey]))
            $obj = self::$tracked[$model][$pkkey];
        else        
            $obj = self::$tracked[$model][$pkkey] = new $model;

        // Hydrate each property
        $this->descriptor->properties()
            ->forEach(function ($p) use (&$values, &$obj, &$isNull, &$cyclicModel, &$cyclicInstance) {

                // Check if the property has a named relationship (the ones that create properties in the models) with other models
                // of types HasOne or BelongTo (HasMany relationships are not hydrated by default)
                $relation = $this->descriptor->namedRelationships()->first(function ($relationship) use (&$p) {

                    if ($relationship->kind === ModelRelationDescriptor::HasMany)
                        return false;

                    return $relationship->properties()->any(function ($propertyRelation) use (&$p) {
                        return $propertyRelation->local === $p->propertyName;
                    });
                });

                // If property does not have a relationship, or if the relationship is a HasOne relationship (owner)
                // retrieve the column value, and if it exists, assign it to the object's property
                if ($relation == null || $relation->kind === ModelRelationDescriptor::HasOne)
                {
                    $colAlias = $this->getColumnAlias($p->columnName);
                    if (isset($values[$colAlias]))
                    {
                        $isNull = false;
                        $this->hydrateProperty($obj, $p->propertyName, $values[$colAlias]);
                    }
                    // else: Should we handle type's defaultvalues here?

                    // If there is no relationship, we are done here
                    if ($relation == null)
                        return;
                }

                // At this point, there is a relationship, so that we need to load the foreign
                // instance and assign it to the object's property referred by the ModelDescriptorRelation::$name
                // property.

                // Get the foreign data mapper
                $foreignDataMapper = $this->dataMapperProvider->resolve($relation->foreignModel);

                if ($cyclicModel !== null && $foreignDataMapper->descriptor->fullname() === $cyclicModel)
                {
                    $isNull = false;
                    $this->hydrateProperty($obj, $relation->name, $cyclicInstance);
                    return;
                }

                if ($foreignDataMapper->descriptor->namedRelationships()->any(function ($foreignRel) use (&$relation) {
                    // If the relationship foreign model is not the current model, nothing to do
                    if ($foreignRel->foreignModel !== $this->descriptor->fullname())
                        return false;

                    return $foreignRel->properties()->any(function ($foreignRelProp) use (&$relation) {
                        return $relation->properties()->any(function ($prop) use (&$foreignRelProp) {
                            return $foreignRelProp->local === $prop->foreign 
                                    && $foreignRelProp->foreign === $prop->local;
                        });
                    });
                }))
                {
                    $foreignInstance = $foreignDataMapper->hydrate($values, $this->descriptor->fullname(), $obj);
                }
                else
                {
                    $foreignInstance = $foreignDataMapper->hydrate($values);
                }

                if ($foreignInstance !== null)
                {
                    $isNull = false;
                    $this->hydrateProperty($obj, $relation->name, $foreignInstance);
                }
            });

        // Return the retrieved object
        return $isNull ? null : $obj;
    }

    protected function hydrateProperty($object, $property, $value)
    {
        \Closure::bind(function() use ($property, $value) {
            $this->$property = $value;
        }, $object, $object)->__invoke();
    }

    protected function dehydrate(object $object) : array
    {
        // Return all the values of the current model instance that are not
        // flagged as autoincrement
        return $this->descriptor->properties()
                    ->where(function ($p) { return !$p->autoincrement; })
                    ->select(function ($p) use ($object) {

                        $relation = $this->descriptor->namedRelationships()->first(function ($relation) use (&$p) {
                            return $relation->properties()->any(function ($propertyRelation) use (&$p, &$relation) {
                                // We just want FK, therefore we use BelongsTo
                                return $relation->kind === ModelRelationDescriptor::BelongsTo && $propertyRelation->local === $p->propertyName;
                            });
                        });

                        // If this property does not have a relationship, return its value
                        if ($relation == null)
                            return $this->dehydrateProperty($object, $p->propertyName);

                        // Get the foreign object's reference
                        $foreignObject = $this->dehydrateProperty($object, $relation->name);

                        if ($foreignObject == null)
                            return null;

                        // Return the value identified by the relationship's property of the current
                        // foreign instance
                        $propertyRelation = $relation->properties()->first(function ($propertyRelation) use (&$p) {
                            return $propertyRelation->local === $p->propertyName;
                        });
                        return $this->dehydrateProperty($foreignObject, $propertyRelation->foreign);
                    })
                    ->toArray();
    }

    protected function dehydrateProperty($object, $property)
    {
        return \Closure::bind(function() use ($property) {
                return $this->{$property};
        }, $object, $object)->__invoke();
    }
}
