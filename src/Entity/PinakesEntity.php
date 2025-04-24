<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use ReflectionClass;

abstract class PinakesEntity {

    abstract public function getId(): ?int;
    abstract public function __toString(): string;

    public static function getLink(string $href, string $caption, bool $extern = false): string {
        $attr =  ($extern ? 'class="link-extern" target="_blank" rel="noopener noreferrer" ' : '');
        return sprintf('<a ' . $attr . 'href="%s">%s</a>', $href, $caption);
    }

    public static function getClassName(): string {
        $reflection = new ReflectionClass(get_called_class());
        return strtolower($reflection->getShortName());
    }

    public function getLinkSelf(?string $value = null): string {
        $href = '/' . self::getClassName() . '/show/' .  $this->getId();
        return self::getLink($href, $value ?? (string)$this);
    }

    public static function toHtmlList(Collection $collection, bool $link): string {
        $result = '';

        foreach ($collection as $entry) {
            $result .= '<li>' . ($link ? $entry->getLinkSelf() : (string)$entry) . '</li>';
        }

        return '<ul>' . $result . '</ul>';
    }
}