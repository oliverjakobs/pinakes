<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use ReflectionClass;
use App\Pinakes\Link;
use App\Pinakes\DataType;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public static function getClassName(): string {
        $reflection = new ReflectionClass(get_called_class());
        return strtolower($reflection->getShortName());
    }

    public static function getDataType(): DataType {
        return new DataType(static::class);
    }

    public function getLinkSelf(?string $value = null): Link {
        $url = '/' . self::getClassName() . '/show/' .  $this->getId();
        return new Link($value ?? (string)$this, $url);
    }

    public static function toHtmlList(Collection $collection, bool $link): string {
        if ($collection->isEmpty()) return '';
        if (1 === $collection->count()) {
            return $link ? $collection->first()->getLinkSelf()->getHTML() : (string) $collection->first();
        }

        $result = '';
        foreach ($collection as $entry) {
            $result .= '<li>' . ($link ? $entry->getLinkSelf()->getHTML() : (string) $entry) . '</li>';
        }

        return '<ul>' . $result . '</ul>';
    }
}
