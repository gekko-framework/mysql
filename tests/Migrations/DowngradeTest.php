<?php
declare(strict_types=1);

namespace Tests\Migrations;

use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Database\MySQL\Migrations\MySQLMigration;

final class DowngradeTest extends \Tests\BaseTestCase
{
    public function test_Downgrade_From_Last_Version_To_Initial_State(): void
    {
        $dbconfig = self::$configProvider->getConfig("database");
        $connection = new MySQLConnection($dbconfig->get("mysql.connection.host"), $dbconfig->get("mysql.connection.name"), $dbconfig->get("mysql.connection.user"), $dbconfig->get("mysql.connection.pass"));

        $migrationManager = new MySQLMigration($connection, self::$migrationsPath);

        $migrationManager->reset();

        $this->assertEquals(0, $migrationManager->getCurrentVersion());
    }
}