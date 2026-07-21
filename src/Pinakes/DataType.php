<?php declare(strict_types=1);

namespace App\Pinakes;

use DateTime;
use BackedEnum;
use App\Entity\PinakesEntity;
use App\Entity\TagInterface;
use App\Renderable\Renderable;
use App\Renderable\FormElement;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\PinakesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DataType {
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_ARRAY = 'array';

    const TYPE_ENUM = 'enum';

    const TYPE_DATETIME = 'datetime';
    const TYPE_CURRENCY = 'currency';
    const TYPE_COLOR = 'color';

    const TYPE_ENTITY = 'entity';
    const TYPE_COLLECTION = 'collection';
    const TYPE_TAGS = 'tags';

    const TYPE_ACTION = 'action';

    const TARGET_TYPES = [
        self::TYPE_ENTITY,
        self::TYPE_COLLECTION,
        self::TYPE_TAGS,
    ];

    const ARRAY_TYPES = [
        self::TYPE_ARRAY,
        self::TYPE_COLLECTION,
        self::TYPE_TAGS
    ];
    
    public readonly string $type;

    private ?string $fmt = null;
    private float|int|null $min = null;
    private float|int|null $max = null;

    private ?string $target_class = null;
    private ?array $options = null;

    private function __construct(string $type) {
        $this->type = $type;
    }

    public function setOptions(array $options): self {
        $this->options = $options;
        return $this;
    }

    public static function create(string $type): self {
        return new self($type);
    }

    public static function string(): self {
        return new self(self::TYPE_STRING);
    }

    public static function integer(?int $min = null, ?int $max = null): self {
        $result = new self(self::TYPE_INTEGER);
        $result->min = $min;
        $result->max = $max;
        return $result;
    }

    public static function float(?float $min = null, ?float $max = null): self {
        $result = new self(self::TYPE_FLOAT);
        $result->min = $min;
        $result->max = $max;
        return $result;
    }

    public static function array(): self {
        return new self(self::TYPE_ARRAY);
    }

    public static function enum(string $enum_class): self {
        $result = new self(self::TYPE_ENUM);
        $result->target_class = $enum_class;
        return $result;
    }

    public static function datetime(string $fmt = 'd.m.Y'): self {
        $result = new self(self::TYPE_DATETIME);
        $result->fmt = $fmt;
        return $result;
    }

    public static function currency(string $currency = '€'): self {
        $result = new self(self::TYPE_CURRENCY);
        $result->fmt = '%.2f ' . $currency;
        return $result;
    }

    public static function color(): self {
        return new self(self::TYPE_COLOR);
    }

    private static function withTarget(string $type, string $target): self {
        Assert::isTrue(is_a($target, PinakesEntity::class, true), 'Invalid target ' . $target);
        $result = new self($type);
        $result->target_class = $target;
        return $result;
    }

    public static function entity(string $entity): self {
        return self::withTarget(self::TYPE_ENTITY, $entity);
    }

    public static function collection(string $entity): self {
        return self::withTarget(self::TYPE_COLLECTION, $entity);
    }

    public static function tags(string $entity): self {
        Assert::isTrue(is_a($entity, TagInterface::class, true), 'Invalid tag-target ' . $entity);
        return self::withTarget(self::TYPE_TAGS, $entity);
    }

    public static function action(): self {
        return new self(self::TYPE_ACTION);
    }

    public function __toString(): string {
        if (null !== $this->target_class) return $this->type . '(' . $this->target_class . ')';
        return $this->type;
    }

    public function getTargetRepository(): PinakesRepository {
        Assert::isTrue($this->isTargetType(), 'Not a target type');
        return Pinakes::getRepository($this->target_class);
    }

    public function isSortable(): bool {
        if (DataType::TYPE_ACTION === $this->type) return false;
        if ($this->isArrayType()) return false;
        return true;
    }

    public function isArrayType(): bool {
        return in_array($this->type, self::ARRAY_TYPES);
    }

    public function isTargetType(): bool {
        return in_array($this->type, self::TARGET_TYPES);
    }

    public function parse(string|array|null $value): mixed {
        switch ($this->type) {
            case self::TYPE_ENTITY:
                if (null === $value) return null;
                Assert::isTrue(is_string($value), 'Expected string, got ' . get_debug_type($value) . ' instead');
                return $this->getTargetRepository()->getOrCreate($value);
            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
                if (null === $value) return new ArrayCollection();
                Assert::isTrue(is_array($value), 'Expected array, got ' . get_debug_type($value) . ' instead');
                $repository = $this->getTargetRepository();
                $entities = array_map(fn ($e) => $repository->getOrCreate($e), $value);
                return new ArrayCollection($entities);
            case self::TYPE_DATETIME:
                if (null === $value) return null;
                return new DateTime($value);
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
                if (null === $value) return null;
                return floatval($value);
            case self::TYPE_INTEGER:
                if (null === $value) return null;
                return intval($value);
            case self::TYPE_ENUM:
                if (null === $value) return null;
                return $this->target_class::from($value);
            case self::TYPE_ACTION:
                Assert::error('Cannot parse type "' . $this->type . '"');
        }
        return $value;
    }

    public function render(mixed $data, bool $link = false): Renderable|string {
        if (null === $data) return '-';

        switch ($this->type) {
            case self::TYPE_ENTITY:
                if ($link) return $data->getLinkSelf();
                return (string) $data;
            case self::TYPE_COLLECTION:
                if ($data instanceof Collection) $data = $data->toArray();
                if ($link) $data = array_map(fn (PinakesEntity $d) => $d->getLinkSelf(), $data);
                return ViewElement::ul($data)->addStyleClasses('collection');
            case self::TYPE_ARRAY:
                return ViewElement::ul($data)->addStyleClasses('collection');
            case self::TYPE_TAGS:
                if ($data instanceof Collection) $data = $data->toArray();
                $data = array_map(fn ($tag) => ViewElement::tag($tag->getLinkSelf(), $tag->getColor()), $data);
                return implode(' ', $data);
            case self::TYPE_CURRENCY:
                return sprintf($this->fmt, $data);
            case self::TYPE_COLOR:
                return ViewElement::tag($data, $data)->addStyleClasses('monospace');
            case self::TYPE_DATETIME:
                Assert::instanceOf($data, DateTime::class);
                return $data->format($this->fmt);
            case self::TYPE_ENUM:
                Assert::instanceOf($data, BackedEnum::class);
                return $data->name;
            case self::TYPE_ACTION:
                Assert::instanceOf($data, Link::class);
                return $data->addStyleClasses('button');
        }
        return (string) $data;
    }

    public function export(mixed $data): string {
        if (null === $data) return '';

        switch ($this->type) {
            case self::TYPE_ARRAY:
            case self::TYPE_COLLECTION:
            case self::TYPE_TAGS:
                if ($data instanceof Collection) $data = $data->toArray();
                return implode('; ', $data);
            case self::TYPE_CURRENCY:
                return sprintf($this->fmt, $data);
            case self::TYPE_DATETIME:
                Assert::instanceOf($data, DateTime::class);
                return $data->format(DateTime::ATOM);
            case self::TYPE_ENUM:
                Assert::instanceOf($data, BackedEnum::class);
                return $data->name;
            case self::TYPE_ACTION:
                Assert::error('Cannot export type "' . $this->type . '"');
        }
        return (string) $data;
    }

    private function getOptions(): array {
        if (null !== $this->options) return $this->options;
        if ($this->isTargetType()) return $this->getTargetRepository()->getOptions();

        if (self::TYPE_ENUM === $this->type) {
            $result = [];
            foreach ($this->target_class::cases() as $case) {
                $result[$case->name] = $case->value;
            }
            return $result;
        }
        Assert::error('Cannot get options for type "' . $this->type . '"');
    }

    public function getForm(string $name, mixed $value): FormElement {
        switch ($this->type) {
            case self::TYPE_ENTITY:
            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
            case self::TYPE_ARRAY:
                return FormElement::autocomplete($name, $this->getOptions(), $value);
            case self::TYPE_COLOR:
                return FormElement::input($name, 'color', $value);
            case self::TYPE_DATETIME:
                return FormElement::input($name, 'date', $value?->format('Y-m-d'));
            case self::TYPE_FLOAT:
            case self::TYPE_INTEGER:
                return FormElement::number($name, $value, $this->min, $this->max);
            case self::TYPE_ENUM:
                return FormElement::select($name, $this->getOptions(), $value?->value);
            case self::TYPE_ACTION:
                Assert::error('Cannot edit type "' . $this->type . '"');
        }
        return FormElement::input($name, 'text', $value);
    }

    public function getStyleClasses(): array {
        switch ($this->type) {
            case self::TYPE_DATETIME:
            case self::TYPE_CURRENCY:
            case self::TYPE_INTEGER:
            case self::TYPE_FLOAT:
                return [ 'align-right', 'fit-content' ];
        }
        return [];
    }
}
