<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Series;
use App\Traits\NamedEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class AuthorRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Author::class;
    }

    public function findBySeries(Series $series): Collection {
        if (0 === $series->volumes->count()) return new ArrayCollection();

        $qb = $this->applyOr($this->getQueryBuilder(), $series->volumes, 'MEMBER OF', 'books');
        return new ArrayCollection($qb->getQuery()->getResult());
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->books->count(),
            ],
            'translation_count' => [
                'caption' => 'Translations',
                'data' => fn(Author $a) => $a->translations->count(),
            ],
            'openlibrary' => [
                'caption' => 'OpenLibrary',
                'data' => fn(Author $a) => $a->getLinkOpenLibrary(),
                'edit' => 'openlibrary'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'book_count', 'translation_count' ]);
    }
    
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'openlibrary' ]);
    }
}
