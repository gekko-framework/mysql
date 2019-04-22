<?php
declare(strict_types=1);

namespace Tests\Scaffolding;

use Gekko\Model\Generators\Runner;
use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Model\Generators\Domain\DomainGenerator;
use Gekko\Database\MySQL\Migrations\MySQLMigration;
use Gekko\Model\Generators\MySQL\Schema\MySQLSchemaGenerator;
use Gekko\Model\Generators\MySQL\Mappers\MySQLDataMapperGenerator;
use Gekko\Model\Generators\MySQL\Repositories\MySQLRepositoryGenerator;

final class ScaffoldingTest extends \Tests\BaseTestCase
{
    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();

        // Cleanup the migrations path
        foreach (glob(self::$migrationsPath . "/*.json") as $file) {
            unlink($file);
        }
        if (\file_exists(self::$migrationsPath . "/.versions"))
            unlink(self::$migrationsPath . "/.versions");

        $runner = new Runner();
        $runner->register(new DomainGenerator(DomainGenerator::GEN_CLASS, self::$outputDir));
        $runner->register(new MySQLSchemaGenerator(self::$migrationsPath));
        $runner->register(new MySQLDataMapperGenerator(MySQLDataMapperGenerator::GEN_CLASS, self::$outputDir));
        $runner->register(new MySQLRepositoryGenerator(MySQLRepositoryGenerator::GEN_CLASS, self::$outputDir));
        $package = (function() { 
            return require self::$outputDir . "/.model/package.php";
        })();
        $runner->run($package);
    }

    public function test_Auto_Generate_Domain_Classes() : void
    {
        $this->assertTrue(\class_exists("Tests\\Domain\\User"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Destination"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Rate"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Booking"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Ticket"));

        $this->assertTrue(\class_exists("Tests\\Domain\\Descriptors\\UserDescriptor"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Descriptors\\DestinationDescriptor"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Descriptors\\RateDescriptor"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Descriptors\\BookingDescriptor"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Descriptors\\TicketDescriptor"));
    }

    public function test_Auto_Generate_MySQL_DataMappers() : void
    {
        $this->assertTrue(\class_exists("Tests\\Domain\\DataMappers\\UserDataMapper"));
        $this->assertTrue(\class_exists("Tests\\Domain\\DataMappers\\DestinationDataMapper"));
        $this->assertTrue(\class_exists("Tests\\Domain\\DataMappers\\RateDataMapper"));
        $this->assertTrue(\class_exists("Tests\\Domain\\DataMappers\\BookingDataMapper"));
        $this->assertTrue(\class_exists("Tests\\Domain\\DataMappers\\TicketDataMapper"));
    }

    public function test_Auto_Generate_MySQL_Repositories() : void
    {
        $this->assertTrue(\class_exists("Tests\\Domain\\Repositories\\UserRepository"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Repositories\\DestinationRepository"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Repositories\\RateRepository"));
        $this->assertTrue(\class_exists("Tests\\Domain\\Repositories\\TicketRepository"));
    }

    public function test_Auto_Generate_Database_Schema() : void
    {
        $dbconfig = self::$configProvider->getConfig("database");
        $connection = new MySQLConnection($dbconfig->get("mysql.connection.host"), null, $dbconfig->get("mysql.connection.user"), $dbconfig->get("mysql.connection.pass"));

        $migrationManager = new MySQLMigration($connection, self::$migrationsPath);

        $lastVersion = $migrationManager->getLastVersion();

        $this->assertTrue(file_exists(self::$migrationsPath . "/v{$lastVersion}.json"));

        $this->assertGreaterThan(0, $lastVersion);

        $schema = $migrationManager->loadSchema($lastVersion);

        $this->assertNotNull($schema);

        $this->assertEquals("mysql_tests", $schema->name);

        $this->assertGreaterThan(0, $schema->tables);

        $this->assertNotNull($schema->tables["users"]);
        $this->assertNotNull($schema->tables["rates"]);
        $this->assertNotNull($schema->tables["destinations"]);
        $this->assertNotNull($schema->tables["bookings"]);
        $this->assertNotNull($schema->tables["tickets"]);
    }
}