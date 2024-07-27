<?php

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Author::class);
    }
    
    public function getOrCreate(string $name): Author {
        $author = $this->findOneBy(['name' => $name]);
        if (null !== $author) return $author;
        
        $author = new Author();
        $author->setName($name);
        $this->save($author);
        return $author;
    }

   /**
    * @return Author[] Returns an array of Author objects
    */
    public function findLikeName(?string $name): array {
        return $this->findLike('name', $name);
    }

    public static function getName(): string {
        return 'authors';
    }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'self',
                'link' => fn(Author $a) => self::getLinkSelf($a),
            ),
            'paper_count' => array(
                'caption' => 'Papers',
                'data' => fn(Author $a) => $a->getPapers()->count(),
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->getBooks()->count(),
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->getDataFields(array(
            'name', 'paper_count', 'book_count'
        ));
    }
}
