<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;

function RenderCurrency(float $data): string {
    return sprintf('%.2f €', $data);
}

function RenderDateTime(\DateTime $data, string $format = 'd.m.Y'): string {
    return $data->format($format);
}

function RenderColored(string $element, string $content, string $color, string $class = '', string $attr = ''): string {
    $fg = (hexdec($color) > 0xffffff/2) ? 'black':'white';
    return <<<HTML
        <$element style="background-color:$color;color:$fg;" class="$class" $attr>$content</$element>
    HTML;
}

function RenderCollection(Collection|array $data, string $class = ''): string {
    if ($data instanceof Collection) $data = $data->toArray();

    $data = implode(PHP_EOL, array_map(fn ($e) => '<li>' . $e . '</li>', $data));
    return <<<HTML
        <ul class="$class">
            $data
        </ul>
    HTML;
}

function RenderCollectionInline(Collection|array $data, ?int $limit = null): string {
    if ($data instanceof Collection) $data = $data->toArray();

    $separator = '; ';
    if (null !== $limit && count($data) > $limit) {
        return implode($separator, array_slice($data, 0, $limit)) . '&#8230;';
    }

    return implode($separator, $data);
}
