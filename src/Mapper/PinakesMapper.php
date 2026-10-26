<?php declare(strict_types=1);

namespace App\Mapper;

use App\Database\Database;
use App\Database\MappedProperty;
use App\Database\Mapping as Mapping;
use App\Database\SQL\Delete;
use App\Database\SQL\Expr;
use App\Database\SQL\Insert;
use App\Database\SQL\Query;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use App\Entity\Artist;
use App\Entity\Author;
use App\Entity\Boardgame;
use App\Entity\BoardgamePublisher;
use App\Entity\Book;
use App\Entity\PinakesEntity;
use App\Entity\Publisher;
use App\Entity\Record;
use App\Entity\RecordLabel;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Transaction;
use App\Entity\User;
use App\Pinakes\Assert;
use App\Pinakes\DataField;
use App\Pinakes\DataTable;
use App\Pinakes\Helper;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

abstract class PinakesMapper {

    /** @var DataField[] */
    private array $data_fields;

    /** @var MappedProperty[] */
    private array $mapped_properties;

    public function __construct() {
        $this->mapped_properties = [];

        $reflection = new ReflectionClass($this->getEntityClass());
        foreach ($reflection->getProperties() as $property) {
            $mapping = MappedProperty::fromReflection($property);
            if (null === $mapping) continue;

            $this->mapped_properties[$mapping->name] = $mapping;
        }

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
        return [ 'self.' . static::getSearchKey() => 'ASC' ];
    }

    public function getMapping(string $property): MappedProperty {
        Assert::isTrue(array_key_exists($property, $this->mapped_properties), 'Unkown property: ' . $property);
        return $this->mapped_properties[$property];
    }

    /** @return PinakesEntity[] */
    public function fetchQuery(Query $query): array {
        $result = [];
        foreach (Database::getInstance()->fetchAll($query) as $row) {
            $result[] = $this->hydrate($row);
        }
        return $result;
    }

    /** @return PinakesEntity[] */
    public function fetchAll(array $where = [], array $order_by = []): array {
        $query = $this->createQuery($this->mapped_properties);
        return $this->fetchQuery($query);
    }

    public function fetchOne(array $where = [], array $order_by = []): ?PinakesEntity {
        $result = $this->fetchAll($where, $order_by);
        Assert::isTrue(1 === count($result), 'Result not unique');
        return array_first($result);
    }

    public function findBy(string $key, mixed $value): ?PinakesEntity {
        $query = $this->createQuery($this->mapped_properties);
        $query->addWhere("self.{$key} = :key");
        $query->setParameter(':key', $value);

        // TODO use fetchOne
        return array_first($this->fetchQuery($query));
    }

    public function find(int $id): ?PinakesEntity {
        return $this->findBy('id', $id);
    }

    public function createTable(string $fields): DataTable {
        $columns = $this->getDataFields($fields);
        return new DataTable($this, $columns);
    }

    public function hydrate(array $data): PinakesEntity {
        $entity_class = $this->getEntityClass();
        $result = new $entity_class();

        foreach ($data as $key => $value) {
            try {
                $property = new ReflectionProperty($result, $key);
            } catch (ReflectionException $ex) {
                continue;
            }

            // not a mapped property
            $mapping = MappedProperty::fromReflection($property);
            if (null === $mapping) continue;

            if (Mapping\Reference::class === $mapping->mapping_type) {
                $reference_mapper = PinakesMapper::getMapper($mapping->virtual_target->target);
                $mapping = $reference_mapper->getMapping($mapping->reference);
            }

            switch ($mapping->mapping_type) {
                case Mapping\OneToMany::class:
                case Mapping\ManyToMany::class:
                    /**
                     * invalid values:
                     * - NULL
                     * - "[{"id":null,"name":null}]"
                    */
                    $target_mapper = PinakesMapper::getMapper($mapping->target);
                    $entities = [];
                    foreach (json_decode($value ?? '[]', true) as $v) {
                        if (!array_key_exists('id', $v) || null === $v['id']) continue;
                        $entities[] = $target_mapper->hydrate($v);
                    }
                    $value = $entities;
                    
                    break;
                case Mapping\ManyToOne::class:
                    /**
                     * invalid values:
                     * - "{"id":null,"name":null}"
                     */
                    $value = json_decode($value, true);
                    if (array_key_exists('id', $value) && null !== $value['id']) {
                        $target_mapper = PinakesMapper::getMapper($mapping->target);
                        $value = $target_mapper->hydrate($value);
                    } else {
                        $value = null;
                    }
                    break;

                default:
                    $value = $mapping->parseValue($value);
            }

            $property->setValue($result, $value);
        }

        return $result;
    }

    public function createQuery(array $properties): Query {
        $table_name = $this->getTableName();

        $result = new Query($table_name);
        $result->addSelect('self.id');

        /** @var MappedProperty $mapping */
        foreach ($properties as $mapping) {
            switch ($mapping->mapping_type) {
                case Mapping\Count::class:
                    $table_name = PinakesMapper::getMapper($mapping->entity)->getTableName();
                    
                    $target = $mapping->virtual_target;
                    if ($target->isManyToMany()) {
                        $from = $target->getJoinTable();
                        $target_key = "{$table_name}_id";
                    } else {
                        $from = PinakesMapper::getMapper($target->target)->getTableName();
                        $target_key = "{$target->target_property}_id";
                    }

                    $result->addSelect("(SELECT COUNT(*) FROM {$from} WHERE {$target_key} = self.id)", $mapping->name);
                    break;
                case Mapping\Reference::class:
                    $target = $mapping->virtual_target;
                    $target_mapper = PinakesMapper::getMapper($target->target);
                    $target_name = $target_mapper->getTableName();

                    $reference = $target_mapper->getMapping($mapping->reference);
                    $reference_target_mapper = PinakesMapper::getMapper($reference->target);
                    $reference_target_name = $reference_target_mapper->getTableName();

                    if ($reference->isManyToMany()) {
                        $from = $reference->getJoinTable();
                    } else {
                        // $from = $reference_target_mapper->getTableName();
                        Assert::notImplemented();
                    }
                    $exists = new Query($from);
                    $exists->addSelect('1');
                    $exists->addJoin("JOIN {$target_name} ON {$target_name}.id = self.{$target_name}_id");
                    $exists->addWhere("self.{$reference_target_name}_id = {$reference_target_name}.id AND book.{$table_name}_id = {$table_name}.id");

                    $subquery = new Query($reference_target_name);
                    $subquery->addSelect(Expr::json_group($subquery->table_name, $reference_target_mapper->getDefaultSelect()));
                    $subquery->addWhere("EXISTS ({$exists->build()})");
                    
                    $result->addSelect("({$subquery->build()})", $mapping->name);
                    break;
                case Mapping\ManyToMany::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect("{$mapping->name}.{$mapping->name}", $mapping->name);

                    $join_table = $mapping->getJoinTable();

                    $subquery = new Query($join_table);
                    $subquery->addSelect("self.{$table_name}_id");
                    $subquery->addSelect(Expr::json_group($target_name, $target->getDefaultSelect()), $mapping->name);
                    $subquery->addJoin("JOIN {$target_name} ON {$target_name}.id = self.{$target_name}_id");
                    $subquery->addGroupBy("self.{$table_name}_id");

                    $result->addJoin("LEFT JOIN ({$subquery->build()}) AS {$mapping->name} ON {$mapping->name}.{$table_name}_id = self.id");
                    break;
                case Mapping\ManyToOne::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect(Expr::json_object($mapping->name, $target->getDefaultSelect()), $mapping->name);
                    $result->addJoin("LEFT JOIN {$target_name} {$mapping->name} ON {$mapping->name}.id = self.{$mapping->name}_id");
                    break;
                case Mapping\OneToMany::class:
                    $target = PinakesMapper::getMapper($mapping->target);
                    $target_name = $target->getTableName();

                    $result->addSelect(Expr::json_group($mapping->name, $target->getDefaultSelect()), $mapping->name);
                    $result->addJoin("LEFT JOIN {$target_name} {$mapping->name} ON {$mapping->name}.{$mapping->target_property}_id = self.id");
                    break;
                case Mapping\Column::class:
                case Mapping\Enum::class:
                    $result->addSelect("self.{$mapping->name}");
            }
        }

        return $result;
    }

    public function upsert(mixed $value): ?int {
        if (Helper::isEmpty($value)) return null;

        $upsert = new Upsert($this->getTableName(), $this->getSearchKey(), $value);
        return Database::getInstance()->fetchColumn($upsert);
    }

    public function saveTransaction(PinakesEntity $entity, array $data): int {
        $instance = Database::getInstance();
        
        $table_name = $this->getTableName();

        if (null === $entity->getId()) {
            $stmt = new Insert($table_name);
        } else {
            $stmt = new Update($table_name, $entity->getId());
        }

        $many_to_manys = [];
        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;

            $mapping = $this->getMapping($name);

            Assert::isFalse($mapping->isVirtual(), 'Cannot insert virtual mapping');

            switch ($mapping->mapping_type) {

                case Mapping\OneToMany::class:
                    // not used for now
                    Assert::error('Unused');
                case Mapping\ManyToMany::class:
                    $many_to_manys[$name] = $value;
                    break;
                case Mapping\ManyToOne::class:
                    if (null !== $value) {
                        $target = PinakesMapper::getMapper($mapping->target);
                        $upsert_id = $target->upsert($value);
                    } else {
                        $upsert_id = null;
                    }

                    $stmt->add($mapping->name . '_id', $upsert_id);
                    break;

                default:
                    $stmt->add($mapping->name, $mapping->prepareValue($value));
            }
        }
        $id = $instance->fetchColumn($stmt);

        foreach ($many_to_manys as $name => $value) {
            $mapping = $this->getMapping($name);
            
            $target = PinakesMapper::getMapper($mapping->target);
            $join_table = $mapping->getJoinTable();

            $delete = new Delete($join_table, "{$table_name}_id = :id");
            $delete->setParameter(':id', $id);
            $instance->execute($delete);

            foreach ($value ?? [] as $v) {
                $upsert_id = $target->upsert($v);

                $insert = new Insert($join_table, false);
                $insert->add($table_name . '_id', $id);
                $insert->add($target->getTableName() . '_id', $upsert_id);
                $instance->execute($insert);
            }
        }

        return $id;
    }

    public function save(PinakesEntity $entity, array $data): int {
        return Database::getInstance()->asTransaction(fn () => $this->saveTransaction($entity, $data));
    }

    public function delete(PinakesEntity $entity) {
        $delete = new Delete($this->getTableName(), "id = :id");
        $delete->setParameter(':id', $entity->getId());
        Database::getInstance()->execute($delete);
    }

    abstract protected function defineDataFields(): array;

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

    abstract public function getDataFieldsList(): array;
    abstract public function getDataFieldsShow(): array;
    abstract public function getDataFieldsExport(): array;

    public function getOptions(): array {
        $options = [];
        foreach ($this->fetchAll() as $entity) {
            $options[$entity->getId()] = (string) $entity;
        }
        return $options;
    }

    public static function getMapper(string $model): static {
        return match($model) {
            Book::class => new BookMapper(),
            Author::class => new AuthorMapper(),
            Publisher::class => new PublisherMapper(),
            Tag::class => new TagMapper(),
            Series::class => new SeriesMapper(),
            Transaction::class => new TransactionMapper(),
            User::class => new UserMapper(),
            Record::class => new RecordMapper(),
            RecordLabel::class => new RecordLabelMapper(),
            Artist::class => new ArtistMapper(),
            Boardgame::class => new BoardgameMapper(),
            BoardgamePublisher::class => new BoardgamePublisherMapper(),
        };
    }
}
