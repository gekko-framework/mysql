<?php
declare(strict_types=1);

namespace Tests\Migrations;

use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Database\MySQL\Migrations\MySQLMigration;

final class UpgradeTest extends \Tests\BaseTestCase
{
    public function test_Upgrade_From_Initial_State_To_Last_Version(): void
    {
        $dbconfig = self::$configProvider->getConfig("database");
        $connection = new MySQLConnection($dbconfig->get("mysql.connection.host"), null, $dbconfig->get("mysql.connection.user"), $dbconfig->get("mysql.connection.pass"));

        $migrationManager = new MySQLMigration($connection, self::$migrationsPath);
        
        $this->assertEquals(0, $migrationManager->getCurrentVersion());
        
        $lastVersion = $migrationManager->getLastVersion();
        $migrationManager->upgradeTo($lastVersion);

        $this->assertEquals($lastVersion, $migrationManager->getCurrentVersion());
    }
}