<?= '<?php' ?>

/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace <?= $model->namespace; ?>\DataMappers;

use \Gekko\Database\MySQL\Mappers\MySQLDataMapper;

class <?= $model->className ?>DataMapper extends MySQLDataMapper
{
    use <?= $model->className?>DataMapperTrait;
}
