<?php declare(strict_types=1);

namespace App\Database;

use App\Database\SQL\Query;
use App\Database\SQL\Statement;
use Closure;

class Database {

    private static ?self $_instance = null;

    public static function connect(string $dsn) {
        assert(null === self::$_instance, 'Database already connected');

        self::$_instance = new self($dsn);
    }

    public static function getInstance(): self {
        assert(null !== self::$_instance, 'Database not connected');
        return self::$_instance;
    }

    private \PDO $pdo;

    private function __construct(string $dsn) {
        $this->pdo = new \PDO($dsn);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function fetchAll(Query $query): array {
        $stmt = $this->execute($query);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchColumn(Statement $statement): mixed {
        $stmt = $this->execute($statement);
        return $stmt->fetchColumn();
    }

    public function execute(Statement $statement): \PDOStatement {
        $sql = $statement->build() . ';';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($statement->getParameters());
        } catch (\PDOException $ex) {
            var_dump($ex->getMessage());
            var_dump($sql);
            var_dump($statement->getParameters());
            die();
        }
        return $stmt;
    }

    public function exec(string $statement) {
        $this->pdo->exec($statement);
    }

    public function count(Query $query): int {
        $count = new Query($query->table_name);
        $count->select("COUNT(DISTINCT self.id)");
        $count->wheres = $query->wheres;
        $count->parameters = $query->parameters;
        return $this->fetchColumn($count);
    }

    public function asTransaction(Closure $func) {
        $this->pdo->beginTransaction();

        try {
            $result = $func();

            $this->pdo->commit();
            return $result;
        } catch (\PDOException $ex) {
            $this->pdo->rollBack();
            var_dump('Rollback: ' . $ex); die();
        }
    }
}
