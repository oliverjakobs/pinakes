<?php declare(strict_types=1);

namespace App\Renderable;

use App\Pinakes\Pinakes;

class Link implements Renderable {
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    private string $url;
    private Renderable|string $caption;

    private bool $is_extern = false;
    private bool $is_button = false;

    private string $method = 'GET';
    private ?string $target = null;
    private ?string $swap = null;

    private function __construct(self|string $caption, string $url) {
        $this->url = $url;
        $this->caption = $caption;
    }

    public static function create(self|string $caption, string $route, array $parameters = []): self {
        $url = Pinakes::getUrl($route, $parameters);
        return new self($caption, $url);
    }

    public static function post(self|string $caption, string $route, array $parameters = []): self {
        return self::create($caption, $route, $parameters)->setHx(self::METHOD_POST);
    }

    public static function delete(self|string $caption, string $route, array $parameters = []): self {
        return self::create($caption, $route, $parameters)->setHx(self::METHOD_DELETE);
    }

    public static function modal(self|string $caption, string $route, array $parameters = []): self {
        return self::create($caption, $route, $parameters)->setHx(self::METHOD_GET, 'body', 'beforeend');
    }

    public static function extern(self|string $caption, string $url): self {
        $result = new self($caption, $url);
        $result->is_extern = true;

        return $result;
    }

    public function setHx(string $method, ?string $target = null, ?string $swap = null): self {
        $this->method = $method;
        $this->target = $target;
        $this->swap = $swap;

        return $this;
    }

    public function setButton(bool $b = true): self {
        $this->is_button = $b;
        return $this;
    }

    public function __toString(): string {
        return $this->render();
    }
    
    public function render(): string {
        $attributes = [];
        $style_classes = [];
        $element = 'a';

        if ($this->is_extern) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
            
            $style_classes[] = 'link-extern';
        }

        if ($this->is_button) {
            $style_classes[] = 'button';
        }

        if (self::METHOD_GET !== $this->method || null !== $this->target) {
            $method = 'hx-' . strtolower($this->method);
            $attributes[$method] = $this->url;

            if (null !== $this->target) $attributes['hx-target'] = $this->target;
            if (null !== $this->swap) $attributes['hx-swap'] = $this->swap;
            $element = 'button';
        } else {
            $attributes['href'] = $this->url;
        }

        $attr = implode(' ', array_map(fn ($k, $v) => sprintf('%s="%s"', $k, $v), array_keys($attributes), $attributes));

        $class = '';
        if (!empty($style_classes)) {
            $class = sprintf('class="%s"', implode(' ', $style_classes));
        }

        $caption = $this->caption;
        if ($caption instanceof Renderable) $caption = $caption->render();

        return <<<HTML
            <$element $attr $class>$caption</$element>
        HTML;
    }
}
