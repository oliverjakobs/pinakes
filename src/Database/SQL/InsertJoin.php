<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
INSERT INTO join_table
    (a_id, b_id)
VALUES
    (23, 69)
RETURNING a_id;
*/
class InsertJoin implements Statement {
    public readonly string $table_name;
    public readonly array $keys;

    public function __construct(string $table_name, array $keys) {
        $this->table_name = $table_name;
        $this->keys = $keys;
    }

    public function __toString() {
        return $this->build();
    }

    public function build(): string {
        $names = implode(', ', array_keys($this->keys));
        $values = implode(', ', array_map(fn($k) => ':' . $k, array_keys($this->keys)));
        return <<<SQL
            INSERT INTO {$this->table_name}
                ({$names})
            VALUES
                ({$values})
            ON CONFLICT($names) DO NOTHING
        SQL;
    }

    public function getParameters(): array {
        return array_combine(
            array_map(fn($k) => ':' . $k, array_keys($this->keys)),
            $this->keys
        );
    }
}