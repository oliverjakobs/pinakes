<?php declare(strict_types=1);

namespace App\Renderable;

use App\Entity\PinakesEntity;
use App\Pinakes\Pinakes;
use Doctrine\Common\Collections\Collection;

class FormElement implements Renderable {
    private function __construct(
        private string $name,
        private string $twig_path,
        private array $params) {
    }

    public static function input(string $name, string $type, mixed $value): self {
        return new self($name, '/elements/form/input.html.twig', [
            'type' => $type,
            'value' => $value,
        ]);    
    }

    public static function number(string $name, int|float|null $value, int|float|null $min = null, int|float|null $max = null): self {
        return new self($name, '/elements/form/number.html.twig', [
            'value' => $value,
            'min' => $min,
            'max' => $max,
        ]);    
    }

    public static function autocomplete(string $name, array $options, Collection|PinakesEntity|null $values): self {
        return new self($name, '/elements/form/autocomplete.html.twig', [
            'options' => $options,
            'values' => $values,
        ]);
    }

    public function __toString(): string {
        return $this->render();
    }

    public function render(): string {
        return Pinakes::renderTemplate($this->twig_path, [
            'name' => $this->name,
            ...$this->params
        ]);
    }
}