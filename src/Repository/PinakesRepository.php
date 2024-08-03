<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class PinakesRepository extends ServiceEntityRepository {

    private array $dataFields;

    public function __construct(ManagerRegistry $registry, string $entityClass) {
        parent::__construct($registry, $entityClass);
        $this->dataFields = $this->defineDataFields();
    }

    public function getEntityName(): string {
        return call_user_func($this->_entityName . '::getClassName');
    }
    
    public function getDataFields(?array $names = null): array {
        if (null === $names) return $this->dataFields;

        return array_filter($this->dataFields, fn ($e) => in_array($e, $names), ARRAY_FILTER_USE_KEY);
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

    public function findAll(?array $orderBy = null, $limit = null, $offset = null): array {
        return $this->findBy([], $orderBy, $limit, $offset);
    }

    public function findLike(string $key, ?string $value, ?array $orderBy = null): array {
        if (is_null($value) || empty($value)) return $this->findAll();

        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere($qb->expr()->like('p.' . $key, ':value'))
            ->setParameter('value', '%' . $value . '%')
            ->getQuery()
            ->getResult();
    }

    abstract protected function defineDataFields(): array;
    abstract public function search(?string $search): array;
}
