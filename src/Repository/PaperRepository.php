<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Paper;
use Doctrine\Persistence\ManagerRegistry;

class PaperRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Paper::class);
    }

    public static function getName(): string {
        return 'papers';
    }

   /**
    * @return Paper[] Returns an array of Paper objects
    */
    public function findLikeTitle(?string $title): array {
        return $this->findLike('title', $title);
    }

    public static function getLinkDoi(Paper $paper): ?string {
        if (null === $paper->getDoi()) return null;
        return self::getLink('https://www.doi.org/' . $paper->getDoi(), $paper->getDoi());
    }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'self',
                'link' => fn(Paper $p) => self::getLinkSelf($p),
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => fn(Author $a) => AuthorRepository::getLinkSelf($a),
            ),
            'releaseYear' => array(
                'caption' => 'Release Year',
                'data' => 'releaseYear',
            ),
            'doi' => array(
                'caption' => 'DOI',
                'data' => fn(Paper $p) => self::getLinkDoi($p),
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
}
