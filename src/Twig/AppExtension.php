<?php

namespace App\Twig;

use App\Entity\PinakesEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('entry', [$this, 'renderEntity']),
        ];
    }

    public function renderEntity(PinakesEntity $entity, array $field): string
    {
        return $entity->renderField($field);
    }
}