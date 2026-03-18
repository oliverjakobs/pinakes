<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;

class DataTable {

    const DEFAULT_FILTER = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
    ];

    private array $filter = self::DEFAULT_FILTER;
    private array $hidden = [];

    private ?PinakesRepository $repository;
    private array $columns;
    private ?array $data = null;

    private ?string $component_path = null; 

    public bool $allow_pagination = true;
    public bool $allow_ordering = true;

    public function __construct(?PinakesRepository $repository, array $columns) {
        $this->repository = $repository;
        $this->columns = $columns;
    }

    public static function fromData(array $data): self {
        $result = new self(null, []);
        return $result->setData($data);
    }

    public function setData(array $data): self {
        $this->data = $data;
        return $this;
    }

    public function addFilter(string $name, mixed $value): self {
        assert(null === $this->data, 'Table is finalized');

        $this->hidden[] = $name;

        if (is_iterable($value)) {
            $value = array_map(fn ($v) => ($v instanceof PinakesEntity) ? $v->getId() : $v, $value);
        } else if ($value instanceof PinakesEntity) {
            $value = $value->getId();
        }

        $this->filter[$name] = $value;
        return $this;
    }

    public function setQuery(array $query): bool {
        $filter_only = $query['filter_only'] ?? false;
        unset($query['filter_only']);
        $this->filter = array_merge($this->filter, array_filter($query));
        return boolval($filter_only);
    }

    public function getRepository(): ?PinakesRepository {
        return $this->repository;
    }

    public function getDataFields(): array {
        return $this->columns;
    }

    public function getColumn(string $name): DataColumn {
        assert(array_key_exists($name, $this->columns), 'Unkown column ' . $name);
        return $this->columns[$name];
    }

    public function getFilter(): array {
        return $this->filter;
    }

    public function buildQuery(): string {
        $diff = [
            'order_dir' => null,
            'order_by' => null,
            'pp' => null,
            'filter_only' => null
        ];

        if (1 === intval($this->filter['page'])) {
            $diff['page'] = null;
        }

        $hidden_keys = array_flip($this->hidden);
        $query = array_diff_key($this->filter, $diff, $hidden_keys);
        return http_build_query($query);
    }
    public function setComponentPath(string $path): self {
        $this->component_path = $path;
        return $this;
    }

    public function getComponentPath(): string {
        if (null !== $this->component_path) return $this->component_path;
        return 'components/table.html.twig';
    }

    public function getFilterValue(string $key): mixed {
        return $this->filter[$key] ?? null;
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

    public function getData(): array {
        if (null === $this->data) {
            $this->data = $this->repository->applyFilter($this->filter);
        }
        return $this->data;
    }

    public function getPage(): int {
        return intval($this->filter['page']);
    }

    public function getPerPage(): int {
        $pp = intval($this->filter['pp']);
        return ($pp <= 0) ? 1 : $pp;
    }

    public function getMaxPages(): int {
        $max_count = count($this->getData());
        return (int) ceil($max_count / $this->getPerPage());
    }

    public function getCurrentPage(): array {
        $pp = $this->getPerPage();
        return array_slice($this->getData(), ($this->getPage() - 1) * $pp, $pp);
    }
}
