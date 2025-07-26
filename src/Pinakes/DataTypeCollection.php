<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use Doctrine\Common\Collections\Collection;

class DataTypeCollection extends DataTypeEntity {
    public ?string $separator = null;

    public function setInline(string $separator): static {
        $this->separator = $separator;
        return $this;
    }

    public function renderValue(PinakesEntity $entity, mixed $data, mixed $link): string {
        assert(is_iterable($data), 'data is not iterable');
        if ($data instanceof Collection) $data = $data->toArray();

        if (null !== $link) {
            assert(PinakesRepository::LINK_DATA === $link, 'Iterables can only link to data');
            $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
        }

        if (null !== $this->separator) return implode($this->separator, $data);

        $li = implode(PHP_EOL, array_map(fn ($e) => '<li>' . $e . '</li>', $data));
        return <<<HTML
        <ul>
        $li
        </ul>
        HTML;
    }
}
