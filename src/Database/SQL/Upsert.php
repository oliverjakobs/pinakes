<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
INSERT INTO
  author (name)
VALUES
  (
    'Jane Smith'
  )
ON CONFLICT (name) DO
UPDATE
SET
  name = excluded.name
RETURNING id;
*/
class Upsert implements Statement {
    public readonly string $table_name;
    public readonly string $property;
    public readonly string $value;

    public function __construct(string $table_name, string $property, string $value) {
        $this->table_name = $table_name;
        $this->property = $property;
        $this->value = $value;
    }

    public function __toString() {
        return $this->build();
    }

    public function build(): string {
        return <<<SQL
            INSERT INTO {$this->table_name}
                ({$this->property})
            VALUES
                (:value)
            ON CONFLICT ({$this->property}) DO UPDATE
            SET
                {$this->property} = excluded.{$this->property}
            RETURNING id
        SQL;
    }

    public function getParameters(): array {
        return [ ':value' => $this->value ];
    }
}