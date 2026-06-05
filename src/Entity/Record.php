<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\RecordRepository;
use Doctrine\ORM\Mapping as ORM;

enum Medium: string {
    case CD = 'cd';
    case Vinyl = 'vinyl';
}

#[ORM\Entity(repositoryClass: RecordRepository::class)]
class Record extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'records', cascade: ['persist'])]
    public ?Artist $artist = null;

    #[ORM\ManyToOne(inversedBy: 'records', cascade: ['persist'])]
    public ?RecordLabel $label = null;

    #[ORM\Column(enumType: Medium::class)]
    public ?Medium $medium = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTime $released = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    public ?\DateTime $created_at = null;

    public function __toString(): string {
        return $this->title ?? 'Untitled record';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setMedium(string $medium): self {
        $this->medium = Medium::from($medium);
        return $this;
    }
}
