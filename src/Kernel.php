<?php

namespace App;

use App\Pinakes\Context;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void {
        parent::boot();

        if (!$this->booted) return;

        Context::init(
            $this->container->get('doctrine.orm.entity_manager'),
            $this->container->get('App\Pinakes\PinakesRouter'),
            $this->getProjectDir()
        );
    }
}
