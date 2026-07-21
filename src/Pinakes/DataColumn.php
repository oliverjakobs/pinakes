<?php declare(strict_types=1);

namespace App\Pinakes;

use Closure;
use App\Entity\PinakesEntity;
use App\Entity\User;
use App\Renderable\Renderable;
use App\Renderable\ViewElement;
use App\Repository\PinakesRepository;
use Doctrine\ORM\QueryBuilder;

class DataColumn {
    const LINK_NONE = 0;
    const LINK_SELF = 1;
    const LINK_DATA = 2;

    public readonly string $name;
    public readonly string $caption;
    public readonly ?string $property;
    public readonly DataType $data_type;

    private ?Closure $data_cb;
    private ?Closure $edit_cb;
    private ?Closure $filter_cb;

    private int $link;

    public readonly bool $edit;
    public readonly string $visibility;
    public readonly array $order_by;

    public function __construct(
        PinakesRepository $repository,
        string $name,
        string|Closure $data,
        ?DataType $data_type = null,
        array|string $order_by = [],
        string $caption = '',
        int $link = self::LINK_NONE,
        bool $edit = false,
        ?Closure $edit_callback = null,
        ?Closure $filter = null,
        string $visibility = User::ROLE_USER,
    ) {        
        $this->name = $name;

        if (is_callable($data)) {
            $this->property = null;
            $this->data_cb = $data;
        } else {
            $this->property = $data;
            $this->data_cb = null;

            if (null === $data_type) {
                $data_type = $repository->getDataType($this->property);
            }
        }

        Assert::notNull($data_type, 'Failed to determine data type for ' . $name);
        $this->data_type = $data_type;
        
        Assert::inArray($link, [ self::LINK_NONE, self::LINK_SELF, self::LINK_DATA ], 'Unkown link type');
        Assert::isTrue(self::LINK_DATA !== $link || $data_type->isTargetType(), 'Can not link to ' . (string) $data_type);
        $this->link = $link;

        $this->caption = $caption;
        $this->visibility = $visibility;
        $this->edit = $edit;
        $this->edit_cb = $edit_callback;
        $this->filter_cb = $filter;

        if (is_string($order_by)) $order_by = [ $order_by ];
        $this->order_by = $order_by;
    }

    public function canOrderBy(): bool {
        if (!empty($this->order_by)) return true;
        if (!$this->data_type->isSortable()) return false;
        return null !== $this->property;
    }

    public function filter(QueryBuilder $qb, mixed $value): QueryBuilder {
        if (null !== $this->filter_cb) {
            return call_user_func($this->filter_cb, $qb, $value);
        }

        Assert::notNull($this->property, 'Cannot filter without property');

        if ($this->data_type->isArrayType()) {
            if (!is_iterable($value)) $value = [ $value ];
    
            foreach ($value as $idx => $v) {    
                $key = $this->property . $idx;
                $expr = $qb->expr()->isMemberOf(':' . $key, 'e.' . $this->property);
                $qb->andWhere($expr)->setParameter($key, $v);
            }
            return $qb;
        }

        $expr = $qb->expr()->eq(':' . $this->name, 'e.' . $this->property);
        return $qb->andWhere($expr)->setParameter(':' . $this->name, $value);
    }

    public function orderBy(QueryBuilder $qb, string $dir): QueryBuilder {
        if (!$this->canOrderBy()) return $qb;

        if (!empty($this->order_by)) {
            foreach ($this->order_by as $by) {
                $qb->addOrderBy($by, $dir);
            }
            return $qb;
        }

        if (DataType::TYPE_ENTITY === $this->data_type->type) {
            $target = $this->data_type->getTargetRepository();
            return $qb->leftJoin('e.' . $this->property, $this->name)->addOrderBy($this->name . '.' . $target->getSearchKey(), $dir);
        }
        
        return $qb->orderBy('e.' . $this->name, $dir);
    }

    public function getData(PinakesEntity $entity): mixed {
        if (null !== $this->data_cb) return call_user_func($this->data_cb, $entity);
        return $entity->{$this->property};
    }

    public function updateEntity(PinakesEntity $entity, string|array|null $value): void {
        if (!$this->edit) return;

        $value = $this->data_type->parse($value);
        if (null !== $this->edit_cb) {
            call_user_func($this->edit_cb, $entity, $value);
        } else {
            $entity->{$this->property} = $value;
        } 
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
