<?php declare(strict_types=1);

namespace App\Pinakes;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class Pinakes {
    private static ?self $_instance = null;
    private static ?EntityManagerInterface $em = null;
    private static ?RouterInterface $router = null;
    private static ?AuthorizationCheckerInterface $auth = null;
    private static ?Environment $twig = null;

    public function __construct(
        private string $app_dir,
        private ContainerInterface $container,
    ) {
    }

    public static function init(string $app_dir, ContainerInterface $container): void {
        if (self::isInitialized()) return;

        self::$_instance = new self($app_dir, $container);
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

    public static function getParameter(string $name): mixed {
        return self::getInstance()->container->getParameter($name);
    }

    public static function getEntityManager(): EntityManagerInterface {
        if (null === self::$em) self::$em = self::getInstance()->container->get('doctrine.orm.entity_manager');
        return self::$em;
    }

    public static function getRepository(string $entity): EntityRepository {
        return self::getEntityManager()->getRepository($entity);
    }

    public static function getAbsolutePath(string $path): string {
        return self::getInstance()->app_dir . $path;
    }

    public static function getUrl(string $route, array $params = []): string {
        if (null === self::$router) self::$router = self::getInstance()->container->get('app.router');
        return self::$router->generate($route, $params);
    }

    public static function renderTemplate(string $path, array $params): string {
        if (null === self::$twig) self::$twig = self::getInstance()->container->get('app.twig');
        return self::$twig->render($path, $params);
    }

    public static function isGranted(string $role): bool {
        if (null === self::$auth) self::$auth = self::getInstance()->container->get('app.auth_checker');
        return self::$auth->isGranted($role);
    }
}
