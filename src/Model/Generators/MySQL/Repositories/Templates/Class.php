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
 *  If you want to add custom code to this class, consider using the GEN_TRAIT option
 *  to generate a Trait.
 *
 */

namespace <?= $model->namespace ?>\Repositories;

use <?= $model->fullname() ?>;
use Gekko\Database\MySQL\MySQLConnection;
use <?= $model->namespace ?>\DataMappers\<?= $model->className ?>DataMapper;

class <?= $model->className ?>Repository
{
    /**
     * <?= $model->className ?> model data mapper
     *
     * @var \<?= $model->namespace ?>\DataMappers\<?= $model->className ?>DataMapper
     */
    private $<?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper;


    public function __construct(MySQLConnection $connection)
    {
        $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper = new <?= $model->className ?>DataMapper($connection);
    }


    function get(...$id) : ?<?= $model->className . PHP_EOL ?>
    {
        return $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper->get(...$id);
    }

    function getAll() : array
    {
        return $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper->getAll();
    }

    function add(<?= $model->className ?> $<?= strtolower($model->className[0]) . substr($model->className, 1) ?>) : bool
    {
        return $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper->add($<?= strtolower($model->className[0]) . substr($model->className, 1) ?>);
    }

    function save(<?= $model->className ?> $<?= strtolower($model->className[0]) . substr($model->className, 1) ?>) : bool
    {
        return $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper->save($<?= strtolower($model->className[0]) . substr($model->className, 1) ?>);
    }

    function delete(<?= $model->className ?> $<?= strtolower($model->className[0]) . substr($model->className, 1) ?>) : bool
    {
        return $this-><?= strtolower($model->className[0]) . substr($model->className, 1) ?>DataMapper->delete($<?= strtolower($model->className[0]) . substr($model->className, 1) ?>);
    }
}