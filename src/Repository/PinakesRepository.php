<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

abstract class PinakesRepository extends ServiceEntityRepository {

    const LINK_SELF = 'link_self';
    const LINK_DATA = 'link_data';

    const INPUT_DATE = 'date';
    const INPUT_DATETIME = 'datetime-local';
    const INPUT_TIME = 'time';

    private array $data_fields;

    public function __construct(ManagerRegistry $registry, string $entityClass) {
        parent::__construct($registry, $entityClass);
        $this->data_fields = $this->defineDataFields();
    }
    
    abstract protected function defineDataFields(): array;

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

    public function getDefaultOrder(): array {
        return [];
    }

    public function findAll(?array $order_by = null, ?int $limit = null, ?int $offset = null): array {
        return $this->findBy([], $order_by ?? $this->getDefaultOrder(), $limit, $offset);
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
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
        return $this->getQueryBuilder($filter)->getQuery()->getResult();
    }

    public function getOptions(): array {
        $options = [];
        foreach ($this->findAll() as $entity) {
            $options[$entity->getId()] = (string)$entity;
        }
        return $options;
    }

    abstract public function getSearchKey(): string;

    public function update(PinakesEntity $entity, string $key, mixed $value) {
        $field = $this->data_fields[$key];

        $edit = $field['edit'] ?? true;
        if (!$edit) return;

        $callback = $field['edit_callback'] ?? null;
        if (is_callable($callback)) {
            $callback($entity, $value);
        } else {
            if (is_string($edit)) {
                $key = $edit;
            } else {
                $key = $field['data'];
            }

            $entity->setValue($key, $value);
        }
        $this->save($entity);
    }
}
