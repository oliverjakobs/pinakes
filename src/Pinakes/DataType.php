<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Renderable\Renderable;
use App\Renderable\FormElement;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DataType {

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';

    const TYPE_DATETIME = 'datetime';
    const TYPE_COLOR = 'color';
    const TYPE_CURRENCY = 'currency';

    const TYPE_ENTITY = 'entity';
    const TYPE_COLLECTION = 'collection';

    // TODO maybe private?
    public function __construct(
        private string $type,
        private array $config = []
    ) {
    }

    public static function currency(string $currency = '€'): self {
        return new self(self::TYPE_CURRENCY, [
            'fmt' => '%.2f ' . $currency,
        ]);
    }

    public static function color(): self {
        return new self(self::TYPE_COLOR);
    }

    public static function datetime(string $fmt): self {
        return new self(self::TYPE_DATETIME, [
            'fmt' => $fmt
        ]);
    }

    private static function isValidTarget(string $target) {
        return str_starts_with($target, 'App\\Entity\\');
    }

    public static function entity(string $entity): self {
        assert(self::isValidTarget($entity), 'Invalid target');
        return new self(self::TYPE_ENTITY, [ 'target' => $entity ]);
    }

    public static function collection(string $entity, ?string $separator = null): self {
        assert(self::isValidTarget($entity), 'Invalid target');
        return new self(self::TYPE_COLLECTION, [
            'target' => $entity,
            'separator' => $separator,
        ]);
    }

    public function __toString() {
        return $this->type . match($this->type) {
            self::TYPE_ENTITY, self::TYPE_COLLECTION => '(' . $this->config['target'] . ')',
            default => ''
        };
    }

    public function parse(string|array $value): mixed {
        switch ($this->type) {
            case self::TYPE_ENTITY:
                if (empty($value)) return null;
                assert(is_string($value), 'Expected string got array');
                $repository = Pinakes::getRepository($this->config['target']);
                return $repository->getOrCreate($value, false);

            case self::TYPE_COLLECTION:
                assert(is_array($value), 'Expected array got string');
                $repository = Pinakes::getRepository($this->config['target']);
                $entities = array_map(fn ($e) => $repository->getOrCreate($e, false), $value);
                return new ArrayCollection($entities);

            case self::TYPE_DATETIME:
                return new DateTime($value);
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
                if (0 === strlen($value)) return null;
                return floatval($value);
            case self::TYPE_INTEGER:
                if (0 === strlen($value)) return null;
                return intval($value);
        }

        return $value;
    }

    private function renderCollection(mixed $data): string {
        
        if ($data instanceof Collection) $data = $data->toArray();

        $separator = $this->config['separator'] ?? null;
        if (null !== $separator) {
            return implode($separator, $data);
        }

        $data = implode(PHP_EOL, array_map(fn ($e) => '<li>' . $e . '</li>', $data));
        return <<<HTML
        <ul class="collection">
        $data
        </ul>
        HTML;
    }

    public function render(mixed $data): string {
        switch ($this->type) {
            case self::TYPE_ENTITY:
                return (string) $data;
            case self::TYPE_COLLECTION:
                return $this->renderCollection($data);
            case self::TYPE_CURRENCY:
                return sprintf($this->config['fmt'], $data);
            case self::TYPE_DATETIME:
                assert($data instanceof DateTime);
                return $data->format($this->config['fmt'] ?? 'd.m.Y');
        }
        return (string) $data;
    }

    public function getForm(string $name, mixed $value): Renderable {
        switch ($this->type) {
            case self::TYPE_ENTITY:
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
        }
        return FormElement::input($name, 'text', $value);
    }
}
