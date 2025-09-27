<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use ReflectionClass;
use App\Pinakes\Context;
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

    public function setValue(string $key, mixed $value) {
        if (property_exists($this, $key)) {
            if ($this->$key instanceof PinakesEntity) {
                $repository = Context::getRepository($this->$key::class);
                $value = $repository->getOrCreate($value);
            } else if (is_int($this->$key)) {
                $value = intval($value);
            }

            $this->$key = $value;
            return;
        }

        $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
            return;
        }

        assert(false, 'Cant set ' . $data);
    }

    public function getValue(string $key): mixed {
        if (property_exists($this, $key)) {
            return $this->$key;
        }

        $getter = 'get' . str_replace('_', '', ucwords($key, '_'));
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        assert(false, 'Cant get ' . $key);
    }
}
