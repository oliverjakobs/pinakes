<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use App\Pinakes\DataColumn;
use App\Pinakes\DataTable;
use App\Pinakes\DataType;
use App\Pinakes\Pinakes;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class PinakesRepository extends ServiceEntityRepository {

    /** @var DataColumn[] */
    private array $data_fields;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, static::getEntityClass());

        $this->data_fields = [];
        foreach ($this->defineDataFields() as $name => $def) {
            $this->data_fields[$name] = new DataColumn($this, $name, ...$def);
        }
    }
    
    abstract static protected function getEntityClass(): string;
    abstract protected function defineDataFields(): array;

    protected function composeDataFields(array $names): array {
        $result = [];
        foreach ($names as $name) {
            assert(array_key_exists($name, $this->data_fields), 'Unknown data field ' . $name);
            $result[$name] = $this->data_fields[$name];
        }
        return $result;
    }

    public function getColumn(string $name): ?DataColumn {
        return $this->data_fields[$name] ?? null;
    }

    public function getDataFields(string $fields): array {
        $func = 'getDataFields' . str_replace('_', '', ucwords($fields, '_'));
        assert(method_exists($this, $func), $func . ' missing for ' . $this::class);
        $result = $this->$func();

        return array_filter($result, fn (DataColumn $col) => Pinakes::isGranted($col->visibility));
    }

    public function getDataType(string $property): DataType {
        $meta = $this->getClassMetadata();
        if ($meta->hasField($property)) {
            $mapping = $meta->getFieldMapping($property);
            if (null !== $mapping->enumType) return DataType::enum($mapping->enumType);
            if ('datetime' === $mapping->type) return DataType::datetime();

            return DataType::create($mapping->type);
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

    public function save(PinakesEntity $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($flush) $em->flush();
    }

    public function flush() {
        $this->getEntityManager()->flush();
    }

    public function delete(PinakesEntity $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->remove($entity);

        if ($flush) $em->flush();
    }

    public function create(): PinakesEntity {
        $entity_name = $this->getEntityName();
        return new $entity_name();
    }

    public function getOrCreate(string $key): PinakesEntity {
        $result = $this->findOneBy([ $this->getSearchKey() => $key ]);
        if (null === $result) {
            $result = $this->create();
            $result->{$this->getSearchKey()} = $key;
        }

        return $result;
    }

    abstract public function getSearchKey(): string;

    public function getDefaultOrder(): array {
        return [ $this->getSearchKey() => 'asc' ];
    }

    public function findAll(?array $order_by = null, ?int $limit = null, ?int $offset = null): array {
        return $this->findBy([], $order_by ?? $this->getDefaultOrder(), $limit, $offset);
    }

    protected function getListQuery(): QueryBuilder {
        return $this->createQueryBuilder('e');
    }

    public function getFilterQuery(array $filter): QueryBuilder {
        $qb = $this->getListQuery();

        // add search
        $search = $filter['search'] ?? null;
        if (null !== $search) {
            $qb->where($qb->expr()->like('e.' . $this->getSearchKey(), ':search'));
            $qb->setParameter('search', '%' . $search . '%');
        }

        // filter by data fields
        foreach ($filter as $name => $value) {
            $field = $this->getColumn($name);
            if (null !== $field) $qb = $field->filter($qb, $value);
        }

        // apply order
        $by = $filter['order_by'] ?? null;
        if (null !== $by) {
            $field = $this->getColumn($by);
            if (null !== $field) $qb = $field->orderBy($qb, $filter['order_dir'] ?? 'asc');
        } else {
            foreach ($this->getDefaultOrder() as $by => $dir) {
                $qb->addOrderBy('e.' . $by, $dir);
            }
        }

        return $qb;
    }

    public function createTable(string $fields = 'list'): DataTable {
        return new DataTable($this, $this->getDataFields($fields));
    }

    public function getOptions(): array {
        $options = [];
        foreach ($this->findAll() as $entity) {
            $options[$entity->getId()] = (string) $entity;
        }
        return $options;
    }
}
