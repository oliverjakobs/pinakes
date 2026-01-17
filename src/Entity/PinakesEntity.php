<?php declare(strict_types=1);

namespace App\Entity;

use ReflectionClass;
use App\Pinakes\ViewElement;
use App\Pinakes\Context;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public function getModelName(): string {
        $reflection = new ReflectionClass($this);
        return strtolower($reflection->getShortName());
    }

    public function getLinkSelf(?string $caption = null): ViewElement {
        $url = Context::getUrl($this->getModelName() . '_show', ['id' => $this->getId()]);
        return ViewElement::anchor($caption ?? (string)$this, $url);
    }

    public function getLinkEdit(?string $caption = null): ViewElement {
        $url = Context::getUrl($this->getModelName() . '_modal', ['id' => $this->getId()]);
        return ViewElement::buttonModal($caption ?? 'Edit', $url);
    }

    public function getLinkDelete(?string $caption = null): ViewElement {
        $url = Context::getUrl($this->getModelName() . '_delete', ['id' => $this->getId()]);
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
