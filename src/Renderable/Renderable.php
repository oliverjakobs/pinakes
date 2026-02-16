<?php declare(strict_types=1);

namespace App\Renderable;

interface Renderable {
    
    public function __toString(): string;
    public function render(): string;
}
