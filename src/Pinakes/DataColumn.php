<?php declare(strict_types=1);

namespace App\Pinakes;

use Closure;
use App\Entity\PinakesEntity;
use App\Renderable\FormElement;
use App\Renderable\ViewElement;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;

class DataColumn {
    const LINK_NONE = 0;
    const LINK_SELF = 1;
    const LINK_DATA = 2;

    public readonly string $name;
    public readonly ?string $property;

    private ?DataType $data_type;
    private string|Closure $data;

    public string $caption;

    private int $link;

    private bool $edit;
    private ?Closure $edit_cb = null;
    private ?Closure $filter_cb = null;

    private ?string $visibility = null;

    public function __construct(PinakesRepository $repository, string $name, array $options) {
        assert(isset($options['data']), 'No data specified');

        $data = $options['data'];
        $edit = $options['edit'] ?? false;

        if (is_callable($data)) {
            $property = null;
        } else {
            $property = $data;
        }

        $data_type = $options['data_type'] ?? null;
        if (null === $data_type && null !== $property) {
            $data_type = $repository->getDataType($property);
        }
        assert(null !== $data_type, 'Failed to determine data type for ' . $name);

        $this->name = $name;
        $this->property = $property;
        $this->data = $data;
        $this->data_type = $data_type;

        $this->caption = $options['caption'] ?? '';
        $this->link = $options['link'] ?? self::LINK_NONE;
        $this->visibility = $options['visibility'] ?? null;
        $this->edit = is_string($edit) || $edit;
        $this->edit_cb = $options['edit_callback'] ?? null;
        $this->filter_cb = $options['filter'] ?? null;
    }

    public function isVisible(): bool {
        return Pinakes::isGranted($this->visibility);
    }

    public function canOrderBy(): bool {
        return null !== $this->property;
    }

    public function filter(QueryBuilder $qb, mixed $value): QueryBuilder {
        if (null === $this->filter_cb) return $qb;
        assert(is_callable($this->filter_cb));
        return call_user_func($this->filter_cb, $qb, $value);
    }

    public function getData(PinakesEntity $entity): mixed {
        if (is_callable($this->data)) return call_user_func($this->data, $entity);
        return $entity->getValue($this->data);
    }

    public function renderValue(PinakesEntity $entity): string {
        // Step 1: Get data
        $data = $this->getData($entity);

        // Step 2: Apply link
        if (null !== $data) {
            if (self::LINK_SELF === $this->link) {
                assert(!is_iterable($data), 'Iterables can only link to data');
                $data = $entity->getLinkSelf((string) $data);
            } else if (self::LINK_DATA === $this->link) {
                if (is_iterable($data)) {
                    if ($data instanceof Collection) $data = $data->toArray();
                    $data = array_map(fn (PinakesEntity $e) => $e->getLinkSelf(), $data);
                } else {
                    assert($data instanceof PinakesEntity, 'Can only link to entities');
                    $data = $data->getLinkSelf((string) $data);
                }
            } else {
                assert(self::LINK_NONE === $this->link, 'Unkown link type');
            }
        }

        // Step 3: Render data
        $value = $this->data_type->render($data);
        return ViewElement::create('td', $value)->addClasses($this->data_type->getStyleClasses())->render();
    }

    public function renderForm(PinakesEntity $entity): string {
        if (!$this->edit) return '';
        
        // Step 1: Get data
        $data = $this->getData($entity);

        // Step 2: Get form element
        $form = $this->data_type->getForm($this->name, $data);
        return $form->render();
    }

    public function renderExport(PinakesEntity $entity): string {
        // Step 1: Get data
        $data = $this->getData($entity);

        // Step 2: Render
        return $this->data_type->export($data);
    }

    public function getFilterForm(mixed $value): FormElement {
        return $this->data_type->getForm($this->name, $this->data_type->parse($value));
    }

    public function updateEntity(PinakesEntity $entity, string|array|null $value): void {
        if (!$this->edit) return;

        $value = $this->data_type->parse($value);

        // callback
        if (null !== $this->edit_cb) {
            call_user_func($this->edit_cb, $entity, $value);
        } else {
            $entity->setValue($this->property, $value);
        } 
    }
}
