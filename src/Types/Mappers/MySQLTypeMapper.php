<?php
/*
* (c) Leonardo Brugnara
*
* Full copyright and license information in LICENSE file.
*/

namespace Gekko\Types\Mappers;

class MySQLTypeMapper implements ITypeMapper
{
    public function mapFrom(\Gekko\Types\Type $type) : string
    {
        switch ($type)
        {
            case \Gekko\Types\Byte::class:
                return "TINYINT";
            
            case \Gekko\Types\Int16::class:
                return "SMALLINT";
            
            case \Gekko\Types\Int64::class:
                return "BIGINT";
            
            case \Gekko\Types\Int32::class:
            case \Gekko\Types\Integer::class:
                return "INT";

            case \Gekko\Types\Double64::class:
                return "DOUBLE";
            
            case \Gekko\Types\Decimal::class:
                return "DECIMAL";
            
            case \Gekko\Types\Real::class:
            case \Gekko\Types\Float32::class:
                return "FLOAT";

            case \Gekko\Types\Boolean::class:
                return "BOOL";
            
            case \Gekko\Types\Char::class:
                return "CHAR";
            
            case \Gekko\Types\Varchar::class:
                return "VARCHAR";
            
            case \Gekko\Types\Text::class:
            case \Gekko\Types\Str::class:
                return "TEXT";
                
            case \Gekko\Types\Binary::class:
                return "VARBINARY";
            case \Gekko\Types\Blob::class:
                return "BLOB";

            case \Gekko\Types\Time::class:
                return "TIME";

            case \Gekko\Types\DateTime::class:
                return "DATETIME";

            case \Gekko\Types\DateTime::class:
                return "TIMESTAMP";

            case \Gekko\Types\Date::class:
                return "DATE";
        }

        return $type->raw();
    }

    public function mapTo(string $type) : \Gekko\Types\Type
    {
        switch ($type)
        {
            case "TINYINT":
                return \Gekko\Types\Byte::instance();
            
            case "SMALLINT":
                return \Gekko\Types\Int16::instance();
            
            case "INT";
                return \Gekko\Types\Int32::instance();
            
            case "BIGINT":
                return \Gekko\Types\Int64::instance();

            case "FLOAT":
                return \Gekko\Types\Float32::instance();

            case "DOUBLE":
                return \Gekko\Types\Double64::instance();

            case "DECIMAL":
                return \Gekko\Types\Decimal::instance();

            case "BOOL":
            case "BOOLEAN":
                return \Gekko\Types\Boolean::instance();
            

            case "CHAR":
                return \Gekko\Types\Char::instance();

            case "VARCHAR":
                return \Gekko\Types\Varchar::instance();

            case "TEXT":
                return \Gekko\Types\Text::instance();
                
            case "VARBINARY":
            case "BINARY":
                return \Gekko\Types\Binary::instance();
                
            case "BLOB":
                return \Gekko\Types\Blob::instance();
    
            case "TIME":
                return \Gekko\Types\Time::instance();
                
            case "DATE":
            case "DATETIME":
                return \Gekko\Types\DateTime::instance();


            case "TIMESTAMP":
                return \Gekko\Types\DateTime::instance();
        }

        return \Gekko\Types\Type::new($type);
    }
}