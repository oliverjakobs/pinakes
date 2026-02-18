<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use App\Pinakes\DataTable;
use App\Pinakes\DataType;
use App\Pinakes\Helper;
use App\Pinakes\Pinakes;
use App\Renderable\FormElement;
use App\Renderable\Renderable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

abstract class PinakesRepository extends ServiceEntityRepository {

    const LINK_SELF = 'link_self';
    const LINK_DATA = 'link_data';

    const INPUT_DATE = 'date';
    const INPUT_DATETIME = 'datetime-local';
    const INPUT_TIME = 'time';

    private array $data_fields;
    private array $filters;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, static::getEntityClass());
        $this->data_fields = $this->defineDataFields();
        $this->filters = $this->getFilters();
    }
    
    abstract static protected function getEntityClass(): string;
    abstract protected function defineDataFields(): array;

    public static function getInstance(): static {
        return Pinakes::getRepository(static::getEntityClass());
    }

    protected function composeDataFields(?array $names = null): array {
        if (null === $names) return $this->data_fields;

        $result = [];
        foreach ($names as $name) {
            assert(array_key_exists($name, $this->data_fields), 'Unknown data field ' . $name);
            $result[$name] = $this->data_fields[$name];
        }
        return $result;
    }

    public function getDataFields(string $fields): array {
        $func = 'getDataFields' . str_replace('_', '', ucwords($fields, '_'));
        assert(method_exists($this, $func), $func . ' missing for ' . $this::class);
        $result = $this->$func();

        return array_filter($result, fn ($field) => Pinakes::isGranted($field['visibility'] ?? null));
    }

    public function getFilters(): array {
        return [];
    }

    private function getDataType(string $property) {
        $meta = $this->getClassMetadata();
        if ($meta->hasField($property)) {
            return DataType::fromFieldMapping($meta->getFieldMapping($property));
        }
        
        if ($meta->hasAssociation($property)) {
            $target_entity = $meta->getAssociationMapping($property)->targetEntity;

            if ($meta->isSingleValuedAssociation($property)) {
                return DataType::entity($target_entity);
            } else if ($meta->isCollectionValuedAssociation($property)) {
                return DataType::collection($target_entity);
            }
        }
        
        assert(false, 'Unkown field ' . $property);
    }


    private static function guessDataType(PinakesEntity $entity, ?string $property, mixed $data): ?DataType {   
        if (null !== $property) {
            return $entity->getRepository()->getDataType($property);
        }
        
        if ($data instanceof PersistentCollection) {
            return DataType::collection($data->getTypeClass()->rootEntityName);
        }
        
        if ($data instanceof Collection) {
            assert(!$data->isEmpty(), 'Cannot determine type of empty Collection');
            return DataType::collection($data->first()::class);
        }

        if (is_int($data)) return DataType::integer();
        if (is_float($data)) return DataType::float();
        if (is_string($data) || $data instanceof Renderable) return DataType::string();

        return null;
    }

    const MODE_RENDER = 0;
    const MODE_EDIT = 1;
    const MODE_EXPORT = 2;

    private static function getProperty(array $field, int $mode = self::MODE_RENDER): ?string {
        if (self::MODE_EDIT === $mode) {
            $edit = $field['edit'] ?? null;
            if (is_string($edit)) return $edit;
        }
        
        $data = $field['data'] ?? null;
        if (is_callable($data)) return null;
        
        assert(null !== $data, 'No property');
        return $data;
    }

    public static function parseDataField(PinakesEntity $entity, array $field, int $mode = self::MODE_RENDER): array {
        assert(isset($field['data']), 'No data specified');

        // Step 1: Get data
        $property = self::getProperty($field, $mode);
        if (null !== $property) {
            $data = $entity->getValue($property);
        } else {
            $data = $field['data']($entity);
        }

        if (self::MODE_RENDER === $mode && Helper::isEmpty($data)) $data = null;  

        // Step 2: Get datatype
        $data_type = $field['data_type'] ?? null;
        if (null === $data_type) {
            $data_type = self::guessDataType($entity, $property, $data);
        }
        assert(null !== $data_type, 'Failed to determine data type');
        return [ $data, $data_type ];
    }

    public function parseFilter(array $field, string $name, mixed $value): FormElement {
        $data_type = $field['data_type'] ?? null;
        if (null === $data_type) {
            $property = $this->getProperty($field, self::MODE_EDIT);
            assert(null !== $property, 'No property found. Please define a data type');

            $data_type = $this->getDataType($property);
        }

        assert(null !== $data_type, 'Failed to determine data type');
        return $data_type->getForm($name, $data_type->parse($value));
    }

    public function update(PinakesEntity $entity, string $name, string|array|null $value): void {
        $field = $this->data_fields[$name];

        $edit = $field['edit'] ?? true;
        if (!$edit) return;

        // callback
        $callback = $field['edit_callback'] ?? null;
        if (null !== $callback) {
            assert(is_callable($callback));
            $callback($entity, $value);
            return;
        }

        $property = is_string($edit) ? $edit : $field['data'];        
        $data_type = $this->getDataType($property);

        $entity->setValue($property, $data_type->parse($value));
    }

    public function save(PinakesEntity $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($flush) $em->flush();
    }

    public function delete(PinakesEntity $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->remove($entity);

        if ($flush) $em->flush();
    }

    public function getTemplate(): PinakesEntity {
        $entity_name = $this->getEntityName();
        return new $entity_name();
    }

    public function getOrCreate(string $key, bool $flush = true): PinakesEntity {
        $result = $this->findOneBy([ $this->getSearchKey() => $key ]);
        if (null === $result) {
            $result = $this->getTemplate();
            $result->{$this->getSearchKey()} = $key;
            $this->save($result, $flush);
        }

        return $result;
    }

    abstract public function getSearchKey(): string;

    public function getDefaultOrder(): array {
        return [];
    }

    public function findAll(?array $order_by = null, ?int $limit = null, ?int $offset = null): array {
        return $this->findBy([], $order_by ?? $this->getDefaultOrder(), $limit, $offset);
    }

    protected function applyAnd(QueryBuilder $qb, mixed $filter, string $op, string $target): QueryBuilder {
        if (!is_iterable($filter)) $filter = [ $filter ];

        foreach ($filter as $idx => $value) {
            $key = $target . $idx;
            $qb->andWhere(':' . $key . ' ' . $op . ' e.' . $target);
            $qb->setParameter($key, $value);
        }

        return $qb;
    }

    protected function applyOr(QueryBuilder $qb, mixed $filter, string $op, string $target): QueryBuilder {
        if (!is_iterable($filter)) $filter = [ $filter ];

        foreach ($filter as $idx => $value) {
            $key = $target . $idx;
            $qb->orWhere(':' . $key . ' ' . $op . ' e.' . $target);
            $qb->setParameter($key, $value);
        }

        return $qb;
    }

    protected function getQueryBuilder(array $filter = []): QueryBuilder {
        $qb = $this->createQueryBuilder('e');

        $search = $filter['search'] ?? [];
        if (!empty($search)) {
            $qb->where($qb->expr()->like('e.' . $this->getSearchKey(), ':search'));
            $qb->setParameter('search', '%' . $search . '%');
        }

        if (isset($filter['order_by'])) {
            $qb->orderBy('e.' . $filter['order_by'], $filter['order_dir'] ?? 'asc');
        } else {
            foreach ($this->getDefaultOrder() as $by => $dir) {
                $qb->addOrderBy('e.' . $by, $dir);
            }
        }

        return $qb;
    }

    /** @return PinakesEntity[] */
    public function applyFilter(array $filter): array {
        $qb = $this->getQueryBuilder($filter);
        
        // append predefined filters
        foreach ($filter as $name => $value) {
            $def = $this->filters[$name] ?? null;
            if (null === $def) continue;

            $filter_fn = $def['filter'];
            $qb = $filter_fn($qb, $value);
        }

        return $qb->getQuery()->getResult();
    }

    public function createTable(string $fields = 'list'): DataTable {
        return new DataTable($this, $fields);
    }

    public function getOptions(): array {
        $options = [];
        foreach ($this->findAll() as $entity) {
            $options[$entity->getId()] = (string)$entity;
        }
        return $options;
    }
}
