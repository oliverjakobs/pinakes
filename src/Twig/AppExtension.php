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
            new TwigFunction('query_order', [$this, 'getOrder']),
            new TwigFunction('order_icon', [$this, 'getOrderIcon']),
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

    private function parseOrderString(?string $order_by): array {
        if (null === $order_by) return ['', ''];
        $result = explode(' ', $order_by);

        $key = $result[0] ?? '';
        $dir = $result[1] ?? '';

        return [$key, $dir];
    }

    public function getOrder(Request $request, string $name): string {
        list($key, $dir) = $this->parseOrderString($request->query->get('orderby'));

        if (strcmp($name, $key) !== 0) {
            $key = $name;
            $dir = '';
        }

        return '?' . http_build_query([
            'orderby' => $key . ' ' . ($dir === 'asc' ? 'dsc' : 'asc')
        ]);
    }

    public function getOrderIcon(Request $request, string $name): string {
        $order_by = $request->query->get('orderby');
        if (null === $order_by) return '';

        list($key, $dir) = $this->parseOrderString($order_by);

        if (strcmp($name, $key) !== 0) return '';
        return 'order-' . $dir;
    }
}