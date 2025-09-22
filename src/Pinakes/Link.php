<?php declare(strict_types=1);

namespace App\Pinakes;

class Link {
    public string $caption;
    public string $url;
    public bool $extern;

    private array $style_classes = [];

    private ?string $hx_method = null;
    private ?string $hx_target = null;

    public function __construct(string $caption, string $url, bool $extern = false) {
        $this->caption = $caption;
        $this->url = $url;
        $this->extern = $extern;
    }

    public static function createHx(string $caption, string $url, string $method, ?string $target = null): self {
        $result = new self($caption, $url);
        return $result->setHx($method, $target);
    }

    public function setHx(string $method, ?string $target = null): self {
        $this->hx_method = $method;
        $this->hx_target = $target;
        return $this;
    }

    public function addStyleClasses(...$classes): self {
        $this->style_classes = array_merge($this->style_classes, $classes);
        return $this;
    }

    public function __toString(): string {
        return $this->getHtml();
    }

    public function getHtml(): string {
        $styles = $this->style_classes;

        if ($this->extern) {
            $styles[] = 'link-extern';
        }

        $class = '';
        if (!empty($styles)) {
            $class = 'class="'. implode(' ', $styles) . '"';
        }

        if (null !== $this->hx_method) {
            $method = strtolower($this->hx_method);
            $target = (null !== $this->hx_target) ? 'hx-target="' . $this->hx_target . '"' : '';
            return <<<HTML
                <button hx-$method="$this->url" $class $target>$this->caption</button>
            HTML;
        }

        $attr = $this->extern ? 'target="_blank" rel="noopener noreferrer"' : '';
        return <<<HTML
            <a $attr $class href="$this->url">$this->caption</a>
        HTML;
    }
}
