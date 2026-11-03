<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class BoardgamePublisher extends PinakesEntity {
    #[Mapping\Column]
    public string $name = 'Unknown publisher';

    /** @var Boardgame[] */
    #[Mapping\OneToMany(target: Boardgame::class, mapped_by: 'publisher')]
    public array $boardgames;

    #[Mapping\Count(property: 'boardgames')]
    public int $boardgame_count = 0;

    public function __toString(): string {
        return $this->name;
    }
}
