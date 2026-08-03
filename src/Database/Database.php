<?php declare(strict_types=1);

namespace App\Database;

use App\Database\SQL\Delete;
use App\Database\SQL\Insert;
use App\Database\SQL\Query;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use App\Entity\PinakesEntity;
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

    public function query(Query $query): array {
        $stmt = $this->execute($query->build() .  ';', $query->parameters);
        return $this->fetch($stmt);
    }

    public function insert(Insert $insert) {
        $stmt = $this->pdo->prepare($insert->build() . ';');
        $stmt->execute();
    }

    public function update(Update $update) {
        $stmt = $this->pdo->prepare($update->build() . ';');
        $stmt->execute([ ':id' => $update->id ]);
    }

    public function upsert(Upsert $upsert): int {
        $stmt = $this->pdo->prepare($upsert->build() . ';');
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function delete(Delete $delete) {
        $stmt = $this->pdo->prepare($delete->build() . ';');
        $stmt->execute($delete->parameters);
    }

    public function count(Query $query): int {
        $count_query = new Query($query->table_name);
        $count_query->selects = [ "COUNT(DISTINCT {$query->table_name}.id)" ];
        $count_query->wheres = $query->wheres;

        $stmt = $this->pdo->prepare($count_query->build() . ';');
        $stmt->execute($query->parameters);

        return $stmt->fetchColumn();
    }

    public function execute(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $ex) {
            var_dump($ex->getMessage());
            var_dump($sql); die();
        }
        return $stmt;
    }

    public function asTransaction(Closure $func) {
        $this->pdo->beginTransaction();

        try {
            $func();
        } catch (\PDOException $ex) {
            $this->pdo->rollBack();
            var_dump('Rollback: ' . $ex); die();
            return;
        }

        $this->pdo->commit();
    }

    public function fetch(\PDOStatement $stmt): array {
        $result = [];
        while ($fetch = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $id = $fetch['id'];
            $result[$id] = $this->processFetch($fetch, $result[$id] ?? []);
        }

        return $result;
    }

    /*
  array(15) {
    ["id"]=>
    int(633)
    ["title"]=>
    string(35) "Manifest der Kommunistischen Partei"
    ["authors:id"]=>
    string(7) "109;252"
    ["authors:name"]=>
    string(26) "Karl Marx;Friedrich Engels"
    ["publisher:id"]=>
    int(137)
    ["publisher:name"]=>
    string(12) "Dietz Verlag"
    ["tags:id"]=>
    string(5) "30;31"
    ["tags:name"]=>
    string(18) "Philosophy;Marxism"
    ["tags:color"]=>
    string(15) "#adb9e2;#ff0000"
    ["published"]=>
    int(1982)
    ["first_published"]=>
    int(1890)
    ["isbn"]=>
    NULL
    ["series:id"]=>
    NULL
    ["series:name"]=>
    NULL
    ["series_volume"]=>
    NULL
  }

    */

    private function processFetch(array $fetch, array $row): array {
        $result = [];
        foreach ($fetch as $key => $value) {
            // ':' in key means association
            if (str_contains($key, ':')) {
                list($key, $assoc_key) = explode(':', $key, 2);

                if (null !== $value) {
                    $assoc_values = $result[$key] ?? [];
                    foreach (explode(';', (string) $value) as $idx => $v) {
                        $assoc_values[$idx] = array_merge($assoc_values[$idx] ?? [], [ $assoc_key => $v ]);
                    }

                    $value = $assoc_values;
                }
            }

            $result[$key] = $value;
        }

        return $result;
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
                    if (null === $value) $value = [];

                    $entities = [];
                    foreach ($value as $v) {
                        $entities[] = self::hydrate(new $class_name(), $v);
                    }
                    $value = new ArrayCollection($entities);
                } else {
                    if (null !== $value) $value = self::hydrate(new $class_name(), $value[0]);
                }
            } else if ('DateTime' === $mapping->type) {
                $value = new \DateTime($value);
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }
}
