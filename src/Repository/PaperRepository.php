<?php

namespace App\Repository;

use App\Entity\Paper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paper>
 *
 * @method Paper|null find($id, $lockMode = null, $lockVersion = null)
 * @method Paper|null findOneBy(array $criteria, array $orderBy = null)
 * @method Paper[]    findAll()
 * @method Paper[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paper::class);
    }

   /**
    * @return Paper[] Returns an array of Paper objects
    */
    public function findLikeTitle(?string $title): array
    {
        if (is_null($title) or empty($title)) return $this->findAll();

        $qb = $this->createQueryBuilder('p');
        return $qb
            ->andWhere($qb->expr()->like('p.title', ':title'))
            ->setParameter('title', '%' . $title . '%')
            ->getQuery()
            ->getResult();
    }

//    public function findOneBySomeField($value): ?Paper
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
