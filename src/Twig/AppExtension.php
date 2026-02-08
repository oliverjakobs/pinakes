<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Pinakes;
use App\Entity\PinakesEntity;
use App\Pinakes\DataType;
use App\Pinakes\Helper;
use App\Repository\PinakesRepository;
use App\Pinakes\Renderer;
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

    public function renderValue(array $field, PinakesEntity $entity): string {
        // Step 1: Get data
        [$data, $data_type] = PinakesRepository::parseDataField($field, $entity);

        if (null === $data_type) return '-';

        // Step 2: Apply link
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

        // Step 3: Render data
        return $data_type->render($data);
    }

    public function renderForm(string $name, array $field, PinakesEntity $entity): string {
        $edit = $field['edit'] ?? true;
        if (!$edit) return '';

        // Step 1: Get data
        [$data, $data_type] = PinakesRepository::parseDataField($field, $entity, PinakesRepository::MODE_EDIT);

        // Step 2: Get form element
        $form = $data_type->getForm($name, $data);
        return $form->render();
    }

    // TODO simplify
    public function exportValue(array $field, PinakesEntity $entity): string {
        // Step 1: Get data
        assert(isset($field['data']), 'No data specified');

        if (is_callable($field['data'])) {
            $data = $field['data']($entity);
        } else {
            $data = $entity->getValue($field['data']);
        }

        if (null === $data 
            || (!is_scalar($data) && empty($data)) 
            || ($data instanceof ArrayCollection && $data->isEmpty())) {
            return '-';
        }

        // Step 2: Render data
        return (string) $data;
    }

    public function renderFilter(string $name, array $filter): string {
        $form_element = $filter['form'];
        return $form_element->render($name);
    }
}
