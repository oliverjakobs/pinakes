<?php declare(strict_types=1);

namespace App\Pinakes;

class Link {

    private string $caption;
    private string $url;
    private bool $extern;

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

    public function __toString(): string {
        return $this->getHtml();
    }

    public function getHtml(): string {
        if (null === $this->hx_method) {
            $attr = $this->extern ? 'class="link-extern" target="_blank" rel="noopener noreferrer"' : '';
            return sprintf('<a %s href="%s">%s</a>', $attr, $this->url, $this->caption);
        }

        $target = (null !== $this->hx_target) ? 'hx-target="' . $this->hx_target . '"' : '';
        return sprintf('<button hx-%s="%s" %s>%s</button>', strtolower($this->hx_method), $this->url, $target, $this->caption);
    }
}
