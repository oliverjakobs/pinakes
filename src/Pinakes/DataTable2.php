<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Database\Database;
use App\Database\SQL\Query;
use App\Mapper\PinakesMapper;

class TableCaption {
    public string $text;

    public string $order_by = '';
    public string $order_dir = '';

    public function __construct(string $text) {
        $this->text = $text;
    }
}

class DataTable2 {
    const DEFAULT_QUERY = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
    ];

    private array $filter = [];
    private array $query = self::DEFAULT_QUERY;
    
    public string $component_path = 'components/table2.html.twig'; 
    public bool $allow_pagination = true;
    public bool $allow_ordering = true;

    public readonly PinakesMapper $mapper;
    /** @var DataField[] $columns */
    public readonly array $columns;

    public function __construct(PinakesMapper $mapper, array $columns) {
        $this->mapper = $mapper;
        $this->columns = $columns;
    }

    public function getColumn(string $name): DataField {
        Assert::isTrue(array_key_exists($name, $this->columns), 'Unkown column ' . $name);
        return $this->columns[$name];
    }
    
    public function setOrder(string $by, string $dir = 'ASC'): self {
        $this->query['order_by'] = $by;
        $this->query['order_dir'] = $dir;
        return $this;
    }

    public function setPP(int $pp): self {
        $this->query['pp'] = $pp;
        return $this;
    }

    public function setQuery(array $query): bool {
        $filter_only = $query['filter_only'] ?? false;
        unset($query['filter_only']);
        $this->query = array_merge($this->query, array_filter($query));
        return boolval($filter_only);
    }

    public function getFilterUrl(string $route, array $params): string {
        return Pinakes::getUrl($route, array_merge(
            $this->filter,
            $this->query,
            $params,
            ['filter_only' => true]
        ));
    }

    public function buildQuery(): string {
        $diff = [
            'order_dir' => null,
            'order_by' => null,
            'pp' => null,
            'filter_only' => null
        ];

        if (1 === intval($this->query['page'])) {
            $diff['page'] = null;
        }

        $query = array_diff_key($this->query, $diff, $this->filter);
        return http_build_query($query);
    }

    private ?Query $_select = null;
    public function getSelect(): Query {
        if (null !== $this->_select) return $this->_select;
        $query = $this->mapper->createQuery($this->columns);

        $filter = array_merge($this->filter, $this->query);

        // add search
        $search = $filter['search'] ?? null;
        if (null !== $search) {
            $query->addWhere('.' . $this->mapper->getSearchKey() . ' LIKE :search');
            $query->setParameter(':search', '%' . $search . '%');
        }

        // // filter by data fields
        // foreach ($filter as $name => $value) {
        //     $field = $this->getColumn($name);
        //     if (null !== $field) $qb = $field->filter($qb, $value);
        // }

        // apply order
        $by = $filter['order_by'] ?? null;
        if (null !== $by) {
            $field = $this->mapper->getDataField($by);
            // if (null !== $field) 
            $query->addOrderBy($field->getOrderBy(), $filter['order_dir'] ?? 'asc');
        } else {
            foreach ($this->mapper->getDefaultOrder() as $by => $dir) {
                $query->addOrderBy($by, $dir);
            }
        }

        $pp = $this->getPerPage();
        $query->setLimit($pp, ($this->getPage() - 1) * $pp);

        $this->_select = $query;
        return $query;
    }

    private ?array $_data = null;
    public function getData(): array {
        if (null !== $this->_data) return $this->_data;

        $db = Database::getInstance();
        $rows = $db->query($this->getSelect());

        $entities = [];
        foreach ($rows as $row) {
            $entity_class = $this->mapper->getEntityClass();
            $entities[] = Database::hydrate(new $entity_class(), $row);
        }

        return $entities;
    }

    public function currentPage(): array {

        $db = Database::getInstance();
        $rows = $db->query($this->getSelect());

        $entities = [];
        foreach ($rows as $row) {
            $entity_class = $this->mapper->getEntityClass();
            $entities[] = Database::hydrate(new $entity_class(), $row);
        }

        return $entities;
    }

    public function getCaptions(): array {
        $result = [];

        foreach ($this->columns as $col) {
            $caption = new TableCaption($col->caption);

            if ($this->allow_ordering && $col->canOrderBy()) {
                // $order = $col->getOrderBy()
                $caption->order_by = $col->name;
                $caption->order_dir = ($col->name === $this->getOrderBy()) ? $this->getOrderDir() : '';
            }

            $result[] = $caption;
        }
        return $result;
    }

    public function getFilterValue(string $key, mixed $default = null): mixed {
        return $this->query[$key] ?? $this->filter[$key] ?? $default;
    }

    public function getFilterValueInt(string $key, int $default = 0): int {
        return intval($this->getFilterValue($key, $default));
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
        return $this->getFilterValueInt('page', 1);
    }

    public function getPerPage(): int {
        return $this->getFilterValueInt('pp', 1);
    }
    
    public function getCount(): int {
        return Database::getInstance()->count($this->getSelect());
    }

    public function getMaxPages(): int {
        return (int) ceil($this->getCount() / $this->getPerPage());
    }
}
