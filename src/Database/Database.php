<?php declare(strict_types=1);

namespace App\Database;

use App\Database\SQL\Delete;
use App\Database\SQL\Insert;
use App\Database\SQL\Query;
use App\Database\SQL\Statement;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use App\Entity\PinakesEntity;
use App\Pinakes\Helper;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionException;
use ReflectionProperty;

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
    }

    public function fetchAll(Query $query): array {
        $stmt = $this->execute($query);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(Insert $insert): int {
        $stmt = $this->execute($insert);
        return $stmt->fetchColumn();
    }

    public function update(Update $update) {
        $stmt = $this->execute($update);
    }

    public function upsert(Upsert $upsert): int {
        $stmt = $this->execute($upsert);
        return $stmt->fetchColumn();
    }

    public function delete(Delete $delete) {
        $this->execute($delete);
    }

    public function count(Query $query): int {
        $count_query = new Query($query->table_name);
        $count_query->selects = [ "COUNT(DISTINCT {$query->table_name}.id)" ];
        $count_query->wheres = $query->wheres;
        $count_query->parameters = $query->parameters;

        $stmt = $this->execute($count_query);
        return $stmt->fetchColumn();
    }

    public function execute(Statement $statement): \PDOStatement {
        $sql = $statement->build() . ';';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($statement->getParameters());
        } catch (\PDOException $ex) {
            var_dump($ex->getMessage());
            var_dump($sql); die();
        }
        return $stmt;
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
            return;
        }

    }

    public static function hydrate(PinakesEntity $entity, array $data): PinakesEntity {
        foreach ($data as $key => $value) {
            try {
                $property = new ReflectionProperty($entity, $key);
            } catch (ReflectionException $ex) {
                continue;
            }

            // not a mapped property
            $mapping = MappedProperty::fromReflection($property);
            if (null === $mapping) continue;

            if ($mapping->isAssociative()) {
                $class_name = $mapping->target;

                if ($mapping->isArrayType()) {
                    if (null !== $value) {
                        $value = json_decode($value, true);
                    }
                    $entities = [];
                    foreach ($value ?? [] as $v) {
                        $entities[] = self::hydrate(new $class_name(), $v);
                    }
                    $value = new ArrayCollection($entities);
                } else {
                    if (null !== $value) {
                        $value = json_decode($value, true);
                        $value = self::hydrate(new $class_name(), $value);
                    }
                }
            } else if ('DateTime' === $mapping->type) {
                $value = new \DateTime($value);
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }
}
