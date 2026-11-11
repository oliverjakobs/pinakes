<?php declare(strict_types=1);

namespace App\Database\SQL;

interface Statement {
    public function build(): string;
    public function getParameters(): array;
}