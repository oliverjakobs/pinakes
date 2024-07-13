<?php

namespace App\Repository;

use App\Entity\Author;
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

    public static function getLinkDoi(string $doi): string {
        return self::getLink('https://www.doi.org/' . $doi, $doi);
    }

    public static function getName(): string {
        return 'papers';
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
                'data' => 'doi',
                'default' => '-',
                'link' => fn(string $p) => self::getLinkDoi($p),
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
