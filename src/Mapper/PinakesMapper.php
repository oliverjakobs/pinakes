<?php declare(strict_types=1);

namespace App\Mapper;

use App\Database\Database;
use App\Database\MappedProperty;
use App\Database\SQL\Delete;
use App\Database\SQL\Query;
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
        return [ static::getTableName() . '.' . static::getSearchKey() => 'ASC' ];
    }

    abstract protected function defineDataFields(): array;

    public function getMapping(string $property): MappedProperty {
        Assert::isTrue(array_key_exists($property, $this->mapped_properties), 'Unkown property: ' . $property);
        return $this->mapped_properties[$property];
    }

    /** @var string[]|MappedProperty[] $properties */
    private function getMappedProperties(array $properties): array {
        if (Helper::isEmpty($properties)) {
            return $this->mapped_properties;
        }
        
        return array_map(function (string|MappedProperty $p) {
            if (is_string($p)) return $this->getMapping($p);
            return $p;
        }, $properties);
    }

    /** @return PinakesEntity[] */
    public function fetchQuery(Query $query): array {
        $result = [];
        foreach (Database::getInstance()->fetchAll($query) as $row) {
            $entity_class = $this->getEntityClass();
            $result[] = Database::hydrate(new $entity_class(), $row);
        }
        return $result;
    }

    /** @var string[]|MappedProperty[] $properties */
    public function fetchAll(array $properties, array $where = [], array $order_by = []): array {
        $properties = $this->getMappedProperties($properties);
        $query = $this->createQuery($properties);

        return $this->fetchQuery($query);
    }

    public function fetchOne(Query $query): ?PinakesEntity {
        return array_first($this->fetchQuery($query));
    }

    private function getFieldProperties(array $fields): array {
        $properties = array_map(fn(DataField $field) => $field->mapping, $fields);
        return array_filter($properties);
    }

    /** @var string[]|MappedProperty[] $properties */
    public function find(int $id, array $properties = []): ?PinakesEntity {
        return $this->findBy('id', $id, $properties);
    }

    /** @var string[]|MappedProperty[] $properties */
    public function findBy(string $key, mixed $value, array $properties = []): ?PinakesEntity {
        $properties = $this->getMappedProperties($properties);

        $query = $this->createQuery($properties);
        $query->addWhere(".{$key} = :key");
        $query->setParameter(':key', $value);

        return $this->fetchOne($query);
    }

    public function getOrCreate(mixed $value): PinakesEntity {
        $result = $this->findBy($this->getSearchKey(), $value);
        if (null === $result) {
            $entity_class = $this->getEntityClass();
            $result = new $entity_class();
        }
        return $result;
    }

    public function createTable(string $fields): DataTable {
        $columns = $this->getDataFields($fields);
        return new DataTable($this, $columns);
    }

    public function createQueryFromFields(array $fields): Query {
        return $this->createQuery($this->getFieldProperties($fields));
    }

    public function createQuery(array $properties): Query {
        return Database::createQuery($this, $properties);
    }

    public function upsertEntity(mixed $value): ?int {
        if (Helper::isEmpty($value)) return null;

        $upsert = new Upsert($this->getTableName(), $this->getSearchKey(), $value);
        return Database::getInstance()->fetchColumn($upsert);
    }

    public function updateEntity(PinakesEntity $entity, array $data): int {
        if (null !== $entity->getId()) {
            Database::getInstance()->asTransaction(fn() => Database::update($this, $entity->getId(), $data));
            return $entity->getId();
        } else {
            return Database::getInstance()->asTransaction(fn() => Database::insert($this, $data));
        }
    }

    public function deleteEntity(PinakesEntity $entity) {
        $delete = new Delete($this->getTableName(), "id = :id");
        $delete->setParameter(':id', $entity->getId());
        Database::getInstance()->execute($delete);
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

    abstract public function getDataFieldsList(): array;
    abstract public function getDataFieldsShow(): array;
    abstract public function getDataFieldsExport(): array;

    public function getOptions(): array {
        $options = [];
        foreach ($this->fetchAll($this->getDefaultSelect()) as $entity) {
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
