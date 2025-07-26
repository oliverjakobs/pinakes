<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use ReflectionClass;
use App\Pinakes\Link;
use App\Pinakes\DataTypeEntity;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public static function getClassName(): string {
        $reflection = new ReflectionClass(get_called_class());
        return strtolower($reflection->getShortName());
    }

    public static function getDataType(): DataTypeEntity {
        return new DataTypeEntity(static::class);
    }

    public function getLinkSelf(?string $value = null): Link {
        $url = '/' . self::getClassName() . '/show/' .  $this->getId();
        return new Link($value ?? (string)$this, $url);
    }
}
