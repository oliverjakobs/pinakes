<?php declare(strict_types=1);

namespace App\Entity;

use App\Pinakes\DataType;
use ReflectionClass;
use App\Pinakes\ViewElement;
use App\Pinakes\Pinakes;
use App\Repository\PinakesRepository;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ReflectionProperty;

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

    public function getLinkSelf(?string $caption = null): ViewElement {
        $url = Pinakes::getUrl($this->getModelName() . '_show', ['id' => $this->getId()]);
        return ViewElement::anchor($caption ?? (string)$this, $url);
    }

    public function getLinkEdit(?string $caption = null): ViewElement {
        $url = Pinakes::getUrl($this->getModelName() . '_modal', ['id' => $this->getId()]);
        return ViewElement::buttonModal($caption ?? 'Edit', $url);
    }

    public function getLinkDelete(?string $caption = null): ViewElement {
        $url = Pinakes::getUrl($this->getModelName() . '_delete', ['id' => $this->getId()]);
        return ViewElement::hxButton($caption ?? 'Delete', $url, 'DELETE');
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
