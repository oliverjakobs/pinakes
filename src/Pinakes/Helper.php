<?php declare(strict_types=1);

namespace App\Pinakes;

use Doctrine\Common\Collections\Collection;

class Helper {

    public static function isEmpty(mixed $value): bool {
        if (null === $value) return true;
        if ($value instanceof Collection) return $value->isEmpty();
        if (is_string($value)) return (0 === strlen($value));
        return !is_scalar($value) && empty($value);
    }

    public static function filterEmpty(array $values): array {
        return array_filter($values, fn($v) => !Helper::isEmpty($v));
    }
}
