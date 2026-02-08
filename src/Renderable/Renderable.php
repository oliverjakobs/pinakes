<?php declare(strict_types=1);

namespace App\Renderable;

interface Renderable {
    public function render(): string;
}
