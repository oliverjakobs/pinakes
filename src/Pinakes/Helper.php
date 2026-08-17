<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use Doctrine\Common\Collections\Collection;

class Helper {

    public static function isEmpty(mixed $value): bool {
        if (null === $value) return true;
        if ($value instanceof Collection) return $value->isEmpty();
        if ($value instanceof PinakesEntity) return null === $value->getId();
        if (is_string($value)) return self::strEmpty($value);
        return !is_scalar($value) && empty($value);
    }

    public static function strEmpty(string $value): bool {
        return 0 === strlen($value);
    } 

    public static function filterEmpty(array $values): array {
        return array_filter($values, fn($v) => !Helper::isEmpty($v));
    }

    public static function bp(mixed $value, mixed ...$values) {
        var_dump($value, ...$values); die();
    }
}
