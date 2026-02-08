<?php

namespace App;

use App\Pinakes\Pinakes;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void {
        parent::boot();

        if (!$this->booted) return;

        Pinakes::init(
            $this->container->get('doctrine.orm.entity_manager'),
            $this->container->get('app.router'),
            $this->container->get('app.twig'),
            $this->getProjectDir()
        );
    }
}
