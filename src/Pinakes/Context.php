<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Repository\PinakesRepository;
use Doctrine\ORM\EntityManagerInterface;

class Context {
    private static ?EntityManagerInterface $em = null;

    public static function setEntityManager(EntityManagerInterface $em): void
    {
        self::$em = $em;
    }

    public static function getEntityManager(): EntityManagerInterface
    {
        if (!self::$em) {
            throw new \RuntimeException('EntityManager has not been initialized.');
        }

        return self::$em;
    }

    public static function getRepository(string $entity): PinakesRepository {
        return self::getEntityManager()->getRepository($entity);
    }
}
