<?php declare(strict_types=1);

namespace App\Pinakes;

class ViewElement {

    public string $element;
    public string $content;
    public array $classes = [];
    public array $attributes = [];

    public function __construct(string $element, string $content = '') {
        $this->element = $element;
        $this->content = $content;
    }

    public static function icon(string $name): self {
        // TODO
    }

    public static function tag(string $caption, string $color, string $url = ''): self {
        if (!empty($url)) {
            $result = self::anchor($caption, $url);;
        } else {
            $result = new self('div', $caption);
        }

        $fg = (hexdec(ltrim($color, '#')) > 0xffffff/2) ? 'black':'white';
        $result->attributes['style'] = sprintf('background-color:%s;color:%s;', $color, $fg);
        $result->classes[] = 'tag';

        return $result;
    }

    public static function anchor(string $caption, string $url, bool $extern = false): self {
        $result = new self('a', $caption);
        $result->attributes['href'] = $url;

        if ($extern) {
            $result->classes[] = 'link-extern';
            $result->attributes['target'] = '_blank';
            $result->attributes['rel'] = 'noopener noreferrer';
        }

        return $result;
    }

    public static function hxButton(string $caption, string $url, string $method, string $target = ''): self {
        $result = new self('button', $caption);

        $method = 'hx-' . strtolower($method);
        $result->attributes[$method] = $url;

        if (!empty($target)) {
            $result->attributes['hx-target'] = $target;
        }

        return $result;
    }

    public function __toString(): string {
        return $this->getHtml();
    }

    public function addClasses(array $classes): self {
        $this->classes = array_merge($this->classes, $classes);
        return $this;
    }

    public function getHtml(): string {
        $attr = array_map(fn ($k, $v) => sprintf('%s="%s"', $k, $v), array_keys($this->attributes), $this->attributes);
        $attr = implode(' ', $attr);

        $class = '';
        if (!empty($this->classes)) {
            $class = implode(' ', $this->classes);
            $class = sprintf('class="%s"', $class);
        }

        return <<<HTML
            <$this->element $attr $class >$this->content</$this->element>
        HTML;
    }
}
