<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use App\Pinakes\Context;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

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
        $this->filters = $this->defineFilters();
    }
    
    abstract static protected function getEntityClass(): string;
    abstract protected function defineDataFields(): array;

    protected function defineFilters(): array {
        return [];
    }

    public static function getInstance(): static {
        return Context::getRepository(static::getEntityClass());
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
        return $this->$func();
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

    public function getOptions(): array {
        $options = [];
        foreach ($this->findAll() as $entity) {
            $options[$entity->getId()] = (string)$entity;
        }
        return $options;
    }

    public function update(PinakesEntity $entity, string $name, mixed $value): void {
        $field = $this->data_fields[$name];

        $edit = $field['edit'] ?? true;
        if (!$edit) return;

        // callback
        if (isset($field['edit_callback'])) {
            $callback = $field['edit_callback'];
            assert(is_callable($callback));
            $callback($entity, $value);
            return;
        }

        // map value
        $key = is_string($edit) ? $edit : $field['data'];

        $meta = $this->getClassMetadata();
        if ($meta->hasField($key)) {
            $value = $value = match($meta->getFieldMapping($key)->type) {
                'integer' => intval($value),
                'float' => floatval($value),
                'datetime' => new DateTime($value),
                default => $value
            };
        } else if ($meta->hasAssociation($key)) {
            $target = $meta->getAssociationMapping($key)->targetEntity;
            $target_repository = Context::getRepository($target);

            if ($meta->isSingleValuedAssociation($key)) {
                $value = empty($value) ? null : $target_repository->getOrCreate($value, false);
            } else if ($meta->isCollectionValuedAssociation($key)) {
                $entities = array_map(fn ($e) => $target_repository->getOrCreate($e, false), $value);
                $value = new ArrayCollection($entities);
            }
        } else {
            assert(false, 'Unkown field ' . $key);
        }

        // set value
        $entity->setValue($key, $value);
    }
}
