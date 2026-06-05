<?php declare(strict_types=1);

namespace App\Renderable;

use App\Pinakes\Pinakes;

class ViewElement implements Renderable {
    public string $element;
    public Renderable|string $content;
    public array $style_classes = [];
    public array $attributes = [];

    private function __construct(string $element, Renderable|string $content = '') {
        $this->element = $element;
        $this->content = $content;
    }

    public static function create(string $element, Renderable|string $content = ''): self {
        return new self($element, $content);
    }

    public static function separator(): self {
        $result = new self('div', '');
        $result->style_classes[] = 'separator';
        return $result;
    }

    public static function tag(Renderable|string $caption, string $color): self {
        $result = new self('div', $caption);

        $fg = (hexdec(ltrim($color, '#')) > 0xffffff/2) ? 'black':'white';
        $result->attributes['style'] = sprintf('background-color:%s;color:%s;', $color, $fg);
        $result->style_classes[] = 'tag';

        return $result;
    }

    public static function ul(array $items): self {
        $content = implode(PHP_EOL, array_map(fn ($i) => '<li>' . $i . '</li>', $items));
        return new self('ul', $content);
    }

    public function __toString(): string {
        return $this->render();
    }

    public function addStyleClasses(string ...$classes): self {
        $this->style_classes = array_merge($this->style_classes, $classes);
        return $this;
    }

    public function setAttribute(string $key, string $value): self {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function isSeparator(): bool {
        return in_array('separator', $this->style_classes);
    }
    
    public function render(): string {
        return Pinakes::renderTemplate('/elements/element.html.twig', [
            'element' => $this->element,
            'content' => $this->content,
            'attributes' => $this->attributes,
            'style_classes' => $this->style_classes
        ]);
    }
}
