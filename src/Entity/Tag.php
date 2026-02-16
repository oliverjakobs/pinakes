<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use App\Renderable\ViewElement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    #[ORM\Column(length: 9, options: ['default' => '#ffffff'])]
    public ?string $color = null;

    /** @var Collection<int, Book> */
    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'tags')]
    public Collection $books;

    public function __construct() {
        $this->books = new ArrayCollection();
        $this->color = '#ffffff';
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown tag';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTag(): ViewElement {
        return ViewElement::tag((string) $this->getLinkSelf(), $this->color);
    }
}
