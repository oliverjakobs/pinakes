<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Pinakes;
use App\Entity\PinakesEntity;
use App\Pinakes\DataColumn;
use App\Pinakes\DataTable;
use App\Pinakes\DataType;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Markup;

class AppExtension extends AbstractExtension {

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

    public function getFilterUrl(Request $request, DataTable $table, array $filter = []): string {
        $route = $request->attributes->get('_route');
        $params = $request->attributes->get('_route_params');
        return Pinakes::getUrl($route, array_merge(
            $params,
            $table->getFilter(),
            $filter,
            ['filter_only' => true]
        ));
    }

    public function getNavigationItems(): array {
        $filename = Pinakes::getAbsolutePath('/data/navigation.json');
        assert(file_exists($filename));

        $content = file_get_contents($filename);
        if (!$content) return [];

        $items = json_decode($content, true);
        return array_filter($items, fn ($item) => Pinakes::isGranted($item['role'] ?? null));
    }

    public function getIcon(string $name): ?Markup {
        $filename = Pinakes::getAbsolutePath('/public/icons/bootstrap/' . $name . '.svg');
        if (!file_exists($filename)) return null;
        return new Markup(file_get_contents($filename), 'UTF-8');
    }

    public function renderValue(PinakesEntity $entity, DataColumn $col): string {
        return $col->renderValue($entity);
    }

    public function renderForm(PinakesEntity $entity, DataColumn $col): string {
        return $col->renderForm($entity);
    }

    // TODO test
    public function exportValue(PinakesEntity $entity, DataColumn $col): string {
        return $col->renderExport($entity);
    }

    public function renderFilter(DataTable $table, DataColumn $col): string {
        $value = $table->getFilterValue($col->name);
        return $col->getFilterForm($value)->render();
    }
}
