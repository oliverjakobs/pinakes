<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Repository\PinakesRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DataTable {

    const DEFAULT_QUERY = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
    ];

    private array $filter = [];
    private array $query = self::DEFAULT_QUERY;

    private ?PinakesRepository $repository;
    private ?array $data = null;
    private ?Paginator $paginator = null;
    public readonly array $columns;

    public string $component_path = 'components/table.html.twig'; 
    public bool $allow_pagination = true;
    public bool $allow_ordering = true;

    public function __construct(?PinakesRepository $repository, array $columns) {
        $this->repository = $repository;
        $this->columns = $columns;
    }

    public static function fromData(array $data, array $columns): self {
        $result = new self(null, $columns);
        $result->data = $data;
        return $result;
    }

    public function addFilter(string $name, mixed $value): self {
        assert(null === $this->data, 'Table is finalized');

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
        $this->query = array_merge($this->query, array_filter($query));
        return boolval($filter_only);
    }

    public function getColumn(string $name): DataColumn {
        assert(array_key_exists($name, $this->columns), 'Unkown column ' . $name);
        return $this->columns[$name];
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

    public function finalize(): self {
        if (null !== $this->data || null === $this->repository) return $this;

        $pp = $this->getPerPage();
        $offset = ($this->getPage() - 1) * $pp;

        $filter = array_merge($this->filter, $this->query);
        $query = $this->repository->getFilterQuery($filter)
            ->setMaxResults($pp)
            ->setFirstResult($offset);

        $this->paginator = new Paginator($query);
        return $this;
    }

    public function getData(): array {
        if (null !== $this->data) return $this->data;
        if (null === $this->repository) return [];
        return $this->repository->getFilterQuery($this->filter)->getQuery()->getResult();
    }

    public function getCount(): int {
        if (null !== $this->paginator) return count($this->paginator);
        return count($this->data);
    }

    public function getMaxPages(): int {
        return (int) ceil($this->getCount() / $this->getPerPage());
    }

    public function getCurrentPage(): Paginator|array {
        if (null !== $this->paginator) return $this->paginator;
        $pp = $this->getPerPage();
        return array_slice($this->data, ($this->getPage() - 1) * $pp, $pp);
    }
}
