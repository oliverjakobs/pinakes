<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Link;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

use function App\Pinakes\RenderCollection;
use function App\Pinakes\RenderValue;

class AppExtension extends AbstractExtension {

    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private Environment $twig
    ) {
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('render_value', [$this, 'renderValue']),
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

    public function renderValue(array $field, PinakesEntity $entity): string {
        $data = self::getData($field, $entity);

        if (empty($data)) return '-';
        $link = $field['link'] ?? null;

        if (is_iterable($data)) {
            if (null !== $link) {
                assert(PinakesRepository::LINK_DATA === $link, 'Iterables can only link to data');
                if ($data instanceof Collection) $data = $data->toArray();
                $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
            }
            if (isset($field['render'])) return $field['render']($data);

            return RenderCollection($data);
        }

        $value = isset($field['render']) ? $field['render']($data) : (string) $data;

        if (PinakesRepository::LINK_SELF === $link) {
            return $entity->getLinkSelf($value)->getHtml();
        }

        if (PinakesRepository::LINK_DATA === $link) {
            assert($data instanceof PinakesEntity, 'Can only link to entities');
            return $data->getLinkSelf($value)->getHtml();
        }

        assert(null === $link, 'Unkown link type');
        return $value;
    }

    public function renderForm(string $name, array $field, PinakesEntity $entity): string {
        if (isset($field['edit']) && !$field['edit']) return '';

        $data = self::getData($field, $entity);

        if ($data instanceof PersistentCollection) {
            $entity_name = $data->getTypeClass()->rootEntityName;
            $repository = $this->em->getRepository($entity_name);
            return $this->twig->render('/component/form/autocomplete.html.twig', [
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ]);
        }

        if ($data instanceof PinakesEntity) {
            $repository = $this->em->getRepository($data::class);
            return $this->twig->render('/component/form/autocomplete.html.twig', [
                'name' => $name,
                'options' => $repository->getOptions(),
                'values' => $data,
            ]);
        }

        if ($data instanceof \DateTime) {
            return $this->twig->render('/component/form/input.html.twig', [
                'name' => $name,
                'type' => 'date', // TODO DateTime form different formats (time, datetime-local)
                'value' => $data->format('Y-m-d'),
            ]);
        }

        return $this->twig->render('/component/form/input.html.twig', [
            'name' => $name,
            'type' =>'text',
            'value' => $data,
        ]);
    }
}
