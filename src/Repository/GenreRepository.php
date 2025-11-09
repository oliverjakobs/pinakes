<?php

namespace App\Repository;

use App\Entity\Genre;
use App\Entity\User;
use App\Pinakes\ViewElement;
use Doctrine\Persistence\ManagerRegistry;

class GenreRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Genre::class);
    }

    public function getSearchKey(): string{
        return 'name';
    }

    public function getOrCreate(string $name, bool $flush = true): Genre {
        $genre = $this->findOneBy(['name' => $name]);
        if (null === $genre) {
            $genre = new Genre();
            $genre->name = $name;
            $genre->color = '#ffffff';
            $this->save($genre, $flush);
        }

        return $genre;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'color' => [
                'caption' => 'Color',
                'data' => 'color',
                'render' => fn($data) => ViewElement::tag($data, $data)->addClasses(['monospace'])->getHtml(),
                'input_type' => 'color',
            ],
            'show' => [
                'data' => fn(Genre $g) => $g->getLinkShow(),
                'edit' => false,
                'style_class' => 'fit-content',
                'visibility' => User::ROLE_LIBRARIAN
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Genre $g) => $g->books->count(),
                'style_class' => 'align-right fit-content'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'name', 'color', 'book_count', 'show'
        ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([
            'name', 'color'
        ]);
    }
}
