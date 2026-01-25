<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Context;
use App\Pinakes\ViewElement;
use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use App\Pinakes\EntityCollection;
use App\Pinakes\FormElement;
use App\Pinakes\Renderer;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;
use ReflectionClass;
use ReflectionProperty;
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

class AppExtension extends AbstractExtension {

    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private Security $security,
        private Environment $twig
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
            new TwigFilter('fmt_currency', fn(float $value) => Renderer::RenderCurrency($value)),
        ];
    }

    public function getFilterUrl(string $route, array $params, array ...$filters): string {
        $params['filter'] = http_build_query(array_merge(...$filters));
        return $this->router->generate($route, $params);
    }

    public function getNavigationItems(): array {
        $filename = Context::getAbsolutePath('/data/navigation.json');
        assert(file_exists($filename));

        $content = file_get_contents($filename);
        if (!$content) return [];

        $items = json_decode($content, true);
        return array_filter($items, fn ($item) => isset($item['role']) ? $this->security->isGranted($item['role']) : true);
    }

    public function getIcon(string $name): ?Markup {
        $filename = Context::getAbsolutePath('/public/icons/bootstrap/' . $name . '.svg');
        if (!file_exists($filename)) return null;
        return new Markup(file_get_contents($filename), 'UTF-8');
    }

    public function renderValue(array $field, PinakesEntity $entity): string {
        // Step 1: Get data
        assert(isset($field['data']), 'No data specified');

        if (is_callable($field['data'])) {
            $data = $field['data']($entity);
        } else {
            $data = $entity->getValue($field['data']);
        }

        if (empty($data) || ($data instanceof Collection && $data->isEmpty())) return '-';

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
        $render = $field['render'] ?? null;
        if (is_callable($render)) return $render($data);

        if (is_iterable($data)) return Renderer::RenderCollection($data);
        if ($data instanceof \DateTime) return $data->format('d.m.Y');
        if ($data instanceof ViewElement) return $data->getHtml();
        return (string) $data;
    }

    public function renderForm(string $name, array $field, PinakesEntity $entity): string {
        $edit = $field['edit'] ?? true;
        if (!$edit) return '';

        // Step 1: Get data
        if (is_string($edit)) {
            $property = $edit;
            $data = $entity->getValue($edit);
        } else if (is_callable($field['data'])) {
            $data = $field['data']($entity);
            $property = null;
            assert(is_callable($field['edit_callback'] ?? null), 'No property or callback provided');
        } else {
            $property = $field['data'];
            $data = $entity->getValue($property);
        }

        // Step 2: Get form element
        if ($data instanceof PersistentCollection) {
            $entity_name = $data->getTypeClass()->rootEntityName;
            $repository = $this->em->getRepository($entity_name);
            return FormElement::autocomplete($repository->getOptions(), $data)->render($this->twig, $name);
        }

        if ($data instanceof EntityCollection) {
            $entity_name = $data->getTypeClass();
            $repository = $this->em->getRepository($entity_name);
            return FormElement::autocomplete($repository->getOptions(), $data)->render($this->twig, $name);
        }

        $property_type = (new ReflectionProperty($entity, $property))->getType();
        if (!$property_type->isBuiltin()) {
            $class_name = $property_type->getName();
            $reflection = new ReflectionClass($class_name);

            if ($reflection->isSubclassOf(PinakesEntity::class)) {
                $repository = $this->em->getRepository($class_name);
                return FormElement::autocomplete($repository->getOptions(), $data)->render($this->twig, $name);
            }
        }

        $type = $field['input_type'] ?? 'text';
        if ($data instanceof \DateTime) {
            if ('text' === $type) $type = PinakesRepository::INPUT_DATE;
            $data = $data->format('Y-m-d');
        }

        return $this->twig->render('/elements/form/input.html.twig', [
            'name' => $name,
            'type' => $type,
            'value' => $data,
        ]);
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

        if (empty($data) || ($data instanceof ArrayCollection && $data->isEmpty())) return '-';

        // Step 2: Render data
        $render = $field['render'] ?? null;
        if (is_callable($render)) return $render($data);

        if (is_iterable($data)) return Renderer::RenderCollection($data);
        if ($data instanceof \DateTime) return $data->format('d.m.Y');
        if ($data instanceof ViewElement) return $data->getHtml();
        return (string) $data;
    }

    public function renderFilter(string $name, array $filter): string {
        $form_element = $filter['form'];
        return $form_element->render($this->twig, $name);
    }
}
