<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Link;
use App\Entity\PinakesEntity;
use App\Pinakes\DataType;
use App\Pinakes\DataTypeEntity;
use App\Pinakes\DataTypeCollection;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

class AppExtension extends AbstractExtension {

    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private Environment $twig
    ) {
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('get_value', [$this, 'getValue']),
            new TwigFunction('render_form', [$this, 'renderForm']),
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

    private static function getDataType(array $field, PinakesEntity $entity, $data): DataType {
        if (isset($field['data_type'])) return $field['data_type'];

        if ($data instanceof PinakesEntity) {
            return $data->getDataType();
        }

        return new DataType();
    }

    public function getValue(array $field, PinakesEntity $entity): string|array|Link {
        $data = self::getData($field, $entity);
        $data_type = self::getDataType($field, $entity, $data);

        if (null === $data) return '-';
        return $data_type->renderValue($entity, $data, $field['link'] ?? null);
    }

    public function renderForm(string $name, array $field, PinakesEntity $entity): string {
        if (isset($field['edit']) && !$field['edit']) return '';

        $data = self::getData($field, $entity);
        $data_type = self::getDataType($field, $entity, $data);

        if (is_iterable($data)) {
            $repository = $this->em->getRepository($data_type->entity);
            return $this->twig->render('/component/autocomplete.html.twig', [
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ]);
        }

        if ($data instanceof PinakesEntity) {
            $repository = $this->em->getRepository($data::class);
            return $this->twig->render('/component/autocomplete.html.twig', [
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ]);
        }
        return $this->twig->render('/component/input.html.twig', [
            'name' => $name,
            'type' =>'text',
            'value' => $data,
        ]);
    }
}
