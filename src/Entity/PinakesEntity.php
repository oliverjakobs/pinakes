<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use ReflectionClass;
use ReflectionProperty;
use App\Pinakes\Context;
use App\Pinakes\EntityCollection;
use App\Pinakes\ViewElement;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public function getModelName(): string {
        $reflection = new ReflectionClass($this);
        return strtolower($reflection->getShortName());
    }

    public function getLinkSelf(?string $value = null): ViewElement {
        $url = '/' . $this->getModelName() . '/show/' . $this->getId();
        return ViewElement::anchor($value ?? (string)$this, $url);
    }

    public function setValue(string $key, mixed $value) {
        if (property_exists($this, $key)) {
            $property_type = (new ReflectionProperty($this, $key))->getType();

            if (!$property_type->isBuiltin()) {
                $class_name = $property_type->getName();
                $reflection = new ReflectionClass($class_name);

                if ($reflection->isSubclassOf(PinakesEntity::class)) {
                    $value = empty($value) ? null : Context::getRepository($class_name)->getOrCreate($value);
                } else if ($this->$key instanceof PersistentCollection) {
                    $repository = Context::getRepository($this->$key->getTypeClass()->rootEntityName);
                    $entities = array_map(fn ($e) => $repository->getOrCreate($e, false), $value);
                    $value = new ArrayCollection($entities);
                }
            } else if ('int' === $property_type->getName()) {
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
