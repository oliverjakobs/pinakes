<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;

function RenderCurrency(float $data, string $currency = '€'): string {
    return sprintf('%.2f %s', $data, $currency);
}

function RenderColored(string $element, string $content, string $color, string $class = '', string $attr = ''): string {
    $fg = (hexdec($color) > 0xffffff/2) ? 'black':'white';
    return <<<HTML
        <$element style="background-color:$color;color:$fg;" class="$class" $attr>$content</$element>
    HTML;
}

function RenderCollection(Collection|array $data, int $limit = PHP_INT_MAX): string {
    if ($data instanceof Collection) $data = $data->toArray();

    if (count($data) > $limit) {
        $data = array_slice($data, 0, $limit);
        $data[] = '&#8230;';
    }

    $data = implode(PHP_EOL, array_map(fn ($e) => '<li>' . $e . '</li>', $data));
    return <<<HTML
        <ul class="collection">
            $data
        </ul>
    HTML;
}

function RenderCollectionInline(Collection|array $data, string $separator = ' ', int $limit = PHP_INT_MAX): string {
    if ($data instanceof Collection) $data = $data->toArray();

    if (count($data) > $limit) {
        return implode($separator, array_slice($data, 0, $limit)) . '&#8230;';
    }
    return implode($separator, $data);
}
