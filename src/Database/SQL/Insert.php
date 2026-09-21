<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
INSERT INTO contact
    (name, email, phone, effective_date)
VALUES
    (
        'Jane Smith',
        'jane@test.com',
        '(408)-111-3333',
        '2024-05-05'
    )
RETURNING id;
*/
class Insert implements Statement {
    public readonly string $table_name;
    public readonly bool $returning;

    public array $values = [];

    public function __construct(string $table_name, bool $returning = true) {
        $this->table_name = $table_name;
        $this->returning = $returning;
    }

    public function __toString() {
        return $this->build();
    }

    public function add(string $name, mixed $value): self {
        $this->values[$name] = $value;
        return $this;
    }

    public function build(): string {
        $names = implode(', ', array_keys($this->values));
        $values = implode(', ', array_map(fn($k) => ':' . $k, array_keys($this->values)));
        $returning = $this->returning ? 'RETURNING id' : '';
        return <<<SQL
            INSERT INTO "{$this->table_name}"
                ({$names})
            VALUES
                ({$values})
            {$returning}
        SQL;
    }

    public function getParameters(): array {
        return array_combine(
            array_map(fn($k) => ':' . $k, array_keys($this->values)),
            $this->values
        );
    }
}