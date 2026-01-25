<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use Doctrine\Common\Collections\Collection;
use Twig\Environment;

class FormElement {

    public function __construct(
        private string $twig_path,
        private array $params) {
    }

    public static function number(int|float|null $value, int|float|null $min = null, int|float|null $max = null): self {
        return new self('/elements/form/number.html.twig', [
            'value' => $value,
            'min' => $min,
            'max' => $max,
        ]);    
    }

    public static function autocomplete(array $options, Collection|PinakesEntity|null $values): self {
        return new self('/elements/form/autocomplete.html.twig', [
            'options' => $options,
            'values' => $values,
        ]);
    }

    public function render(Environment $twig, string $name): string {
        return $twig->render($this->twig_path, [
            'name' => $name,
            ...$this->params
        ]);
    }
}