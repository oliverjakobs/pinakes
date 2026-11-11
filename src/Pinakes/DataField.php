<?php declare(strict_types=1);

namespace App\Pinakes;

use Closure;
use App\Database\MappedProperty;
use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use App\Renderable\Renderable;
use App\Renderable\ViewElement;

class DataField {
    public readonly PinakesMapper $mapper;

    public readonly string $caption;
    public readonly ?string $property;
    public readonly DataType $data_type;
    public readonly ?MappedProperty $mapping;

    /** @var MappedProperty[] $requires */
    public readonly array $requires;

    private ?Closure $data_cb;
    private ?Closure $render_cb;

    public readonly bool $link;
    public readonly bool $edit;
    // public readonly string $visibility;

    public function __construct(
        PinakesMapper $mapper,
        string|Closure $data,
        ?DataType $data_type = null,
        string $caption = '',
        ?Closure $render = null,
        bool $link = false,
        bool $edit = false,
        array $requires = [],
    ) {
        $this->mapper = $mapper;

        if (is_callable($data)) {
            $this->property = null;
            $this->data_cb = $data;
        } else {
            $this->property = $data;
            $this->data_cb = null;
        }

        $mapping = null;
        if (null !== $this->property) {
            $mapping = $mapper->getMapping($this->property);

            if (null === $data_type) {
                $data_type = DataType::fromMapping($mapping);
            }
        }

        $this->mapping = $mapping;

        Assert::notNull($data_type, 'Failed to determine data type');
        $this->data_type = $data_type;

        $this->render_cb = $render;

        $this->caption = $caption;
        $this->link = $link;
        $this->edit = $edit;

        $required_properies = [];
        foreach ($requires as $req) {
            $required_properies[] = $mapper->getMapping($req);
        }
        $this->requires = $required_properies;
    }

    public function canOrderBy(): bool {
        if (!empty($this->order_by)) return true;
        if (!$this->data_type->isSortable()) return false;
        return null !== $this->property;
    }

    public function getOrderBy(): string {
        if (!$this->canOrderBy()) return '';

        if ($this->mapping->isVirtual()) {
            return $this->property;
        }

        if (DataType::TYPE_ENTITY === $this->data_type->type) {
            $search_key = $this->data_type->getTargetMapper()->getSearchKey();
            return "{$this->mapping->name}.{$search_key}";
        }
        
        return "self.{$this->property}";
    }

    public function getData(PinakesEntity $entity): mixed {
        if (null !== $this->data_cb) return call_user_func($this->data_cb, $entity);
        return $entity->{$this->property};
    }

    public function renderCell(PinakesEntity $entity): Renderable {
        $data = $this->getData($entity);

        if ($this->link && null !== $data) {
            if (DataType::TYPE_ENTITY === $this->data_type->type) {
                $data = $data->getLinkSelf();
            } elseif ((DataType::TYPE_COLLECTION === $this->data_type->type)) {
                $data = array_map(fn (PinakesEntity $d) => $d->getLinkSelf(), $data);
            }
        }

        if (null !== $this->render_cb) {
            $value = call_user_func($this->render_cb, $data);
        } else {
            $value = $this->data_type->render($data);
        }

        if ($this->link && !$this->data_type->isTargetType()) {
            $value = $entity->getLinkSelf($value);
        }
        return ViewElement::create('td', $value)->addStyleClasses(...$this->data_type->getStyleClasses());
    }
}
