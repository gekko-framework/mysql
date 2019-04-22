<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Model\Generators\MySQL\Repositories;

use \Gekko\Model\PackageDescriptor;
use \Gekko\Model\ModelDescriptor;
use \Gekko\Model\PropertyDescriptor;
use \Gekko\Model\Generators\IGenerator;
use \Gekko\Helpers\Utils;
use \Gekko\Types\Type;

class MySQLRepositoryGenerator implements IGenerator
{
    const GEN_CLASS = 1;
    const GEN_TRAIT = 2;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $config;


    public function __construct(int $config, string $path)
    {
        $this->path = $path;
        $this->config = $config;

        if (!\file_exists($this->path))
            \mkdir($this->path, 0777, true);

        if (!\is_writeable($this->path))
            throw new \Error("Cannot write to directory {$this->path}");
    }

    public function generate(PackageDescriptor $package) : void
    {
        $this->generateRepositories($package);
    }

    private function generateRepositories(PackageDescriptor $package) : void
    {
        foreach ($package->models as $model)
        {
            $modelns = $model->namespace;

            if ($package->virtual)
                $modelns = \str_replace($package->namespace, "", $modelns);

            $ns = \explode("\\", $modelns);
        
            array_push($ns, "Repositories");

            $path = Utils::path($this->path, ...$ns);

            if (!file_exists($path))
                mkdir($path, 0777, true);

            $path = Utils::path($path, $model->className);

            if ($this->config & self::GEN_CLASS)
                \file_put_contents($path . 'Repository.php', $this->process("Class", $package, $model));

            if ($this->config & self::GEN_TRAIT)
            {
                \file_put_contents($path . 'RepositoryTrait.php', $this->process("Trait", $package, $model));

                if (!\file_exists($path . 'Repository.php'))
                    \file_put_contents($path . 'Repository.php', $this->process("TraitUsage", $package, $model));
            }
        }
    }

    private function process(string $template, PackageDescriptor $package, ModelDescriptor $model) : string
    {
        ob_start();
        include "Templates/{$template}.php";
        return \ob_get_clean();
    }

    public function mapType(PropertyDescriptor $property) : string
    {
        return $property->type;
    }
}