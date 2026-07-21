<?php declare(strict_types=1);

namespace App\Pinakes;

class Assert {

    public static function instanceOf(mixed $data, string $expected) {
        assert($data instanceof $expected, 'Expected ' . $expected . ', got ' . get_debug_type($data) . ' instead');
    }

    public static function inArray(mixed $needle, array $haystack, string $msg) {
        assert(in_array($needle, $haystack), $msg);
    }

    public static function isTrue(bool $value, string $msg) {
        assert($value, $msg);
    }

    public static function notNull(mixed $value, string $msg) {
        assert(null !== $value, $msg);
    }

    public static function notEmpty(mixed $value, string $msg) {
        assert(!Helper::isEmpty($value), $msg);
    }

    public static function error(string $msg) {
        assert(false, $msg);
    }
}
