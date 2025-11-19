<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;

class Context {
    private static ?self $_instance = null;

    public function __construct(
        private EntityManagerInterface $em,
        private string $app_dir
    ) {
    }

    public static function init(EntityManagerInterface $em, string $app_dir): void {
        if (self::isInitialized()) return;

        self::$_instance = new self($em, $app_dir);
    }

    public static function getInstance(): self {
        if (!self::isInitialized()) {
            throw new \RuntimeException('Context has not been initialized.');
        }
        return self::$_instance;
    }

    public static function isInitialized(): bool {
        return null !== self::$_instance;
    }

    public static function getEntityManager(): EntityManagerInterface {
        return self::getInstance()->em;
    }

    public static function getRepository(string $entity): PinakesRepository {
        return self::getEntityManager()->getRepository($entity);
    }

    public static function getAbsolutePath(string $path): string {
        return self::getInstance()->app_dir . $path;
    }
}
