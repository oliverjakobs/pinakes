<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

class Query {
    public readonly string $table_name;
    public array $selects = [];
    public array $joins = [];
    public array $wheres = [];
    public array $order_by = [];

    public array $parameters = [];

    private ?int $limit = null; 
    private ?int $offset = null;

    public function __construct(string $table_name) {
        $this->table_name = $table_name;
        $this->selects[] = $table_name . '.id';
    }

    public function __toString() {
        return $this->build();
    }

    public function addSelect(string $select, ?string $alias = null): self {
        if ('.' === $select[0]) $select = $this->table_name . $select;
        if (null !== $alias) $select = sprintf('%s AS "%s"', $select, $alias);
        $this->selects[] = $select;
        return $this;
    }

    public function addJoin(string $join): self {
        $this->joins[] = $join;
        return $this;
    }

    public function addWhere(string $where): self {
        if ('.' === $where[0]) $where = $this->table_name . $where;
        $this->wheres[] = $where;
        return $this;
    }

    public function addOrderBy(string $by, string $dir = 'ASC'): self {
        $this->order_by[$by] = $dir;
        return $this;
    }

    public function setLimit(int $limit, ?int $offset = null): self {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function setParameter(string $key, mixed $value): self {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function build(): string {
        $select = implode(', ', $this->selects);

        $sql = "SELECT {$select} FROM {$this->table_name}";
        $sql .= ' ' . implode(' ', $this->joins);

        if (!Helper::isEmpty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND WHERE ', $this->wheres);
        }

        if (!Helper::isEmpty($this->order_by)) {
            $order_by = array_map(fn($v, $k) => $k . ' ' . $v, $this->order_by, array_keys($this->order_by));
            $sql .= ' ORDER BY ' . implode(', ', $order_by);
        }

        if (null !== $this->limit) {
            $sql .= " LIMIT {$this->limit}";
            if (null !== $this->offset) $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}