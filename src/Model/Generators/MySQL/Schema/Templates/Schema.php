<?= '<?php' ?>

/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

$<?= $package->schema ?> = new \Gekko\Database\MySQL\Objects\Schema("<?= $package->schema ?>");

<?php foreach ($package->models as $model): ?>
#
# TABLE: <?= $model->tableName ?>

#

$<?= $model->tableName ?> = $<?= $package->schema ?>->table("<?= $model->tableName ?>");

<?php foreach ($model->properties as $property): ?>
$<?= $model->tableName ?>_<?= $property->columnName ?> = $<?= $model->tableName ?>->column("<?= $property->columnName ?>");

$<?= $model->tableName ?>_<?= $property->columnName ?>->type(<?= $property->type->__toString() ?>::class)
<?php if ($property->length):  ?>
        ->length(<?= $property->length ?>)
<?php endif; ?>
<?php if ($property->primaryKey):  ?>
        ->key()
<?php endif; ?>
<?php if ($property->unique):  ?>
        ->unique()
<?php endif; ?>
<?php if ($property->autoincrement):  ?>
        ->autoincrement()
<?php endif; ?>
<?php if ($property->nullable):  ?>
        ->nullable()
<?php endif; ?>
;

<?php endforeach; ?>
<?php endforeach; ?>

return $<?= $package->schema ?>;
