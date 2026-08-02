<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

/*
DELETE FROM table
WHERE search_condition;
*/
class Delete {
    public readonly string $table_name;

    public array $wheres = [];
    public array $parameters = [];

    public function __construct(string $table_name, string $where) {
        $this->table_name = $table_name;
        $this->addWhere($where);
    }

    public function __toString() {
        return $this->build();
    }

    public function addWhere(string $where): self {
        $this->wheres[] = $where;
        return $this;
    }

    public function setParameter(string $key, mixed $value): self {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function build(): string {
        $sql = "DELETE FROM {$this->table_name}";

        if (!Helper::isEmpty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND WHERE ', $this->wheres);
        }

        return $sql;
    }
}