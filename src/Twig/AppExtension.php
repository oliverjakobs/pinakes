<?php

namespace App\Twig;

use Doctrine\Common\Collections\Collection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('entry', [$this, 'renderEntity']),
        ];
    }

    private static function getData(callable|string $data, object $entity): mixed {
        if (is_callable($data)) {
            return $data($entity);
        }

        if ('self' === $data) {
            return $entity;
        }

        $name = 'get' . ucwords($data, '-');
        return $entity->{$name}();
    }

    private static function getLink(mixed $value, array $field): string {
        if (!isset($field['link'])) return (string) $value;

        return $field['link']($value);
    }

    public function renderEntity(object $entity, array $field): string {
        assert(isset($field['data']), 'No data specified');
        $data = self::getData($field['data'], $entity);

        if (null === $data) {
            return $field['default'] ?? '';
        }

        if ($data instanceof Collection) {
            $array = array_map(fn ($a) => self::getLink($a, $field), $data->toArray());
            return implode('; ', $array);
        }

        return self::getLink($data, $field);
    }
}