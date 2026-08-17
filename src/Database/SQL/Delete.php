<?php declare(strict_types=1);

namespace App\Database\SQL;

/*
DELETE FROM table
WHERE condition;
*/
class Delete implements Statement {
    public readonly string $table_name;
    public readonly string $condition;

    public array $parameters = [];

    public function __construct(string $table_name, string $condition) {
        $this->table_name = $table_name;
        $this->condition = $condition;
    }

    public function __toString() {
        return $this->build();
    }

    public function setParameter(string $key, mixed $value): self {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function build(): string {
        return <<<SQL
            DELETE FROM {$this->table_name}
            WHERE {$this->condition}
        SQL;
    }

    public function getParameters(): array {
        return $this->parameters;
    }
}