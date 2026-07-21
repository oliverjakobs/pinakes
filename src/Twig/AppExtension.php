<?php declare(strict_types=1);

namespace App\Twig;

use App\Pinakes\Pinakes;
use App\Entity\PinakesEntity;
use App\Entity\User;
use App\Pinakes\DataColumn;
use App\Pinakes\DataTable;
use App\Pinakes\DataType;
use App\Renderable\FormElement;
use App\Renderable\Renderable;
use Symfony\Component\HttpFoundation\Request;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class AppExtension {

    #[AsTwigFilter('fmt_currency')]
    public function formatCurrency(float $value): string {
        return DataType::currency()->render($value);
    }

    #[AsTwigFunction('filter_url')]
    public function getFilterUrl(Request $request, DataTable $table, array $filter = []): string {
        $route = $request->attributes->get('_route');
        $params = $request->attributes->get('_route_params');

        return $table->getFilterUrl($route, array_merge($params, $filter));
    }

    #[AsTwigFunction('navigation_items')]
    public function getNavigationItems(): array {
        $filename = Pinakes::getAbsolutePath('/data/navigation.json');
        assert(file_exists($filename), 'File "' . $filename . '" does not exist');

        $content = file_get_contents($filename);
        if (!$content) return [];

        $items = json_decode($content, true);
        return array_filter($items, fn ($item) => Pinakes::isGranted($item['role'] ?? User::ROLE_USER));
    }

    #[AsTwigFunction('icon', isSafe: ['html'])]
    public function getIcon(string $name): ?string {
        $filename = Pinakes::getAbsolutePath('/public/icons/bootstrap/' . $name . '.svg');
        if (!file_exists($filename)) return null;
        return file_get_contents($filename);
    }

    #[AsTwigFunction('render_value')]
    public function renderValue(DataColumn $col, PinakesEntity $entity): Renderable {
        return $col->renderCell($entity);
    }

    #[AsTwigFunction('render_form')]
    public function renderForm(DataColumn $col, PinakesEntity $entity): FormElement {
        return $col->data_type->getForm($col->name, $col->getData($entity));
    }

    #[AsTwigFunction('render_filter')]
    public function renderFilter(DataTable $table, DataColumn $col): FormElement {
        $value = $table->getFilterValue($col->name);
        return $col->data_type->getForm($col->name, $col->data_type->parse($value));
    }

    #[AsTwigFunction('export')]
    public function exportValue(DataColumn $col, PinakesEntity $entity): string {
        return $col->data_type->export($col->getData($entity));
    }
}
