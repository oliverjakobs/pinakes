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

    public readonly DataType $data_type;
    private string|Closure $data;

    public string $caption;

    private int $link;

    private bool $edit;
    private ?Closure $edit_cb = null;
    private ?Closure $filter_cb = null;

    private ?string $visibility = null;

    public readonly array $order_by;

    public function __construct(PinakesRepository $repository, string $name, array $options) {
        assert(isset($options['data']), 'No data specified');

        $data = $options['data'];
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

        $this->link = $options['link'] ?? self::LINK_NONE;

        if (self::LINK_DATA === $this->link) {
            assert($data_type->isTargetType(), 'Can not link to ' . (string)$data_type);
        } else {
            assert(self::LINK_NONE === $this->link || self::LINK_SELF === $this->link, 'Unkown link type');
        }

        $this->name = $name;
        $this->property = $property;
        $this->data = $data;
        $this->data_type = $data_type;

        $this->caption = $options['caption'] ?? '';
        $this->visibility = $options['visibility'] ?? null;
        $this->edit = $options['edit'] ?? false;
        $this->edit_cb = $options['edit_callback'] ?? null;
        $this->filter_cb = $options['filter'] ?? null;

        $order_by = $options['order_by'] ?? [];
        if (is_string($order_by)) $order_by = [ $order_by ];
        $this->order_by = $order_by;
    }

    public function isVisible(): bool {
        return Pinakes::isGranted($this->visibility);
    }

    public function canOrderBy(): bool {
        if (!empty($this->order_by)) return true;
        if (!$this->data_type->isSortable()) return false;
        return null !== $this->property;
    }

    public function filter(QueryBuilder $qb, mixed $value): QueryBuilder {
        if (null !== $this->filter_cb) {
            assert(is_callable($this->filter_cb));
            return call_user_func($this->filter_cb, $qb, $value);
        }

        assert(null !== $this->property, 'Cannot filter without property');
        
        if (DataType::TYPE_ENTITY == $this->data_type->type) {
            $expr = $qb->expr()->eq(':' . $this->name, 'e.' . $this->property);
            return $qb->andWhere($expr)->setParameter(':' . $this->name, $value);
        }

        if ($this->data_type->isArrayType()) {
            if (!is_iterable($value)) $value = [ $value ];
    
            foreach ($value as $idx => $v) {    
                $key = $this->property . $idx;
                $expr = $qb->expr()->isMemberOf(':' . $key, 'e.' . $this->property);
                $qb->andWhere($expr)->setParameter($key, $v);
            }
            return $qb;
        }

        return $qb;
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
        if (is_callable($this->data)) return call_user_func($this->data, $entity);
        return $entity->getValue($this->data);
    }

    public function renderValue(PinakesEntity $entity): string {
        $data = $this->getData($entity);
        if ($data instanceof Collection) $data = $data->toArray();

        if (null !== $data && self::LINK_DATA === $this->link) {
            if (is_iterable($data)) {
                $data = array_map(fn (PinakesEntity $d) => $d->getLinkSelf(), $data);
            } else {
                $data = $data->getLinkSelf((string) $data);
            }
        }

        $value = $this->data_type->render($data);
        if (self::LINK_SELF === $this->link) {
            $value = $entity->getLinkSelf($value);
        }
        return ViewElement::create('td', $value)->addClasses($this->data_type->getStyleClasses())->render();
    }

    public function renderForm(PinakesEntity $entity): string {
        if (!$this->edit) return '';
        $data = $this->getData($entity);

        $form = $this->data_type->getForm($this->name, $data);
        return $form->render();
    }

    public function renderExport(PinakesEntity $entity): string {
        $data = $this->getData($entity);
        return $this->data_type->export($data);
    }

    public function getFilterForm(mixed $value): FormElement {
        return $this->data_type->getForm($this->name, $this->data_type->parse($value));
    }

    public function updateEntity(PinakesEntity $entity, string|array|null $value): void {
        if (!$this->edit) return;

        $value = $this->data_type->parse($value);
        if (null !== $this->edit_cb) {
            call_user_func($this->edit_cb, $entity, $value);
        } else {
            $entity->setValue($this->property, $value);
        } 
    }
}
