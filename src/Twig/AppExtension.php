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

    private static function getData(object $entity, array $field): mixed {
        if (isset($field['data'])) {
            assert(is_callable($field['data']), 'Data must be callable');
            return $field['data']($entity);
        }

        $name = 'get' . ucwords($field['name'], '-');
        return $entity->{$name}();
    }

    public function renderEntity(object $entity, array $field): string {
        $default = isset($field['default']) ? $field['default'] : '';
        $result = self::getData($entity, $field) ?? $default;

        if ($result instanceof Collection) $result = implode('; ', $result->toArray());

        if (isset($field['link'])) {
            $result = sprintf('<a href="%s">%s</a>', $field['link']($entity), $result);
        }

        return $result;
    }
}