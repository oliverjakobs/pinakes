<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Repository\PinakesRepository;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class Pinakes {
    private static ?self $_instance = null;

    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private AuthorizationCheckerInterface $auth,
        private Environment $twig,
        private string $app_dir
    ) {
    }

    public static function init(EntityManagerInterface $em, RouterInterface $router, AuthorizationCheckerInterface $auth, Environment $twig, string $app_dir): void {
        if (self::isInitialized()) return;

        self::$_instance = new self($em, $router, $auth, $twig, $app_dir);
    }

    public static function getInstance(): self {
        if (!self::isInitialized()) {
            throw new \RuntimeException('Pinakes has not been initialized.');
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

    public static function getUrl(string $route, array $params = []): string {
        return self::getInstance()->router->generate($route, $params);
    }

    public static function renderTemplate(string $path, array $params): string {
        return self::getInstance()->twig->render($path, $params);
    }

    public static function isGranted(?string $role): bool {
        if (null === $role) return true;
        return self::getInstance()->auth->isGranted($role);
    }
}
