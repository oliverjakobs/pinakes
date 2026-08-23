<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Database;
use App\Database\SQL\Sum;
use App\Database\Mapping;

class Transaction extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?float $amount = null;

    #[Mapping\Column]
    public ?string $reason = null;

    #[Mapping\Column]
    public ?\DateTime $timestamp = null;

    public function __construct() {
        $this->timestamp = new \DateTime();
    }

    public function __toString(): string {
        return $this->reason;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public static function getBalance(): float {
        $sum = new Sum(self::getMapper()->getTableName(), 'amount');
        return Database::getInstance()->fetchColumn($sum);
    }
}
