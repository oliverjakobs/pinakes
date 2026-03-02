<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Repository\PinakesRepository;

class DataTable {

    const DEFAULT_FILTER = [
        'order_by' => null,
        'order_dir' => 'desc',
        'page' => 1,
        'pp' => 30,
    ];

    private array $filter = self::DEFAULT_FILTER;

    private ?PinakesRepository $repository;
    private array $columns;
    private ?array $data = null;

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

    public function applyFilter(array $filter): self {
        assert(null === $this->data, 'Table is finalized');
        $this->filter = array_merge($this->filter, $filter);
        return $this;
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

    public function getFilterValue(string $key): mixed {
        return $this->filter[$key] ?? null;
    }

    public function getSearch(): ?string {
        return $this->filter['search'] ?? null;
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
