<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Database\MappedProperty;
use Closure;
use App\Entity\PinakesEntity;
use App\Mapper\PinakesMapper;
use App\Renderable\Renderable;
use App\Renderable\ViewElement;

class DataField {
    const LINK_NONE = 0;
    const LINK_SELF = 1;
    const LINK_DATA = 2;

    public readonly PinakesMapper $mapper;

    public readonly string $name;
    public readonly string $caption;
    public readonly ?string $property;
    public readonly DataType $data_type;
    public readonly ?MappedProperty $mapping;

    private ?Closure $data_cb;

    private int $link;

    public readonly bool $edit;

    public function __construct(
        PinakesMapper $mapper,
        string $name,
        string|Closure $data,
        ?DataType $data_type = null,
        string $caption = '',
        int $link = self::LINK_NONE,
        bool $edit = false,
    ) {        
        $this->mapper = $mapper;
        $this->name = $name;

        if (is_callable($data)) {
            $this->property = null;
            $this->data_cb = $data;
        } else {
            $this->property = $data;
            $this->data_cb = null;
        }

        $mapping = null;
        if (null !== $this->property) {
            $mapping = MappedProperty::create($mapper->getEntityClass(), $this->property);

            if (null === $data_type) {
                $data_type = DataType::fromMapping($mapping);
            }
        }

        $this->mapping = $mapping;

        Assert::notNull($data_type, 'Failed to determine data type for ' . $name);
        $this->data_type = $data_type;
        
        Assert::inArray($link, [ self::LINK_NONE, self::LINK_SELF, self::LINK_DATA ], 'Unkown link type');
        Assert::isTrue(self::LINK_DATA !== $link || $data_type->isTargetType(), 'Can not link to ' . (string) $data_type);
        $this->link = $link;

        $this->caption = $caption;
        $this->edit = $edit;
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
            $target = $this->data_type->getTargetMapper();
            return "\"{$target->getTableName()}:{$target->getSearchKey()}\"";
        }
        
        return "{$this->mapper->getTableName()}.{$this->property}";
    }

    public function getData(PinakesEntity $entity): mixed {
        if (null !== $this->data_cb) return call_user_func($this->data_cb, $entity);
        return $entity->{$this->property};
    }

    public function renderCell(PinakesEntity $entity): Renderable {
        $data = $this->getData($entity);

        $value = $this->data_type->render($data, self::LINK_DATA === $this->link);
        if (self::LINK_SELF === $this->link) {
            $value = $entity->getLinkSelf($value);
        }
        return ViewElement::create('td', $value)->addStyleClasses(...$this->data_type->getStyleClasses());
    }
}
