<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\User;
use App\Pinakes\ViewElement;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Tag::class);
    }

    public function getSearchKey(): string{
        return 'name';
    }

    public function getDefaultOrder(): array {
        return [ 'name' => 'ASC' ];
    }

    public function getOrCreate(string $name, bool $flush = true): Tag {
        $tag = $this->findOneBy(['name' => $name]);
        if (null === $tag) {
            $tag = new Tag();
            $tag->name = $name;
            $tag->color = '#ffffff';
            $this->save($tag, $flush);
        }

        return $tag;
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
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Tag $t) => $t->books->count(),
                'style_class' => 'align-right fit-content'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'name', 'color', 'book_count'
        ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([
            'name', 'color'
        ]);
    }
}
