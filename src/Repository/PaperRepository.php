<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Paper;
use Doctrine\Persistence\ManagerRegistry;

class PaperRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Paper::class);
    }

   /** @return Paper[] Returns an array of Paper objects */
    public function search(?string $search, ?array $orderBy = null): array {
        return $this->findLike('title', $search, $orderBy);
    }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'self',
                'link' => fn(Paper $p) => $p->getLinkSelf(),
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => fn(Author $a) => $a->getLinkSelf(),
            ),
            'releaseYear' => array(
                'caption' => 'Release Year',
                'data' => 'releaseYear',
            ),
            'doi' => array(
                'caption' => 'DOI',
                'data' => 'getLinkDoi',
                'default' => '-',
            ),
            'abstract' => array(
                'caption' => 'Abstract',
                'data' => 'abstract',
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->getDataFields(array(
            'title', 'authors', 'releaseYear', 'doi'
        ));
    }

    public function getDataFieldsShow(): array {
        return $this->getDataFields(array(
            'authors', 'releaseYear', 'doi', 'abstract'
        ));
    }
}
