<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

/*
SELECT COUNT(DISTINCT table.id) FROM table
WHERE condition;
*/
class Sum implements Statement {
    public readonly string $table_name;
    public readonly string $field;

    public function __construct(string $table_name, string $field) {
        $this->table_name = $table_name;
        $this->field = $field;
    }

    public function __toString() {
        return $this->build();
    }

    public function build(): string {
        
        return "SELECT SUM({$this->table_name}.{$this->field}) FROM {$this->table_name}";
    }

    public function getParameters(): array {
        return [];
    }
}