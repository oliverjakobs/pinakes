<?php declare(strict_types=1);

namespace App\Pinakes;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PinakesRouter {

    public function __construct(
        private UrlGeneratorInterface $router
    ) {
    }

    public function generate(string $name, array $parameters = []): string {
        return $this->router->generate($name, $parameters);
    }
}
