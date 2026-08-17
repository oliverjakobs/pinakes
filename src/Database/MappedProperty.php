<?php declare(strict_types=1);

namespace App\Database;

use App\Database\Mapping\MappingAttribute;
use App\Database\Mapping\ManyToMany;
use App\Database\Mapping\ManyToOne;
use App\Database\Mapping\OneToMany;
use App\Mapper\PinakesMapper;
use App\Pinakes\Assert;
use ArrayAccess;
use ReflectionAttribute;
use ReflectionProperty;

class MappedProperty {

    public readonly string $entity;
    public readonly string $name;
    public readonly string $type;
    public readonly string $mapping_type;

    public readonly bool $is_owning;
    public readonly ?string $target;
    public readonly ?string $join_table;

    public readonly ?self $target_property;
    
    public function __construct(string $entity, string $property, string $type, string $mapping_type, array $options) {
        $this->entity = $entity;
        $this->name = $property;
        $this->type = $type;
        $this->mapping_type = $mapping_type;

        $this->is_owning = array_key_exists('inversed_by', $options);
        $this->target = $options['target'] ?? null;

        $this->join_table = $options['join_table'] ?? null;

        $target_property = $options['property'] ?? null;
        if (null !== $target_property) {
            $this->target_property = self::create($entity, $target_property);
        } else {
            $this->target_property = null;
        }
    }

    public static function create(object|string $entity, string $property): ?self {
        return self::fromReflection(new ReflectionProperty($entity, $property));
    }

    public static function fromReflection(ReflectionProperty $property): ?self {
        $mapping = array_first($property->getAttributes(MappingAttribute::class, ReflectionAttribute::IS_INSTANCEOF));
        if (null === $mapping) return null;

        return new self(
            $property->getDeclaringClass()->getName(),
            $property->getName(),
            $property->getType()->getName(),
            $mapping->getName(),
            $mapping->getArguments()
        );
    }

    public function isAssociative(): bool {
        return null !== $this->target;
    }

    public function isManyToMany(): bool {
        return ManyToMany::class === $this->mapping_type;
    }

    public function isManyToOne(): bool {
        return ManyToOne::class === $this->mapping_type;
    }

    public function isOneToMany(): bool {
        return OneToMany::class === $this->mapping_type;
    }

    public function isArrayType(): bool {
        return 'array' === $this->type || is_a($this->type, ArrayAccess::class, true);
    }

    public function isVirtual(): bool {
        return null !== $this->target_property;
    }

    public function getJoinTable(): string {
        Assert::isTrue($this->isManyToMany(), 'Join Tables only for ManyToMany');
        
        if (null !== $this->join_table) return $this->join_table;

        $mapper = PinakesMapper::getMapper($this->entity);
        $target_mapper = PinakesMapper::getMapper($this->target);

        if ($this->is_owning) {
            return "{$mapper->getTableName()}_{$target_mapper->getTableName()}";
        }
        return "{$target_mapper->getTableName()}_{$mapper->getTableName()}";
    }
}
