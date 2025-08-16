<?php declare(strict_types=1);

namespace App\Pinakes;

class Link {

    public string $caption;
    public string $url;
    public bool $extern;

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
        if (null !== $this->hx_method) {
            $method = strtolower($this->hx_method);
            $target = (null !== $this->hx_target) ? 'hx-target="' . $this->hx_target . '"' : '';
            return <<<HTML
                <button hx-$method="$this->url" $target>$this->caption</button>
            HTML;
        }

        $attr = $this->extern ? 'class="link-extern" target="_blank" rel="noopener noreferrer"' : '';
        return <<<HTML
            <a $attr href="$this->url">$this->caption</a>
        HTML;
    }
}
