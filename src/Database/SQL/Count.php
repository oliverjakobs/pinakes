<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

/*
SELECT COUNT(DISTINCT table.id) FROM table
WHERE condition;
*/
class Count implements Statement {
    public readonly string $table_name;

    public array $wheres = [];
    public array $parameters = [];

    public function __construct(string $table_name) {
        $this->table_name = $table_name;
    }

    public static function fromQuery(Query $query): self {
        $result = new Count($query->table_name);
        $result->wheres = $query->wheres;
        $result->parameters = $query->parameters;
        return $result;
    }

    public function __toString() {
        return $this->build();
    }

    public function build(): string {
        $where = '';
        if (!Helper::isEmpty($this->wheres)) {
            $where = 'WHERE ' . implode(' AND WHERE ', $this->wheres);
        }
        
        return "SELECT COUNT(DISTINCT {$this->table_name}.id) FROM {$this->table_name} {$where}";
    }

    public function getParameters(): array {
        return $this->parameters;
    }
}