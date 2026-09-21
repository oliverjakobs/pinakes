<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;

class Helper {

    public static function isEmpty(mixed $value): bool {
        if (null === $value) return true;
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

    public static function equalsFloat(float $x, float $y, float $epsilon = 10E-6) {
        return abs($x - $y) < $epsilon;
    }

    public static function equals(mixed $a, mixed $b): bool {
        if ($a === $b) return true;
        if (is_float($a) && is_float($b)) return self::equalsFloat($a, $b);
        
        if ($a instanceof \DateTime && $b instanceof \DateTime) {
            return $a->getTimestamp() === $b->getTimestamp();
        }

        if (is_object($a) && is_object($b)) {
            // comparing objects => different type => not equal
            if (get_class($a) !== get_class($b)) return false;
            // comparing objects => they have to implement 'equals'
            return $a->equals($b);
        }

        return false;
    }

    public static function arrayContains(array $array, mixed $value): bool {
        foreach ($array as $item) {
            if (self::equals($item, $value)) {
                return true;
            }
        }
        return false;
    }

    public static function bp(mixed $value, mixed ...$values) {
        var_dump($value, ...$values); die();
    }
}
