<?php declare(strict_types=1);

namespace App\Database\SQL;

use App\Pinakes\Helper;

/*
SELECT
    book.id,
    book.title,
    authors.authors AS authors,
    json_object(
        'id', publisher.id,
        'name', publisher.name
    ) AS publisher,
    tags.tags AS tags,
    book.published,
    book.first_published,
    book.isbn,
    json_object(
        'id', series.id,
        'name', series.name
    ) AS series,
    book.series_volume
FROM book
LEFT JOIN (
    SELECT
        book_author.book_id,
        json_group_array(
            json_object('id', author.id, 'name', author.name)
        ) AS authors
    FROM book_author
    JOIN author ON author.id = book_author.author_id
    GROUP BY book_author.book_id
) AS authors ON authors.book_id = book.id
LEFT JOIN publisher
    ON publisher.id = book.publisher_id
LEFT JOIN (
    SELECT
        book_tag.book_id,
        json_group_array(
            json_object(
                'id', tag.id,
                'name', tag.name,
                'color', tag.color
            )
        ) AS tags
    FROM book_tag
    JOIN tag ON tag.id = book_tag.tag_id
    GROUP BY book_tag.book_id
) AS tags ON tags.book_id = book.id
LEFT JOIN series
    ON series.id = book.series_id
ORDER BY publisher.name DESC
LIMIT 30 OFFSET 0;
 */
class Query implements Statement {
    public readonly string $table_name;
    public array $selects = [];
    public array $joins = [];
    public array $wheres = [];
    public array $group_by = [];
    public array $order_by = [];

    public array $parameters = [];

    private ?int $limit = null; 
    private ?int $offset = null;

    public function __construct(string $table_name) {
        $this->table_name = $table_name;
    }

    public function __toString() {
        return $this->build();
    }

    private function parse(string $expr): string {
        return str_replace('self.', $this->table_name . '.', $expr);
    }

    public function select(string $select, ?string $alias = null): self {
        if (null !== $alias) $select = sprintf('%s AS %s', $select, $alias);
        $this->selects[] = $this->parse($select);
        return $this;
    }

    public function selectObject(string $name, array $fields, ?string $alias = null): self {
        $content = implode(', ', array_map(fn($v) => "'{$v}', {$name}.{$v}", $fields));
        return $this->select("json_object({$content})", $alias);
    }

    public function selectGroup(string $name, array $fields, ?string $alias = null): self {
        $content = implode(', ', array_map(fn($v) => "'{$v}', {$name}.{$v}", $fields));
        return $this->select("json_group_array(json_object({$content}))", $alias);
    }

    public function join(string $join): self {
        $this->joins[] = $this->parse($join);
        return $this;
    }

    public function where(string $where): self {
        if ('.' === $where[0]) $where = 'self' . $where;
        $this->wheres[] = $this->parse($where);
        return $this;
    }

    public function orderBy(string $by, string $dir = 'ASC'): self {
        $by = $this->parse($by);
        $this->order_by[$by] = $dir;
        return $this;
    }

    public function groupBy(string $by): self {
        $this->group_by[] = $this->parse($by);
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self {
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

        $limit = '';
        if (null !== $this->limit) {
            $limit = "LIMIT {$this->limit} OFFSET {$this->offset}";
        }

        return "SELECT {$select} FROM {$this->table_name} {$join} {$where} {$group_by} {$order_by} {$limit}";
    }

    public function getParameters(): array {
        return $this->parameters;
    }
}