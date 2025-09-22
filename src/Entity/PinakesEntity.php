<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use ReflectionClass;
use App\Pinakes\Link;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public function getModelName(): string {
        $reflection = new ReflectionClass($this);
        return strtolower($reflection->getShortName());
    }

    public function getLinkSelf(?string $value = null): Link {
        $url = '/' . $this->getModelName() . '/show/' . $this->getId();
        return new Link($value ?? (string)$this, $url);
    }

    public function setValue(callable|string $data, mixed $value) {
        if (is_callable($data)) {
            $data($this, $value);
        } else if (method_exists($this, $data)) {
            $this->{$data}($value);
        } else if (property_exists($this, $data)) {
            $this->$data = $value;
        } else {
            assert(false, 'Cant set ' . $data);
        }
    }

    public function getValue(callable|string $data): mixed {
        if (is_callable($data)) {
            return $data($this);
        }

        if (property_exists($this, $data)) {
            return $this->$data;
        }

        $getter = 'get' . str_replace('_', '', ucwords($data, '_'));
        return $this->{$getter}();
    }
}
