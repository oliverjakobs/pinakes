<?php

namespace App\Repository;

use App\Entity\Genre;
use function App\Pinakes\RenderColored;
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
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'color' => array(
                'caption' => 'Color',
                'data' => 'color',
                'render' => fn($data) => RenderColored('div', $data, $data, 'tag'),
                'input_type' => 'color',
            ),
            'show' => [
                'data' => fn(Genre $g) => $g->getLinkShow(),
                'edit' => false,
                'style_class' => 'fit-content'
            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'color', 'show'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'name', 'color'
        ));
    }
}
