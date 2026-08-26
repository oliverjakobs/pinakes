<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

enum Medium: string {
    case CD = 'cd';
    case Vinyl = 'vinyl';
}

class Record extends PinakesEntity {

    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public string $title = 'Untitled record';

    #[Mapping\ManyToOne(target: Artist::class, inversed_by: 'records')]
    public ?Artist $artist = null;

    #[Mapping\ManyToOne(target: RecordLabel::class, inversed_by: 'records')]
    public ?RecordLabel $label = null;

    #[Mapping\Enum(enum_class: Medium::class)]
    public ?Medium $medium = null;

    #[Mapping\Column]
    public ?\DateTime $released = null;

    #[Mapping\Column]
    public \DateTime $created_at;

    public function __construct() {
        $this->created_at = new \DateTime();
    }

    public function __toString(): string {
        return $this->title;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setMedium(string $medium): self {
        $this->medium = Medium::from($medium);
        return $this;
    }
}
