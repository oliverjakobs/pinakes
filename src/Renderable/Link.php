<?php declare(strict_types=1);

namespace App\Renderable;

use App\Pinakes\Helper;
use App\Pinakes\Pinakes;

class Link implements Renderable {
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    private string $url;
    private Renderable|string $caption;
    private array $style_classes = [];

    private bool $is_extern = false;

    private ?string $disabled_message = null;

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
        $result = self::create($caption, $route, $parameters);
        $result->method = self::METHOD_POST;
        return $result;
    }

    public static function delete(self|string $caption, string $route, array $parameters = []): self {
        $result = self::create($caption, $route, $parameters);
        $result->method = self::METHOD_DELETE;
        return $result;
    }

    public static function modal(self|string $caption, string $route, array $parameters = []): self {
        $result = self::create($caption, $route, $parameters);
        $result->method = self::METHOD_GET;
        $result->target = 'body';
        $result->swap = 'beforeend';
        return $result;
    }

    public static function extern(self|string $caption, string $url): self {
        $result = new self($caption, $url);
        $result->is_extern = true;
        return $result;
    }

    public function setDisabledMessage(string $msg): self {
        $this->disabled_message = $msg;
        return $this;
    }

    public function addStyleClasses(string ...$classes): self {
        $this->style_classes = array_merge($this->style_classes, $classes);
        return $this;
    }

    public function __toString(): string {
        return $this->render();
    }
    
    public function render(): string {
        $attributes = [];
        $style_classes = $this->style_classes;
        $element = 'span';

        if (null !== $this->disabled_message && !Helper::strEmpty($this->disabled_message)) {
            $attributes['disabled'] = '';
            $attributes['title'] = $this->disabled_message;
            $style_classes[] = 'disabled';
        } else if (self::METHOD_GET !== $this->method || null !== $this->target) {
            $method = 'hx-' . strtolower($this->method);
            $attributes[$method] = $this->url;

            if (null !== $this->target) $attributes['hx-target'] = $this->target;
            if (null !== $this->swap) $attributes['hx-swap'] = $this->swap;
        } else {
            $element = 'a';
            $attributes['href'] = $this->url;
        }

        if ($this->is_extern) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
            
            $style_classes[] = 'link-extern';
        }
        
        return Pinakes::renderTemplate('/elements/element.html.twig', [
            'element' => $element,
            'content' => $this->caption,
            'attributes' => $attributes,
            'style_classes' => $style_classes
        ]);
    }
}
