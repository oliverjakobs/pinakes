<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
INSERT INTO
  contacts (name, email, phone, effective_date)
VALUES
  (
    'Jane Smith',
    'jane@test.com',
    '(408)-111-3333',
    '2024-05-05'
  )
ON CONFLICT (email) DO
UPDATE
SET
  name = excluded.name,
  phone = excluded.phone,
  effective_date = excluded.effective_date
WHERE
  excluded.effective_date > contacts.effective_date;
*/
class Upsert {
    public readonly string $table_name;

    public array $values = [];

    public function __construct(string $table_name) {
        $this->table_name = $table_name;
    }

    public function __toString() {
        return $this->build();
    }

    public function add(string $name, mixed $value): self {
        $this->values[$name] = $value;
        return $this;
    }

    public function build(): string {
        $names = '(' . implode(', ', array_keys($this->values)) . ')';

        $sql = "INSERT INTO {$this->table_name} {$names}";

        $values = array_map(fn ($v) => "'{$v}'", $this->values);
        $sql .= ' VALUES (' . implode(', ', $values) . ')';

        $sql .= " ON CONFLICT {$names} DO UPDATE";

        $set = array_map(fn($k) => "{$k} = excluded.{$k}", array_keys($this->values));
        $sql .= ' SET ' . implode(', ', $set);
        $sql .= " RETURNING id";

        return $sql;
    }
}