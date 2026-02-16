<?php declare(strict_types=1);

namespace App\Renderable;

class ViewElement implements Renderable {
    public string $element;
    public self|string $content;
    public array $classes = [];
    public array $attributes = [];

    private function __construct(string $element, self|string $content = '') {
        $this->element = $element;
        $this->content = $content;
    }

    public static function create(string $element, self|string $content = ''): self {
        return new self($element, $content);
    }

    public static function separator(): self {
        $result = new self('div', '');
        $result->classes[] = 'separator';
        return $result;
    }

    public static function tag(self|string $caption, string $color): self {
        $result = new self('div', $caption);

        $fg = (hexdec(ltrim($color, '#')) > 0xffffff/2) ? 'black':'white';
        $result->attributes['style'] = sprintf('background-color:%s;color:%s;', $color, $fg);
        $result->classes[] = 'tag';

        return $result;
    }

    public static function ul(array $items): self {
        $content = implode(PHP_EOL, array_map(fn ($i) => '<li>' . $i . '</li>', $items));
        return new self('ul', $content);
    }

    public function __toString(): string {
        return $this->render();
    }

    public function addClasses(array $classes): self {
        $this->classes = array_merge($this->classes, $classes);
        return $this;
    }

    public function setAttribute(string $key, string $value): self {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    public function render(): string {       
        $attr = array_map(fn ($k, $v) => sprintf('%s="%s"', $k, $v), array_keys($this->attributes), $this->attributes);
        $attr = implode(' ', $attr);

        $class = '';
        if (!empty($this->classes)) {
            $class = implode(' ', $this->classes);
            $class = sprintf('class="%s"', $class);
        }

        return <<<HTML
            <$this->element $attr $class>$this->content</$this->element>
        HTML;
    }
}
