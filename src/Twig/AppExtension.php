<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Link;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

class AppExtension extends AbstractExtension {

    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(EntityManagerInterface $em, RouterInterface $router) {
        $this->em = $em;
        $this->router = $router;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
            new TwigFunction('get_form', [$this, 'getForm']),
            new TwigFunction('route_exists', [$this, 'routeExists']),
            new TwigFunction('icon', [$this, 'getIcon'])
        ];
    }

    public function getFilters(): array {
        return [
            new TwigFilter('fmt_currency', [$this, 'formatCurrency']),
        ];
    }

    public function formatCurrency(float $amount): string {
        return sprintf('%.2f €', $amount);
    }

    public function routeExists(string $name): bool {
        try {
            $this->router->generate($name);
        } catch (RouteNotFoundException $e) {
            return false;
        }
        return true;
    }

    public function getIcon(string $name): ?Markup {
        $filename = 'icons/bootstrap/' . $name . '.svg';
        if (!file_exists($filename)) return null;
        return new Markup(file_get_contents($filename), 'UTF-8');
    }

    private static function getData(array $field, PinakesEntity $entity): mixed {
        assert(isset($field['data']), 'No data specified');
        $data = $field['data'];

        if (is_callable($data)) {
            return $data($entity);
        }

        if (array_key_exists($data, get_object_vars($entity))) {
            return $entity->$data;
        }

        $name = 'get' . str_replace('_', '', ucwords($data, '_'));
        return $entity->{$name}();
    }

    public function getValue(array $field, PinakesEntity $entity): string|array|Link {
        $data = self::getData($field, $entity);

        if (null === $data) return $field['default'] ?? '-';

        $link = $field['link'] ?? null;

        if (is_iterable($data)) {
            if ($data instanceof Collection) $data = $data->toArray();
            if (null !== $link) {
                assert(PinakesRepository::LINK_DATA === $link, 'Iterables can only link to data');
                $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
            }
            return $data;
        }

        // TODO format data
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

        if (is_iterable($data)) {
            assert(isset($field['data_type']), 'Iterables need "data_type" to be set');
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
