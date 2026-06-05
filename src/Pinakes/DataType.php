<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use App\Entity\TagInterface;
use App\Renderable\Renderable;
use App\Renderable\FormElement;
use App\Renderable\Link;
use App\Renderable\ViewElement;
use App\Repository\PinakesRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\FieldMapping;

class DataType {
    const TYPE_STRING = 'string';
    const TYPE_ENUM = 'enum';

    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';

    const TYPE_DATETIME = 'datetime';
    const TYPE_CURRENCY = 'currency';
    const TYPE_COLOR = 'color';

    const TYPE_ENTITY = 'entity';
    const TYPE_COLLECTION = 'collection';
    const TYPE_TAGS = 'tags';

    const TARGET_TYPES = [
        self::TYPE_ENTITY,
        self::TYPE_COLLECTION,
        self::TYPE_TAGS,
    ];

    const ARRAY_TYPES = [
        self::TYPE_COLLECTION,
        self::TYPE_TAGS
    ];

    const TYPE_ACTION = 'action';

    private function __construct(
        public readonly string $type,
        private array $config = []
    ) {
    }

    public static function fromFieldMapping(FieldMapping $mapping): self {
        if (null !== $mapping->enumType) return self::enum($mapping->enumType);

        return new self($mapping->type);
    }

    public static function string(): self {
        return new self(self::TYPE_STRING);
    }

    public static function enum(string $enum_class): self {
        return new self(self::TYPE_ENUM, [
            'enum_class' => $enum_class
        ]);
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

    private static function withTarget(string $type, string $target, array $config = []): self {
        assert(is_a($target, PinakesEntity::class, true), 'Invalid target ' . $target);
        $result = new self($type, $config);
        $result->config['target'] = $target;
        return $result;
    }

    public static function entity(string $entity): self {
        return self::withTarget(self::TYPE_ENTITY, $entity);
    }

    public static function collection(string $entity, ?string $separator = null): self {
        return self::withTarget(self::TYPE_COLLECTION, $entity, [ 'separator' => $separator ]);
    }

    public static function tags(string $entity): self {
        assert(is_a($entity, TagInterface::class, true), 'Invalid tag-target ' . $entity);
        return self::withTarget(self::TYPE_TAGS, $entity);
    }

    public function __toString(): string {
        if ($this->isTargetType()) return $this->type . '(' . $this->config['target'] . ')';
        return $this->type;
    }

    public function getTargetRepository(): PinakesRepository {
        assert($this->isTargetType());
        return Pinakes::getRepository($this->config['target']);
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
                assert(is_string($value));
                return $this->getTargetRepository()->getOrCreate($value);
            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
                if (null === $value) return new ArrayCollection();
                assert(is_array($value));
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
                return $this->config['enum_class']::from($value);
            case self::TYPE_ACTION:
                assert(false, 'Cannot parse for type "' . $this->type . '"');
        }
        return $value;
    }

    public function render(mixed $data): Renderable|string {
        if (null === $data) return '-';

        switch ($this->type) {
            case self::TYPE_ENTITY:
                return (string) $data;
            case self::TYPE_COLLECTION:
                if ($data instanceof Collection) $data = $data->toArray();
                $separator = $this->config['separator'] ?? null;
                if (null !== $separator) return implode($separator, $data);
                return ViewElement::ul($data)->addStyleClasses('collection');
            case self::TYPE_TAGS:
                if ($data instanceof Collection) $data = $data->toArray();
                $data = array_map(fn ($tag) => ViewElement::tag($tag->getLinkSelf(), $tag->getColor()), $data);
                return implode(' ', $data);
            case self::TYPE_CURRENCY:
                return sprintf($this->config['fmt'], $data);
            case self::TYPE_COLOR:
                return ViewElement::tag($data, $data)->addStyleClasses('monospace');
            case self::TYPE_DATETIME:
                assert($data instanceof DateTime, 'Got ' . get_debug_type($data) . ' instead');
                return $data->format($this->config['fmt'] ?? 'd.m.Y');
            case self::TYPE_ENUM:
                assert($data instanceof \BackedEnum, 'Got ' . get_debug_type($data) . ' instead');
                return $data->name;
            case self::TYPE_ACTION:
                assert($data instanceof Link);
                return $data->addStyleClasses('button');
        }
        return (string) $data;
    }

    public function getOptions(): array {
        if ($this->isTargetType()) return $this->getTargetRepository()->getOptions();

        if (self::TYPE_ENUM === $this->type) {
            $result = [];
            foreach ($this->config['enum_class']::cases() as $case) {
                $result[$case->name] = $case->value;
            }
            return $result;
        }
        assert(false, 'Cannot get options for type "' . $this->type . '"');
    }

    public function getForm(string $name, mixed $value): FormElement {
        switch ($this->type) {
            case self::TYPE_ENTITY:
            case self::TYPE_TAGS:
            case self::TYPE_COLLECTION:
                return FormElement::autocomplete($name, $this->getOptions(), $value);
            case self::TYPE_COLOR:
                return FormElement::input($name, 'color', $value);
            case self::TYPE_DATETIME:
                return FormElement::input($name, 'date', $value?->format('Y-m-d'));
            case self::TYPE_INTEGER:
                $min = $this->config['min'] ?? null;
                $max = $this->config['max'] ?? null;
                return FormElement::number($name, $value, $min, $max);
            case self::TYPE_ENUM:
                return FormElement::select($name, $this->getOptions(), $value?->value);
            case self::TYPE_ACTION:
                assert(false, 'Cannot edit type "' . $this->type . '"');
        }
        return FormElement::input($name, 'text', $value);
    }

    public function export(mixed $data): string {
        if (null === $data) return '';

        switch ($this->type) {
            case self::TYPE_ENTITY:
                return (string) $data;
            case self::TYPE_COLLECTION:
                case self::TYPE_TAGS:
                if ($data instanceof Collection) $data = $data->toArray();
                return implode('; ', $data);
            case self::TYPE_CURRENCY:
                return sprintf($this->config['fmt'], $data);
            case self::TYPE_COLOR:
                return $data;
            case self::TYPE_DATETIME:
                assert($data instanceof DateTime);
                return $data->format($this->config['fmt'] ?? 'd.m.Y');
            case self::TYPE_ACTION:
                assert(false, 'Cannot export type "' . $this->type . '"');
        }
        return (string) $data;
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
