<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
UPDATE employees
SET city = 'Toronto',
    state = 'ON',
    postalcode = 'M5P 2N7'
WHERE
    employeeid = 4;
*/
class Update {
    public readonly string $table_name;
    public readonly int $id;

    public array $values = [];

    public function __construct(string $table_name, int $id) {
        $this->table_name = $table_name;
        $this->id = $id;
    }

    public function __toString() {
        return $this->build();
    }

    public function add(string $name, mixed $value): self {
        $this->values[$name] = $value;
        return $this;
    }

    public function build(): string {
        $sql = "UPDATE {$this->table_name}";

        $set = array_map(fn($v, $k) => "{$k} = '{$v}'", $this->values, array_keys($this->values));
        $sql .= ' SET ' . implode(', ', $set);

        $sql .= " WHERE id = :id";
        return $sql;
    }
}