<?php declare(strict_types=1);

namespace App\Twig;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Exception;
use Symfony\Component\HttpFoundation\Request;
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

        $name = 'get' . str_replace('_', '', ucwords($data, '_'));
        return $entity->{$name}();
    }

    public function getValue(array $field, PinakesEntity $entity): string {
        assert(isset($field['data']), 'No data specified');
        $data = self::getData($field['data'], $entity);

        if (null === $data) return $field['default'] ?? '-';

        if (!isset($field['link'])) return (string) $data;

        $link = $field['link'];
        if (PinakesRepository::LINK_SELF === $link) {
            return $entity->getLinkSelf();
        }

        if (PinakesRepository::LINK_DATA === $link) {
            assert($data instanceof PinakesEntity, 'Can only link to entities');
            return $data->getLinkSelf();
        }

        throw new Exception('Unkown link type');
    }
}