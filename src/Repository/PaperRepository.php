<?php

namespace App\Repository;

use App\Entity\Paper;
use Doctrine\Persistence\ManagerRegistry;

class PaperRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Paper::class);
    }

   /**
    * @return Paper[] Returns an array of Paper objects
    */
    public function findLikeTitle(?string $title): array {
        return $this->findLike('title', $title);
    }

    public function getFields(): array {
        return [
            array(
                'name' => 'title',
                'caption' => 'Title',
                'link' => fn(Paper $p) => '/papers/' . $p->getId(),
            ),
            array(
                'name' => 'authors',
                'caption' => 'Author(s)',
            ),
            array(
                'name' => 'releaseYear',
                'caption' => 'Release Year',
            ),
            array(
                'name' => 'doi',
                'caption' => 'DOI',
                'default' => '-'
            ),
        ];
    }
}
