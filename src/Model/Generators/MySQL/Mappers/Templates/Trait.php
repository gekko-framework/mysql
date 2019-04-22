<?= '<?php' ?>

/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 *
 * -------------------------------------------------------
 *
 * WARNING: This is an autogenerated file, you should NOT edit this file as all
 *  the changes will be lost on file regeneration.
 *
 */

namespace <?= $model->namespace; ?>\DataMappers;

use \Gekko\Database\MySQL\MySQLConnection;

trait <?= $model->className ?>DataMapperTrait
{
<?php foreach ($model->properties as $property): ?>
<?php endforeach; ?>
    public function __construct(MySQLConnection $connection)
    {
        parent::__construct($connection, (new \<?= $model->namespace . "\\Descriptors\\" . $model->className ?>Descriptor()));
    }
}