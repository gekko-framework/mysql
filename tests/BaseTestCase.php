<?php
declare(strict_types=1);

namespace Tests;

use Gekko\Env;
use Gekko\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Gekko\Database\MySQL\MySQLConnection;

abstract class BaseTestCase extends TestCase
{
    protected static $configProvider;
    protected static $outputDir;
    protected static $migrationsPath;

    public static function setUpBeforeClass() : void
    {
        self::$outputDir = realpath(dirname(__FILE__));
        self::$migrationsPath = self::$outputDir . "/.migrations";

        Env::init(self::$outputDir);

        $configPath = Env::rootDir() . DIRECTORY_SEPARATOR . (Env::get("config.path") ?? "config");

        self::$configProvider = new ConfigProvider(Env::get("config.driver") ?? "php", Env::get("config.env"), $configPath);
    } 
}