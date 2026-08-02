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

        $result = [];
        while ($fetch = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $id = $fetch['id'];
            $result[$id] = $this->processFetch($fetch, $result[$id] ?? []);
        }

        return $result;
    }

    public function queryPaginated(Query $query, int $limit, int $offset): array {
        $subquery = new Query($query->table_name);
        $subquery->wheres = $query->wheres;
        $subquery->order_by = $query->order_by;
        $subquery->setLimit($limit, $offset);

        $paginated_query = new Query($query->table_name);
        $paginated_query->selects = $query->selects;
        $paginated_query->joins = $query->joins;
        $paginated_query->wheres = [ "book.id IN ({$subquery->build()})" ];
        $paginated_query->order_by = $query->order_by;
        $paginated_query->parameters = $query->parameters;
        return $this->query($paginated_query);
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

    private function processFetch(array $fetch, array $row): array {
        $packed = [];
        foreach ($fetch as $key => $value) {
            // ':' in key means association
            if (str_contains($key, ':')) {
                list($key, $assoc_key) = explode(':', $key);
                $value = array_merge($packed[$key] ?? [], [ $assoc_key => $value ]);
            } 
            $packed[$key] = $value;        
        }

        foreach ($packed as $name => $value) {
            // skip empty associations
            if (is_array($value) && !isset($value['id'])) continue;

            $row_value = $row[$name] ?? null;
            if (null === $row_value) {
                $row[$name] = $value;
                continue;
            }

            // association
            if (is_array($row_value)) {
                // $row_value is either:
                // A: [ 'id' => 1, ... ]
                // or
                // B: [ 3 => [ 'id' => 3, ... ], 5 => [ 'id' => 5, ... ] ]
                // $value is always A
                assert(array_key_exists('id', $value));

                $v_id = $value['id'];
                $id = $row[$name]['id'] ?? null;

                // already there
                if ($v_id === $id) continue;

                if (null !== $id) {
                    // switch from A to B to make space for next one
                    $row[$name] = [
                        $id => $row[$name],
                    ];
                }
                $row[$name][$v_id] = $value;
            }
        }

        return $row;
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
                    if (array_key_exists('id', $value)) $value = [ $value ];

                    $entities = [];
                    foreach ($value as $v) {
                        $entities[] = self::hydrate(new $class_name(), $v);
                    }
                    $value = new ArrayCollection($entities);
                } else {
                    $value = self::hydrate(new $class_name(), $value);
                }
            } else if ('DateTime' === $mapping->type) {
                $value = new \DateTime($value);
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }
}
