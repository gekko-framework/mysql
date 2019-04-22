<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Model\Generators\MySQL\Schema;

use \Gekko\Helpers\Utils;
use \Gekko\Model\PackageDescriptor;
use \Gekko\Model\PropertyDescriptor;
use \Gekko\Model\Generators\IGenerator;
use \Gekko\Serialization\JsonSerializer;
use \Gekko\Database\MySQL\Objects\Schema;

class MySQLSchemaGenerator implements IGenerator
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;

        // Create directory if it doesn't exist
        if (!\file_exists($this->path))
            \mkdir($this->path, 0777, true);

        if (!\is_writeable($this->path))
            throw new \Error("Cannot write to directory {$this->path}");
    }

    public function generate(PackageDescriptor $package) : void
    {
        // Get current versions count
        $versions = $this->getVersionsCount();
        
        // Create Schema object from PackageDescriptor
        $schema = Schema::createSchemaFromPackageDescriptor($package);
        
        // If current count of versions is greater or equals to 1, check if
        // new version has changes, if not just leave
        if ($versions >= 1)
        {
            // Get previous schema
            $prevSchema = JsonSerializer::deserialize(
                \file_get_contents(Utils::path($this->path, "v{$versions}.json")),
                Schema::class
            );
            
            if ($schema->equals($prevSchema))
            {
                echo "MySQLSchemaGenerator: No changes since version {$versions}." . PHP_EOL;
                return;
            }
        }
        
        // Update the versions count
        $versions++;
        $this->setVersionsCount($versions);
        
        // Save the new version
        \file_put_contents(
            Utils::path($this->path, "v{$versions}.json"),
            JsonSerializer::serialize($schema, JSON_PRETTY_PRINT)
        );

        echo "MySQLSchemaGenerator: Version {$versions} generated successfully." . PHP_EOL;
    }

    protected function getVersionsCount() : int
    {
        $versionFile = Utils::path($this->path, ".versions");

        if (!\file_exists($versionFile))
            return 0;

        return \intval(\file_get_contents($versionFile));
    }

    protected function setVersionsCount(int $versions) : void
    {
        \file_put_contents(Utils::path($this->path, ".versions"), $versions);
    }

    public function mapType(PropertyDescriptor $property) : string
    {
        return $property->type;
    }
}