<?php declare(strict_types=1);

namespace App\Database;

use App\Database\Mapping\Column;
use App\Database\Mapping\ManyToMany;
use App\Database\Mapping\ManyToOne;
use App\Database\Mapping\OneToMany;
use App\Database\Mapping\Count;
use App\Database\SQL\Delete;
use App\Database\SQL\Expr;
use App\Database\SQL\Insert;
use App\Database\SQL\InsertJoin;
use App\Database\SQL\Query;
use App\Database\SQL\Statement;
use App\Database\SQL\Update;
use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use App\Pinakes\Assert;
use App\Pinakes\Helper;
use Closure;
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
                        if (is_int($v)) Helper::bp($data, $v);
                        $entities[] = self::hydrate(new $class_name(), $v);
                    }
                    $value = $entities;
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


    public static function createQuery(PinakesMapper $mapper, array $properties): Query {
        $table_name = $mapper->getTableName();

        $result = new Query($table_name);
        $result->addSelect('.id');

        /** @var MappedProperty $mapping */
        foreach ($properties as $mapping) {
            switch ($mapping->mapping_type) {
                case Count::class:
                    $target = $mapping->target_property;
                    $target_name = PinakesMapper::getMapper($target->entity)->getTableName();

                    if ($target->isManyToMany()) {
                        $from = $target->getJoinTable();
                    } else {
                        $from = PinakesMapper::getMapper($target->target)->getTableName();
                    }

                    $result->addVirtualSelect("(SELECT COUNT(*) FROM {$from} WHERE {$target_name}_id = {$target_name}.id)", $mapping->name);
                    break;
                case ManyToMany::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect("{$mapping->name}.{$mapping->name}", $mapping->name);

                    $join_table = $mapping->getJoinTable();

                    $subquery = new Query($join_table);
                    $subquery->addSelect(".{$table_name}_id");
                    $subquery->addSelect(Expr::json_group($target_name, $target->getDefaultSelect()), $mapping->name);
                    $subquery->addJoin("JOIN {$target_name} ON {$target_name}.id = {$join_table}.{$target_name}_id");
                    $subquery->addGroupBy(".{$table_name}_id");

                    $result->addJoin("LEFT JOIN ({$subquery->build()}) AS {$mapping->name} ON {$mapping->name}.{$table_name}_id = {$table_name}.id");
                    break;
                case ManyToOne::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect(Expr::json_object($mapping->name, $target->getDefaultSelect()), $mapping->name);
                    $result->addJoin("LEFT JOIN {$target_name} {$mapping->name} ON {$mapping->name}.id = {$table_name}.{$target_name}_id");
                    break;
                case OneToMany::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect(Expr::json_group($mapping->name, $target->getDefaultSelect()), $mapping->name);
                    $result->addJoin("LEFT JOIN {$target_name} {$mapping->name} ON {$mapping->name}.{$table_name}_id = {$table_name}.id");
                    break;
                case Column::class:
                    $result->addSelect('.' . $mapping->name);
            }
        }

        return $result;
    }

    public static function insert(PinakesMapper $mapper, array $data): int {
        $instance = self::getInstance();
        
        $table_name = $mapper->getTableName();
        $insert = new Insert($table_name);

        $many_to_manys = [];

        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $mapping = $mapper->getMapping($name);

            switch ($mapping->mapping_type) {
                case OneToMany::class:
                    // not used for now
                    Assert::error('Unused');
                case ManyToMany::class:
                    $many_to_manys[$name] = $value;
                    break;
                case ManyToOne::class:
                    if (null !== $value) {
                        $target = PinakesMapper::getMapper($mapping->target);
                        $upsert_id = $target->upsert($value);
                    } else {
                        $upsert_id = null;
                    }

                    $insert->add($mapping->name . '_id', $upsert_id);
                    break;

                default:
                    $insert->add($mapping->name, $value);
            }
        }
        $id = $instance->fetchColumn($insert);

        foreach ($many_to_manys as $name => $value) {
            $mapping = $mapper->getMapping($name);
            
            $target = PinakesMapper::getMapper($mapping->target);
            $join_table = $mapping->getJoinTable();

            foreach ($value ?? [] as $v) {
                $upsert_id = $target->upsert($v);

                $insert = new InsertJoin($join_table, [
                    $table_name . '_id' => $id,
                    $target->getTableName() . '_id' => $upsert_id,
                ]);
                $instance->execute($insert);
            }
        }

        return $id;
    }

    public static function update(PinakesMapper $mapper, int $id, array $data) {
        $instance = self::getInstance();
        
        $table_name = $mapper->getTableName();
        $update = new Update($table_name, $id);

        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $mapping = $mapper->getMapping($name);

            switch ($mapping->mapping_type) {
                case OneToMany::class:
                    // not used for now
                    Assert::error('Unused');
                case ManyToMany::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $join_table = $mapping->getJoinTable();

                    $delete = new Delete($join_table, "{$table_name}_id = :id");
                    $delete->setParameter(':id', $id);
                    $instance->execute($delete);

                    foreach ($value ?? [] as $v) {
                        $upsert_id = $target->upsert($v);

                        $insert = new InsertJoin($join_table, [
                            $table_name . '_id' => $id,
                            $target->getTableName() . '_id' => $upsert_id,
                        ]);
                        $instance->execute($insert);
                    }
                    break;
                case ManyToOne::class:
                    if (null !== $value) {
                        $target = PinakesMapper::getMapper($mapping->target);
                        $upsert_id = $target->upsert($value);
                    } else {
                        $upsert_id = null;
                    }

                    $update->add($mapping->name . '_id', $upsert_id);
                    break;

                default:
                    $update->add($mapping->name, $value);
            }
        }
        $instance->execute($update);
    }
}
