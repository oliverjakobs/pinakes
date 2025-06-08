<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\Criteria;

abstract class PinakesRepository extends ServiceEntityRepository {

    const LINK_SELF = 'link_self';
    const LINK_DATA = 'link_data';

    private array $dataFields;

    public function __construct(ManagerRegistry $registry, string $entityClass) {
        parent::__construct($registry, $entityClass);
        $this->dataFields = $this->defineDataFields();
    }
    
    abstract protected function defineDataFields(): array;

    protected function composeDataFields(?array $names = null): array {
        if (null === $names) return $this->dataFields;

        return array_filter($this->dataFields, fn ($e) => in_array($e, $names), ARRAY_FILTER_USE_KEY);
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

    public function findAll(?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
        return $this->findBy([], $orderBy, $limit, $offset);
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
        $qb = $this->createQueryBuilder('e');

        if (!empty($filter['search'])) {
            $qb->where($qb->expr()->like('e.' . $this->getSearchKey(), ':search'));
            $qb->setParameter('search', '%' . $filter['search'] . '%');
        }

        if (isset($filter['order_by'])) {
            $qb->orderBy('e.' . $filter['order_by'], $filter['order_dir'] ?? 'asc');
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
}
