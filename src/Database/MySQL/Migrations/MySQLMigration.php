<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL\Migrations;

use Gekko\Env;
use Gekko\Helpers\Utils;
use Gekko\Database\IConnection;
use Gekko\Config\ConfigProvider;
use Gekko\Database\Objects\ISchema;
use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Database\MySQL\Objects\Schema;
use Gekko\Database\Migrations\IMigration;

class MySQLMigration implements IMigration
{
    /**
     * @var \Gekko\Database\IConnection
     */
    protected $connection;

    /**
     * @var string
     */
    private $path;

    public function __construct(MySQLConnection $connection, string $path)
    {
        $this->connection = $connection;
        $this->path = $path;
    }

    public function loadSchema(int $version) : ?ISchema
    {
        $file = Utils::path($this->path, "v{$version}.json");

        if (!\file_exists($file))
            return null;
        
        $source = \file_get_contents($file);
        return \Gekko\Serialization\JsonSerializer::deserialize($source, Schema::class);
    }

    public function getLastVersion() : int
    {
        $versionFile = Utils::path($this->path, ".versions");

        if (!\file_exists($versionFile))
            return -1;

        return \intval(\file_get_contents($versionFile));
    }

    public function getCurrentVersion() : int
    {
        $currentVersionFile = Utils::path($this->path, ".current");

        if (!\file_exists($currentVersionFile))
            return 0;

        return \intval(\file_get_contents($currentVersionFile));
    }

    public function setCurrentVersion(int $version) : void
    {
        \file_put_contents(Utils::path($this->path, ".current"), $version);
    }

    function migrate(?ISchema $target, ?ISchema $from = null) : void
    {
        $sm = new SchemaMigration();
        $script = $sm->migrate($target, $from);
        $this->connection->exec($script);
    }

    public function upgradeTo(int $version) : void
    {
        if ($version < 1)
            throw new \Exception("Version number must be positive");

        $currver = $this->getCurrentVersion();

        if ($currver == 0)
            $currver = 1;
        else if ($currver == $version)
            return;

        if ($version < $currver)
            throw new \Exception("Cannot upgrade to a lower version");

        for ($i=$currver; $i < $version; $i++)
            $this->upgradeTo($i);

        // Upgrade
        $target = $this->loadSchema($version);
        $from = null;

        if ($version > 1)
            $from = $this->loadSchema($version - 1);

        $this->migrate($target, $from);

        $this->setCurrentVersion($version);
    }

    public function downgradeTo(int $version) : void
    {
        if ($version < 0)
            throw new \Exception("Version number must be positive");

        $currver = $this->getCurrentVersion();

        if ($currver == -1 || $currver == $version)
            return;

        if ($version > $currver)
            throw new \Exception("Cannot downgrade to a higher version");

        for ($i=$currver-1; $i > $version; $i--)
            $this->downgradeTo($i);

        // Downgrade
        $target = $this->loadSchema($version);
        $from = $this->loadSchema($version + 1);

        $this->migrate($target, $from);

        $this->setCurrentVersion($version);
    }

    public function reset() : void
    {
        $this->downgradeTo(0);
    }
}
