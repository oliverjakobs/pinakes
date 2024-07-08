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

    public function getFields(): array {
        return [
            array(
                'name' => 'name',
                'caption' => 'Title',
                'link' => fn(Author $a) => '/authors/' . $a->getId(),
            ),
            array(
                'name' => 'papers',
                'data' => fn(Author $a) => $a->getPapers()->count(),
                'caption' => 'Papers',
            ),
            array(
                'name' => 'books',
                'data' => fn(Author $a) => $a->getBooks()->count(),
                'caption' => 'Books',
            ),
        ];
    }
}
