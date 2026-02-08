<?php declare(strict_types=1);

namespace App\Renderable;

use App\Pinakes\Pinakes;

class TwigElement implements Renderable {
    public function __construct(
        private string $twig_path,
        private array $params) {
    }

    public static function collection(iterable $data, ?string $separator = null): self {
        return new self('/elements/collection.html.twig', [
            'data' => $data,
            'separator' => $separator,
        ]);
    } 

    public function render(): string {
        return Pinakes::renderTemplate($this->twig_path, $this->params);
    }
}