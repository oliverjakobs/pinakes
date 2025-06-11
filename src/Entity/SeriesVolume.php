<?php

namespace App\Entity;

use App\Repository\SeriesVolumeRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\Link;

#[ORM\Entity(repositoryClass: SeriesVolumeRepository::class)]
class SeriesVolume extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    public ?int $volume = null;

    #[ORM\ManyToOne(inversedBy: 'volumes')]
    public ?Series $series = null;

    #[ORM\OneToOne(inversedBy: 'volume')]
    public ?Book $book = null;

    public static function create(Book $book, int $volume) {
        $result = new self();
        $result->book = $book;
        $result->volume = $volume;

        return $result;
    }

    public function __toString(): string {
        return $this->book?->getTitle() ?? $this->series->name  . ' (Vol. '. $this->volume . ')';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkSelf(?string $value = null): Link {
        if (null === $this->book) return parent::getLinkSelf();
        return $this->book->getLinkSelf($value);
    }
}
