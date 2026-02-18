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

    private PinakesRepository $repository;
    private array $data_fields;
    private array $filter = self::DEFAULT_FILTER;

    public function __construct(PinakesRepository $repository, string $fields) {
        $this->repository = $repository;
        $this->data_fields = $repository->getDataFields($fields);
    }

    public function applyFilter(array $filter): self {
        $this->filter = array_merge($this->filter, $filter);
        return $this;
    }

    public function getRepository(): PinakesRepository {
        return $this->repository;
    }

    public function getDataFields(): array {
        return $this->data_fields;
    }

    public function getFilter(): array {
        return $this->filter;
    }

    public function getData(): array {
        return $this->repository->applyFilter($this->filter);
    }
}
