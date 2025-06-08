<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Link;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension {

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
            new TwigFunction('get_form', [$this, 'getForm']),
        ];
    }

    private static function getData(array $field, PinakesEntity $entity): mixed {
        assert(isset($field['data']), 'No data specified');
        $data = $field['data'];

        if (is_callable($data)) {
            return $data($entity);
        }

        $name = 'get' . str_replace('_', '', ucwords($data, '_'));
        return $entity->{$name}();
    }

    public function getValue(array $field, PinakesEntity $entity): string|array|Link {
        $data = self::getData($field, $entity);

        if (null === $data) return $field['default'] ?? '-';

        $link = $field['link'] ?? null;

        if ($data instanceof Collection) {
            $data = $data->toArray();
            if (null !== $link) {
                assert(PinakesRepository::LINK_DATA === $link, 'Collections can only link to data');
                $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
            }
            return $data;
        }

        if (null === $link) return (string) $data;

        if (PinakesRepository::LINK_SELF === $link) {
            return $entity->getLinkSelf();
        }

        if (PinakesRepository::LINK_DATA === $link) {
            assert($data instanceof PinakesEntity, 'Can only link to entities');
            return $data->getLinkSelf();
        }

        if (is_callable($link)) {
            return $link($entity);
        }

        throw new Exception('Unkown link type');
    }

    public function getForm(string $name, array $field, PinakesEntity $entity): array {
        if (isset($field['edit']) && !$field['edit']) return [];

        $data = self::getData($field, $entity);

        if ($data instanceof Collection) {
            assert(isset($field['data_type']), 'Collections need "data_type" to be set');
            $repository = $this->em->getRepository($field['data_type']->entity);
            return [
                'path' => '/component/autocomplete.html.twig',
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ];
        }

        if ($data instanceof PinakesEntity) {
            $repository = $this->em->getRepository($data::class);
            return [
                'path' => '/component/autocomplete.html.twig',
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ];
        }

        return [
            'path' => '/component/input.html.twig',
            'name' => $name,
            'type' =>'text',
            'value' => $data,
        ];
    }
}
