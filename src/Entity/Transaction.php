<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\ViewElement;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
class Transaction extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    public ?float $amount = null;

    #[ORM\Column(length: 255)]
    public ?string $reason = null;

    #[ORM\Column]
    public ?\DateTime $timestamp = null;

    public function __toString(): string {
        return $this->reason;
    }

    public function getId(): ?int {
        return $this->id;
    }
}
