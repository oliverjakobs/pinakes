<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Entity\TagInterface;
use App\Renderable\Renderable;
use App\Renderable\FormElement;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\FieldMapping;

class DataType {

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';

    const TYPE_DATETIME = 'datetime';
    const TYPE_CURRENCY = 'currency';
    const TYPE_COLOR = 'color';

    const TYPE_ENTITY = 'entity';
    const TYPE_COLLECTION = 'collection';
    const TYPE_TAGS = 'tags';

    const TYPE_ACTION = 'action';

    private function __construct(
        private string $type,
        private array $config = []
    ) {
    }

    public static function fromFieldMapping(FieldMapping $mapping): self {
        return new self($mapping->type);
    }

    public static function string(): self {
        return new self(self::TYPE_STRING);
    }

    public static function integer(?int $min = null, ?int $max = null): self {
        return new self(self::TYPE_INTEGER, [
            'min' => $min,
            'max' => $max
        ]);
    }

    public static function float(?float $min = null, ?float $max = null): self {
        return new self(self::TYPE_FLOAT, [
            'min' => $min,
            'max' => $max
        ]);
    }

    public static function currency(string $currency = '€'): self {
        return new self(self::TYPE_CURRENCY, [
            'fmt' => '%.2f ' . $currency,
        ]);
    }

    public static function datetime(string $fmt): self {
        return new self(self::TYPE_DATETIME, [
            'fmt' => $fmt
        ]);
    }

    public static function color(): self {
        return new self(self::TYPE_COLOR);
    }

    public static function action(): self {
        return new self(self::TYPE_ACTION);
    }

    private static function isValidTarget(string $target): bool {
        return is_a($target, PinakesEntity::class, true);
    }

    public static function entity(string $entity): self {
        assert(self::isValidTarget($entity), 'Invalid target ' . $entity);
        return new self(self::TYPE_ENTITY, [ 'target' => $entity ]);
    }

    public static function collection(string $entity, ?string $separator = null): self {
        $result = self::entity($entity);
        $result->type = self::TYPE_COLLECTION;
        return $result;
    }

    public static function tags(string $entity): self {
        assert(is_a($entity, TagInterface::class, true), 'Invalid tag-target ' . $entity);
        $result = self::entity($entity);
        $result->type = self::TYPE_TAGS;
        return $result;
    }

    public function __toString(): string {
        return $this->type . match($this->type) {
            self::TYPE_ENTITY, self::TYPE_COLLECTION => '(' . $this->config['target'] . ')',
            default => ''
        };
    }

    public function parse(string|array|null $value): mixed {
        switch ($this->type) {
            case self::TYPE_ENTITY:
                if (null === $value) return null;
                assert(is_string($value), 'Expected string got array');
                $repository = Pinakes::getRepository($this->config['target']);
                return $repository->getOrCreate($value, false);

            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
                if (null === $value) return new ArrayCollection();
                assert(is_array($value), 'Expected array got string');
                $repository = Pinakes::getRepository($this->config['target']);
                $entities = array_map(fn ($e) => $repository->getOrCreate($e, false), $value);
                return new ArrayCollection($entities);

            case self::TYPE_DATETIME:
                return new DateTime($value);
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
                if (null === $value) return null;
                return floatval($value);
            case self::TYPE_INTEGER:
                if (null === $value) return null;
                return intval($value);
            case self::TYPE_ACTION:
                assert(false, 'Cannot parse for type "' . $this->type . '"');
        }

        return $value;
    }

    public function render(mixed $data): string {
        if (null === $data) return '-';

        switch ($this->type) {
            case self::TYPE_ENTITY:
                return (string) $data;

            case self::TYPE_COLLECTION:
                if ($data instanceof Collection) $data = $data->toArray();
        
                $separator = $this->config['separator'] ?? null;
                if (null !== $separator) return implode($separator, $data);
        
                return ViewElement::ul($data)->addClasses(['collection'])->render();

            case self::TYPE_TAGS:
                if ($data instanceof Collection) $data = $data->toArray();

                $data = array_map(fn ($tag) => $tag->getTag(), $data);
                return implode(' ', $data);

            case self::TYPE_CURRENCY:
                return sprintf($this->config['fmt'], $data);
            case self::TYPE_COLOR:
                return ViewElement::tag($data, $data)->addClasses(['monospace'])->render();
            case self::TYPE_DATETIME:
                assert($data instanceof DateTime);
                return $data->format($this->config['fmt'] ?? 'd.m.Y');
            case self::TYPE_ACTION:
                assert($data instanceof Link);
                return $data->render();
        }
        return (string) $data;
    }

    public function getForm(string $name, mixed $value): Renderable {
        switch ($this->type) {
            case self::TYPE_ENTITY:
            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
                $repository = Pinakes::getRepository($this->config['target']);
                return FormElement::autocomplete($name, $repository->getOptions(), $value);
            case self::TYPE_COLOR:
                return FormElement::input($name, 'color', $value);
            case self::TYPE_DATETIME:
                assert($value instanceof DateTime);
                return FormElement::input($name, 'date', $value->format('Y-m-d'));
            case self::TYPE_INTEGER:
                $min = $this->config['min'] ?? null;
                $max = $this->config['max'] ?? null;
                return FormElement::number($name, $value, $min, $max);
            case self::TYPE_ACTION:
                assert(false, 'Cannot edit type "' . $this->type . '"');
        }
        return FormElement::input($name, 'text', $value);
    }

    public function getStyleClasses(): array {
        switch ($this->type) {
            case self::TYPE_DATETIME:
            case self::TYPE_CURRENCY:
            case self::TYPE_INTEGER:
                return [ 'align-right', 'fit-content' ];
        }

        return [];
    }
}
