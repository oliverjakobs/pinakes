<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Database\Database;
use App\Database\SQL\Query;
use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use BackedEnum;

class TableCaption {
    public string $text;

    public string $order_by = '';
    public string $order_dir = '';

    public function __construct(string $text) {
        $this->text = $text;
    }
}

class DataTable {
    const DEFAULT_FILTER = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
        'filter_only' => false
    ];

    public array $filter = self::DEFAULT_FILTER;

    public string $component_path = 'components/table.html.twig'; 
    public bool $allow_pagination = true;
    public bool $allow_ordering = true;

    public readonly PinakesMapper $mapper;
    /** @var array<string, DataField> $columns */
    public readonly array $columns;

    public function __construct(PinakesMapper $mapper, array $columns) {
        $this->mapper = $mapper;
        $this->columns = $columns;
    }

    public function getColumn(string $name): DataField {
        Assert::isTrue(array_key_exists($name, $this->columns), 'Unkown column ' . $name . ' in table ' . $this->mapper->getTableName());
        return $this->columns[$name];
    }

    public function addFilter(string $name, mixed $value): self {
        if (is_iterable($value)) {
            $value = array_map(fn ($v) => ($v instanceof PinakesEntity) ? $v->id : $v, $value);
        } else if ($value instanceof PinakesEntity) {
            $value = $value->id;
        } else if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $this->filter[$name] = $value;
        return $this;
    }
    
    public function setOrder(string $by, string $dir = 'ASC'): self {
        $this->filter['order_by'] = $by;
        $this->filter['order_dir'] = $dir;
        return $this;
    }

    public function setPP(int $pp): self {
        $this->filter['pp'] = $pp;
        return $this;
    }

    public function setQuery(array $query): self {
        $this->filter = array_merge($this->filter, array_filter($query));
        return $this;
    }

    private function getProperties(): array {
        $result = [];
        foreach ($this->columns as $col) {
            $result = array_merge($result, [ $col->mapping ], $col->requires);
        }
        return array_filter($result);
    }

    private ?Query $_select = null;
    public function getSelect(): Query {
        if (null !== $this->_select) return $this->_select;

        $query = $this->mapper->createQuery($this->getProperties());

        // add search
        $search = $this->filter['search'] ?? null;
        if (null !== $search) {
            $query->where("self.{$this->mapper->getSearchKey()} LIKE :search");
            $query->setParameter(':search', '%' . $search . '%');
        }

        // filter by data fields
        foreach ($this->filter as $name => $value) {
            $field = $this->mapper->getDataField($name);
            if (null === $field) continue;

            Assert::notNull($field->property, 'Cannot filter without property');

            $table_name = $this->mapper->getTableName();
            if ($field->data_type->isArrayType()) {

                $in_subquery = new Query($field->mapping->getJoinTable());
                $in_subquery->select("{$table_name}_id");

                $target_name = PinakesMapper::getMapper($field->mapping->target)->getTableName();
                if (is_iterable($value)) {
                    Assert::error('Not implemented');
                } else {
                    $in_subquery->where("{$target_name}_id = {$value}");
                }

                $query->where("self.id IN ({$in_subquery->build()})");
            } else if ($field->data_type->isTargetType()) {
                $param = ":{$name}";
                $query->where("{$param} = self.{$field->property}_id");
                $query->setParameter($param, $value);
            } else {
                $param = ":{$name}";
                $query->where("{$param} = self.{$field->property}");
                $query->setParameter($param, $value);
            }
        }

        // apply order
        $by = $this->filter['order_by'] ?? null;
        if (null !== $by) {
            $field = $this->mapper->getDataField($by);
            // if (null !== $field) 
            $query->orderBy($field->getOrderBy(), $this->filter['order_dir'] ?? 'asc');
        } else {
            foreach ($this->mapper->getDefaultOrder() as $by => $dir) {
                $query->orderBy($by, $dir);
            }
        }

        $pp = $this->getPerPage();
        $query->limit($pp, ($this->getPage() - 1) * $pp);

        $this->_select = $query;
        return $query;
    }

    public function currentPage(): array {
        return $this->mapper->fetchQuery($this->getSelect());
    }

    public function getCaptions(): array {
        $result = [];

        foreach ($this->columns as $name => $col) {
            $caption = new TableCaption($col->caption);

            if ($this->allow_ordering && $col->canOrderBy()) {
                // $order = $col->getOrderBy()
                $caption->order_by = $name;
                $caption->order_dir = ($name === $this->getOrderBy()) ? $this->getOrderDir() : '';
            }

            $result[] = $caption;
        }
        return $result;
    }

    public function getFilterValue(string $key, mixed $default = null): mixed {
        return $this->filter[$key] ?? $default;
    }
    
    public function getSearch(): ?string {
        return $this->getFilterValue('search');
    }

    public function getOrderDir(): ?string {
        return $this->getFilterValue('order_dir');
    }

    public function getOrderBy(): ?string {
        return $this->getFilterValue('order_by');
    }

    public function getPage(): int {
        return (int) $this->getFilterValue('page', 1);
    }

    public function getPerPage(): int {
        return (int) $this->getFilterValue('pp', 1);
    }
    
    public function getCount(): int {
        return Database::getInstance()->count($this->getSelect());
    }

    public function getMaxPages(): int {
        return (int) ceil($this->getCount() / $this->getPerPage());
    }
}
