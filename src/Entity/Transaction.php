<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Database;
use App\Database\SQL\Sum;
use App\Database\Mapping;
use App\Database\SQL\Query;

class Transaction extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?float $amount = null;

    #[Mapping\Column]
    public ?string $reason = null;

    #[Mapping\Column]
    public \DateTime $timestamp;

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
        $query = new Query(self::getMapper()->getTableName());
        $query->addSelect('SUM(amount)');
        return Database::getInstance()->fetchColumn($query) ?? 0.0;
    }
}
