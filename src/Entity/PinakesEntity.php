<?php declare(strict_types=1);

namespace App\Entity;

use App\Mapper\PinakesMapper;
use ReflectionClass;
use App\Renderable\Link;
use App\Renderable\Renderable;

abstract class PinakesEntity {
    public readonly ?int $id;

    public function __construct(?int $id = null) {
        $this->id = $id;
    }

    abstract public function __toString(): string;

    public static function getMapper(): PinakesMapper {
        return PinakesMapper::getMapper(static::class);
    }

    public function getModelName(): string {
        $reflection = new ReflectionClass($this);
        return strtolower($reflection->getShortName());
    }

    public function equals(PinakesEntity $other): bool {
        return $this->id === $other->id;
    }

    public function getLinkSelf(Renderable|string|null $caption = null): Link {
        return Link::create($caption ?? (string) $this, $this->getModelName() . '_show', ['id' => $this->id]);
    }

    public function getLinkEdit(Renderable|string|null $caption = null): Link {
        return Link::modal($caption ?? 'Edit', $this->getModelName() . '_modal', ['id' => $this->id]);
    }

    public function getLinkDelete(Renderable|string|null $caption = null): Link {
        return Link::delete($caption ?? 'Delete', $this->getModelName() . '_delete', ['id' => $this->id])
            ->setDisabledMessage($this->getMessageDelete());
    }

    public function getMessageDelete(): string {
        return '';
    }
}
