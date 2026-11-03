<?php declare(strict_types=1);

namespace App\Pinakes;

trait Singleton {
    private static ?self $_instance = null;

    public static function getInstance(): static {
        if (null === self::$_instance) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }
}