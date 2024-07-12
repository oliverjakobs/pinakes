<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class PinakesRepository extends ServiceEntityRepository {

    public function save(object $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($flush) $em->flush();
    }

    public function delete(object $entity, bool $flush = true) {
        $em = $this->getEntityManager();
        $em->remove($entity);

        if ($flush) $em->flush();
    }

    public function findLike(string $key, ?string $value): array {
        if (is_null($value) || empty($value)) return $this->findAll();

        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere($qb->expr()->like('p.' . $key, ':value'))
            ->setParameter('value', '%' . $value . '%')
            ->getQuery()
            ->getResult();
    }

    public static function getLink(string $href, string $value): string {
        return sprintf('<a href="%s">%s</a>', $href, $value);
    }

    public static function getLinkSelf(object $entity, ?string $value = null): string {
        $href = '/' . get_called_class()::getName() . '/' .  $entity->getId();
        return self::getLink($href, $value ?? (string)$entity);
    }

    abstract public static function getName(): string;
    abstract public function getFields(): array;
}
