<?php declare(strict_types=1);

namespace App\Twig;

use App\Entity\PinakesEntity;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension {
    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
            new TwigFunction('order_icon', [$this, 'getOrderIcon']),
            new TwigFunction('order_query', [$this, 'getOrderQuery']),
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

    private static function getOrderDir(Request $request): string {
        $dir = $request->query->get('order_dir', 'asc');
        return  $dir === 'asc' ? 'desc' : 'asc';
    }
    private static function getOrderBy(array $field): string {
        if (isset($field['order'])) return $field['order'];
        return $field['data'];
    }

    public function getOrderIcon(Request $request, array $field): string {
        $orderby = $request->query->get('orderby');
        if (self::getOrderBy($field) !== $orderby) return '';
        return self::getOrderDir($request);
    }

    public function getOrderQuery(Request $request, array $field): string {
        return http_build_query([
            'orderby' => self::getOrderBy($field),
            'order_dir' => self::getOrderDir($request)
        ]);
    }
}