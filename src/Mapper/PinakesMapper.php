<?php declare(strict_types=1);

namespace App\Mapper;

use App\Database\Database;
use App\Database\Mapping\Count;
use App\Database\Mapping\ManyToMany;
use App\Database\Mapping\ManyToOne;
use App\Database\Mapping\OneToMany;
use App\Database\SQL\Delete;
use App\Database\SQL\Expr;
use App\Database\SQL\Insert;
use App\Database\SQL\Query;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\PinakesEntity;
use App\Entity\Publisher;
use App\Entity\Series;
use App\Entity\Tag;
use App\Pinakes\Assert;
use App\Pinakes\DataField;
use App\Pinakes\DataTable2;
use App\Pinakes\Helper;

abstract class PinakesMapper {

    /** @var DataField[] */
    private array $data_fields;

    public function __construct() {
        $this->data_fields = [];
        foreach ($this->defineDataFields() as $name => $def) {
            $this->data_fields[$name] = new DataField($this, $name, ...$def);
        }
    }
    
    abstract public static function getTableName(): string;
    abstract public static function getSearchKey(): string;
    abstract public static function getEntityClass(): string;

    public static function getDefaultSelect(): array {
        return [ 'id', static::getSearchKey() ];
    }

    public static function getDefaultOrder(): array {
        return [ static::getTableName() . '.' . static::getSearchKey() => 'ASC' ];
    }

    abstract protected function defineDataFields(): array;

    public function fetchAll(Query $query): array {
        $db = Database::getInstance();

        $entities = [];
        foreach ($db->fetchAll($query) as $row) {
            $entity_class = $this->getEntityClass();
            $entities[] = Database::hydrate(new $entity_class(), $row);
        }
        return $entities;
    }

    public function fetchOne(Query $query): ?PinakesEntity {
        return array_first($this->fetchAll($query));
    }

    public function createTable(string $fields): DataTable2 {
        $columns = $this->getDataFields($fields);
        return new DataTable2($this, $columns);
    }

    public function createQuery(array $fields): Query {
        $result = new Query($this->getTableName());
        $result->addSelect('.id');

        /** @var DataField $field */
        foreach ($fields as $field) {
            if (null === $field->mapping) continue;

            switch ($field->mapping->mapping_type) {
                case Count::class:
                    $target = $field->mapping->target_property;
                    $target_name = PinakesMapper::getMapper($target->entity)->getTableName();

                    if ($target->isManyToMany()) {
                        $from = $target->getJoinTable();
                    } else {
                        $from = PinakesMapper::getMapper($target->target)->getTableName();
                    }

                    $result->addVirtualSelect("(SELECT COUNT(*) FROM {$from} WHERE {$target_name}_id = {$target_name}.id)", $field->property);
                    break;
                case ManyToMany::class:
                    $target = $field->data_type->getTargetMapper();

                    $table_name = $this->getTableName();
                    $target_name = $target->getTableName();

                    $result->addSelect("{$field->property}.{$field->property}", $field->property);

                    $join_table = $field->mapping->getJoinTable();

                    $subquery = new Query($join_table);
                    $subquery->addSelect(".{$table_name}_id");
                    $subquery->addSelect(Expr::json_group($target_name, $target->getDefaultSelect()), $field->property);
                    $subquery->addJoin("JOIN {$target_name} ON {$target_name}.id = {$join_table}.{$target_name}_id");
                    $subquery->addGroupBy(".{$table_name}_id");

                    $result->addJoin("LEFT JOIN ({$subquery->build()}) AS {$field->property} ON {$field->property}.{$table_name}_id = {$table_name}.id");
                    break;

                case ManyToOne::class:
                case OneToMany::class:
                    $target = $field->data_type->getTargetMapper();

                    $table_name = $this->getTableName();
                    $target_name = $target->getTableName();

                    $result->addSelect(Expr::json_object($target_name, $target->getDefaultSelect()), $field->property);
                    $result->addJoin("LEFT JOIN {$target_name} {$field->property} ON {$field->property}.id = {$table_name}.{$target_name}_id");
                    break;

                default:
                    $result->addSelect('.' . $field->property);
            }
        }

        return $result;
    }

    private function insert(array $data): int {
        $insert = new Insert($this->getTableName(), 'id');

        $many_to_manys = [];

        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $field = $this->getDataField($name);
            Assert::notNull($field, "Field {$name} not found");
            switch ($field->mapping->mapping_type) {
                case OneToMany::class:
                    // not used for now
                    Assert::error('Unused');
                case ManyToMany::class:
                    $many_to_manys[$name] = $value;
                    break;
                case ManyToOne::class:
                    if (null !== $value) {
                        $target = $field->data_type->getTargetMapper();
                        $upsert = new Upsert($target->getTableName(), $target->getSearchKey(), $value);
                        $upsert_id = Database::getInstance()->upsert($upsert);
                    } else {
                        $upsert_id = null;
                    }

                    $insert->add($field->property . '_id', $upsert_id);
                    break;

                default:
                    $insert->add($field->property, $field->data_type->parse($value));
            }
        }
        $id = Database::getInstance()->insert($insert);

        foreach ($many_to_manys as $name => $value) {
            $field = $this->getDataField($name);

            $target = $field->data_type->getTargetMapper();
            $join_table = $field->mapping->getJoinTable();

            foreach ($value ?? [] as $v) {
                $upsert = new Upsert($target->getTableName(), $target->getSearchKey(), $v);
                $upsert_id = Database::getInstance()->upsert($upsert);

                $assoc_insert = new Insert($join_table, $this->getTableName() . '_id');
                $assoc_insert->add($this->getTableName() . '_id', $id);
                $assoc_insert->add($target->getTableName() . '_id', $upsert_id);

                Database::getInstance()->insert($assoc_insert);
            }
        }

        return $id;
    }

    private function update(PinakesEntity $entity, array $data) {
        $update = new Update($this->getTableName(), $entity->getId());

        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $field = $this->getDataField($name);
            Assert::notNull($field, "Field {$name} not found");
            switch ($field->mapping->mapping_type) {
                case OneToMany::class:
                    // not used for now
                    Assert::error('Unused');
                case ManyToMany::class:
                    $target = $field->data_type->getTargetMapper();
                    $join_table = $field->mapping->getJoinTable();

                    $delete = new Delete($join_table, "{$this->getTableName()}_id = :id");
                    $delete->setParameter(':id', $entity->getId());
                    Database::getInstance()->delete($delete);

                    foreach ($value ?? [] as $v) {
                        $upsert = new Upsert($target->getTableName(), $target->getSearchKey(), $v);
                        $upsert_id = Database::getInstance()->upsert($upsert);

                        $insert = new Insert($join_table, $this->getTableName() . '_id');
                        $insert->add($this->getTableName() . '_id', $entity->getId());
                        $insert->add($target->getTableName() . '_id', $upsert_id);

                        Database::getInstance()->insert($insert);
                    }
                    break;
                case ManyToOne::class:
                    if (null !== $value) {
                        $target = $field->data_type->getTargetMapper();
                        $upsert = new Upsert($target->getTableName(), $target->getSearchKey(), $value);
                        $upsert_id = Database::getInstance()->upsert($upsert);
                    } else {
                        $upsert_id = null;
                    }

                    $update->add($field->property . '_id', $upsert_id);
                    break;

                default:
                    $update->add($field->property, $field->data_type->parse($value));
            }
        }
        Database::getInstance()->update($update);
    }

    public function updateEntity(PinakesEntity $entity, array $data): int {
        if (null !== $entity->getId()) {
            Database::getInstance()->asTransaction(fn() => $this->update($entity, $data));
            return $entity->getId();
        } else {
            return Database::getInstance()->asTransaction(fn() => $this->insert($data));
        }
    }

    protected function composeDataFields(array $names): array {
        $result = [];
        foreach ($names as $name) {
            assert(array_key_exists($name, $this->data_fields), 'Unknown data field ' . $name);
            $result[$name] = $this->data_fields[$name];
        }
        return $result;
    }

    public function getDataField(string $name): ?DataField {
        return $this->data_fields[$name] ?? null;
    }

    public function getDataFields(string $fields): array {
        $func = 'getDataFields' . str_replace('_', '', ucwords($fields, '_'));
        assert(method_exists($this, $func), $func . ' missing for ' . $this::class);
        $result = $this->$func();
        return $result;
    }

    public static function getMapper(string $model): static {
        return match($model) {
            Book::class => new BookMapper(),
            Author::class => new AuthorMapper(),
            Publisher::class => new PublisherMapper(),
            Tag::class => new TagMapper(),
            Series::class => new SeriesMapper(),
        };
    }
}
