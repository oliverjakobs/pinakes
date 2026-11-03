<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Database;
use App\Database\Mapping;
use App\Database\SQL\Query;

class Transaction extends PinakesEntity {
    #[Mapping\Column]
    public ?float $amount = null;

    #[Mapping\Column]
    public ?string $reason = null;

    #[Mapping\Column]
    public \DateTime $timestamp;

    public function __construct(?int $id = null) {
        parent::__construct($id);
        $this->timestamp = new \DateTime();
    }

    public function __toString(): string {
        return $this->reason;
    }
}
