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
class Update implements Statement {
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
        $set = implode(
            ', ', 
            array_map(fn($k) => "{$k} = :{$k}", array_keys($this->values))
        );
        return <<<SQL
            UPDATE {$this->table_name}
            SET
                {$set}
            WHERE
                id = :id
        SQL;
    }

    public function getParameters(): array {
        $params = array_combine(
            array_map(fn($k) => ':' . $k, array_keys($this->values)),
            $this->values
        );
        return [ ':id' => $this->id, ...$params ];
    }
}