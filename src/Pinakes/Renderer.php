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

function RenderCollection(Collection|array $data): string {
    if ($data instanceof Collection) $data = $data->toArray();

    $li = implode(PHP_EOL, array_map(fn ($e) => '<li>' . $e . '</li>', $data));
    return <<<HTML
    <ul>
    $li
    </ul>
    HTML;
}

function RenderCollectionInline(Collection|array $data, ?int $limit = null): string {
    if ($data instanceof Collection) $data = $data->toArray();

    $separator = '; ';
    if (null !== $limit && count($data) > $limit) {
        return implode($separator, array_slice($data, 0, $limit)) . ' ...';
    }

    return implode($separator, $data);
}
