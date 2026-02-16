<?php declare(strict_types=1);

namespace App\Renderable;

use App\Pinakes\Pinakes;

class Icon implements Renderable {
    private string $data = ''; 

    private function __construct(string $data) {
        $this->data = $data;
    }
 
    public static function create(string $icon): self {
        $filename = Pinakes::getAbsolutePath('/public/icons/bootstrap/' . $icon . '.svg');
        $content = file_exists($filename) ? file_get_contents($filename) : '';

        return new self($content);
    }

    public function __toString(): string {
        return $this->render();
    }
    
    public function render(): string {
        return $this->data;
    }
}
