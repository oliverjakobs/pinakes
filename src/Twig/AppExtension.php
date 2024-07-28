<?php

namespace App\Twig;

use App\Entity\PinakesEntity;
use Doctrine\Common\Collections\Collection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension {
    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
        ];
    }

    private static function getData(callable|string $data, PinakesEntity $entity): mixed {
        if (is_callable($data)) {
            return $data($entity);
        }

        if ('self' === $data) {
            return $entity;
        }

        if (method_exists($entity, $data)) {
            return $entity->{$data}();
        }

        $name = 'get' . ucwords($data, '-');
        return $entity->{$name}();
    }

    private static function getLink(array $field, mixed $value): string {
        if (!isset($field['link'])) return (string) $value;

        return $field['link']($value);
    }

    public function getValue(array $field, PinakesEntity $entity): string {
        assert(isset($field['data']), 'No data specified');
        $data = self::getData($field['data'], $entity);

        if (null === $data) {
            return $field['default'] ?? '';
        }

        if ($data instanceof Collection) {
            $array = array_map(fn ($a) => self::getLink($field, $a), $data->toArray());
            return implode('; ', $array);
        }

        return self::getLink($field, $data);
    }
}