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
            new TwigFunction('order_dir', [$this, 'getOrderDir']),
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
            return $field['default'] ?? '-';
        }

        if ($data instanceof Collection) {
            $array = array_map(fn ($a) => self::getLink($field, $a), $data->toArray());
            return implode('; ', $array);
        }

        return self::getLink($field, $data);
    }

    private static function getOrderBy(array $field): string {
        return $field['order'] ?? $field['data'];
    }

    public function getOrderDir(Request $request, array $field): string {
        $orderby = $request->query->get('order_by');
        if (self::getOrderBy($field) !== $orderby) return '';

        return $request->query->get('order_dir', 'desc');
    }

    public function getOrderQuery(Request $request, array $field, string $dir): string {
        if (empty($dir)) $dir = 'desc';

        return http_build_query([
            'search' => $request->get('search'),
            'order_by' => self::getOrderBy($field),
            'order_dir' => $dir === 'asc' ? 'desc' : 'asc'
        ]);
    }
}