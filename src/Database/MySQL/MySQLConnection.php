<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Database\MySQL;

use \PDO;
use \Gekko\Database\IConnection;

class MySQLConnection implements IConnection
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(string $host, ?string $db, string $user, string $password, string $charset = 'utf8mb4')
    {
        $dsn = "mysql:host=$host;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->pdo = new PDO($dsn, $user, $password, $opt);

        if (isset($db))
            $this->pdo->exec("USE {$db}");
    }

    public function exec(string $statement) : int
    {
        return $this->pdo->exec($statement);
    }

    public function query(string $query) : \PDOStatement
    {
        return $this->pdo->query($query);
    }

    public function prepare(string $statement) : \PDOStatement
    {
        return $this->pdo->prepare($statement);
    }

    public function lastInsertId() : int
    {
        return $this->pdo->lastInsertId();
    }
}