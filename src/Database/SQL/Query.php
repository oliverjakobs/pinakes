<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

class Query {
    public readonly string $table_name;
    public array $selects = [];
    public array $joins = [];
    public array $wheres = [];
    public array $group_by = [];
    public array $order_by = [];
    public array $virtual_selects = [];

    public array $parameters = [];

    private ?int $limit = null; 
    private ?int $offset = null;

    public function __construct(string $table_name) {
        $this->table_name = $table_name;
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

    public function addVirtualSelect(string $select, string $name): self {
        $this->virtual_selects[$name] = $select;
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

    public function addGroupBy(string $by): self {
        if ('.' === $by[0]) $by = $this->table_name . $by;
        $this->group_by[] = $by;
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
        $join = implode(' ', $this->joins);

        $where = '';
        if (!Helper::isEmpty($this->wheres)) {
            $where = 'WHERE ' . implode(' AND WHERE ', $this->wheres);
        }

        $group_by = '';
        if (!Helper::isEmpty($this->group_by)) {
            $group_by = 'GROUP BY ' . implode(', ', $this->group_by);
        }

        $order_by = '';
        if (!Helper::isEmpty($this->order_by)) {
            $order_by = array_map(fn($v, $k) => $k . ' ' . $v, $this->order_by, array_keys($this->order_by));
            $order_by = 'ORDER BY ' . implode(', ', $order_by);
        }

        $virtual_select = '';
        if (!Helper::isEmpty($this->virtual_selects)) {
            $virtual_select = array_map(fn($v, $k) => $v . ' AS ' . $k, $this->virtual_selects, array_keys($this->virtual_selects));
            $virtual_select = ', ' . implode(', ', $virtual_select);
        }

        $from = $this->table_name;
        if (null !== $this->limit) {
            $subquery = "SELECT * {$virtual_select} FROM {$this->table_name} {$where} {$order_by} LIMIT {$this->limit} OFFSET {$this->offset}";
            $from = "({$subquery}) AS {$this->table_name}";
            $where = '';
            if (!Helper::isEmpty($this->virtual_selects)) {
                $virtual_select = array_map(fn($k) => "{$this->table_name}.{$k}", array_keys($this->virtual_selects));
                $virtual_select = ', ' . implode(', ', $virtual_select);
            }
        }

        return "SELECT {$select} {$virtual_select} FROM {$from} {$join} {$where} {$group_by} {$order_by}";
    }
}