<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Pinakes;
use App\Entity\PinakesEntity;
use App\Pinakes\DataType;
use App\Pinakes\Helper;
use App\Repository\PinakesRepository;
use App\Pinakes\Renderer;
use App\Renderable\ViewElement;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

class AppExtension extends AbstractExtension {

    public function __construct(
        private RouterInterface $router,
        private Security $security,
    ) {
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('render_value', [$this, 'renderValue']),
            new TwigFunction('render_form', [$this, 'renderForm']),
            new TwigFunction('render_filter', [$this, 'renderFilter']),
            new TwigFunction('export', [$this, 'exportValue']),
            new TwigFunction('filter_url', [$this, 'getFilterUrl']),
            new TwigFunction('navigation_items', [$this, 'getNavigationItems']),
            new TwigFunction('icon', [$this, 'getIcon'])
        ];
    }

    public function getFilters(): array {
        return [
            new TwigFilter('fmt_currency', fn(float $value) => DataType::currency()->render($value)),
        ];
    }

    public function getFilterUrl(string $route, array $params, array ...$filters): string {
        $params['filter'] = http_build_query(array_merge(...$filters));
        return $this->router->generate($route, $params);
    }

    public function getNavigationItems(): array {
        $filename = Pinakes::getAbsolutePath('/data/navigation.json');
        assert(file_exists($filename));

        $content = file_get_contents($filename);
        if (!$content) return [];

        $items = json_decode($content, true);
        return array_filter($items, fn ($item) => isset($item['role']) ? $this->security->isGranted($item['role']) : true);
    }

    public function getIcon(string $name): ?Markup {
        $filename = Pinakes::getAbsolutePath('/public/icons/bootstrap/' . $name . '.svg');
        if (!file_exists($filename)) return null;
        return new Markup(file_get_contents($filename), 'UTF-8');
    }

    public function renderValue(PinakesEntity $entity, array $field): string {
        // Step 1: Get data
        /** @var DataType data_type */
        [$data, $data_type] = PinakesRepository::parseDataField($entity, $field);

        // Step 2: Apply link
        if (null !== $data) {
            $link = $field['link'] ?? null;
            if (PinakesRepository::LINK_SELF === $link) {
                assert(!is_iterable($data), 'Iterables can only link to data');
                $data = $entity->getLinkSelf((string) $data);
            } else if (PinakesRepository::LINK_DATA === $link) {
                if (is_iterable($data)) {
                    if ($data instanceof Collection) $data = $data->toArray();
                    $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
                } else {
                    assert($data instanceof PinakesEntity, 'Can only link to entities');
                    $data = $data->getLinkSelf((string) $data);
                }
            } else {
                assert(null === $link, 'Unkown link type');
            }
        }

        // Step 3: Render data
        $value = $data_type->render($data);
        return ViewElement::create('td', $value)->addClasses($data_type->getStyleClasses())->render();
    }

    public function renderForm(PinakesEntity $entity, string $name, array $field): string {
        $edit = $field['edit'] ?? true; // TODO default to false
        if (!$edit) return '';

        // Step 1: Get data
        [$data, $data_type] = PinakesRepository::parseDataField($entity, $field, PinakesRepository::MODE_EDIT);

        // Step 2: Get form element
        $form = $data_type->getForm($name, $data);
        return $form->render();
    }

    // TODO test
    public function exportValue(PinakesEntity $entity, array $field): string {
        // Step 1: Get data
        /** @var DataType data_type */
        [$data, $data_type] = PinakesRepository::parseDataField($entity, $field, PinakesRepository::MODE_EXPORT);

        // Step 3: Render data
        return $data_type->render($data);
    }

    public function renderFilter(PinakesRepository $repository, string $name, array $field, array $filter): string {
        return $repository->parseFilter($field, $name, $filter[$name] ?? null)->render();
    }
}
