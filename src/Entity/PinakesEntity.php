<?php declare(strict_types=1);

namespace App\Entity;

use ReflectionClass;
use App\Pinakes\Pinakes;
use App\Renderable\Link;
use App\Repository\PinakesRepository;

abstract class PinakesEntity {
    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public static function getRepository(): PinakesRepository {
        return Pinakes::getRepository(static::class);
    }

    public function getModelName(): string {
        $reflection = new ReflectionClass($this);
        return strtolower($reflection->getShortName());
    }

    public function getLinkSelf(?string $caption = null): Link {
        return Link::create($caption ?? (string)$this, $this->getModelName() . '_show', ['id' => $this->getId()]);
    }

    public function getLinkEdit(?string $caption = null): Link {
        return Link::modal($caption ?? 'Edit', $this->getModelName() . '_modal', ['id' => $this->getId()]);
    }

    public function getLinkDelete(?string $caption = null): Link {
        return Link::delete($caption ?? 'Delete', $this->getModelName() . '_delete', ['id' => $this->getId()]);
    }

    public function setValue(string $key, mixed $value) {
        if (property_exists($this, $key)) {
            $this->$key = $value;
            return;
        }

        $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
            return;
        }

        assert(false, 'Cant set ' . $key);
    }

    public function getValue(string $property): mixed {
        assert(property_exists($this, $property), 'Unknown property ' . $property);
        return $this->$property;
    }
}
