<?php declare(strict_types=1);

namespace App\Pinakes;

class Link {

    private string $caption;
    private string $url;
    private bool $extern;

    public function __construct(string $caption, string $url, bool $extern = false) {
        $this->caption = $caption;
        $this->url = $url;
        $this->extern = $extern;
    }

    public function __toString(): string {
        return $this->getHtml();
    }

    public function getHtml(): string {
        $attr =  ($this->extern ? 'class="link-extern" target="_blank" rel="noopener noreferrer"' : '');
        return sprintf('<a %s href="%s">%s</a>', $attr, $this->url, $this->caption);
    }
}
