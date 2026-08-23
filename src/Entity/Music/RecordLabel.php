<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\RecordLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecordLabelRepository::class)]
class RecordLabel extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /** @var Collection<int, Record> */
    #[ORM\OneToMany(targetEntity: Record::class, mappedBy: 'label')]
    public Collection $records;

    public function __construct() {
        $this->records = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown label';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
