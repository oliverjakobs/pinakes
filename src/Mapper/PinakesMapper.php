<?php declare(strict_types=1);

namespace App\Mapper;

use App\Database\Database;
use App\Database\SQL\Delete;
use App\Database\SQL\Insert;
use App\Database\SQL\Query;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\PinakesEntity;
use App\Entity\Publisher;
use App\Entity\Series;
use App\Entity\Tag;
use App\Pinakes\Assert;
use App\Pinakes\DataField;
use App\Pinakes\DataTable2;
use App\Pinakes\Helper;
use Symfony\Component\VarDumper\Cloner\Data;

abstract class PinakesMapper {

    /** @var DataField[] */
    private array $data_fields;

    public function __construct() {
        $this->data_fields = [];
        foreach ($this->defineDataFields() as $name => $def) {
            $this->data_fields[$name] = new DataField($this, $name, ...$def);
        }
    }
    
    abstract public static function getTableName(): string;
    abstract public static function getSearchKey(): string;
    abstract public static function getEntityClass(): string;

    public static function getDefaultSelect(): array {
        return [ 'id', static::getSearchKey() ];
    }

    public static function getDefaultOrder(): array {
        return [ static::getTableName() . '.' . static::getSearchKey() => 'ASC' ];
    }

    abstract protected function defineDataFields(): array;

    public function createTable(string $fields): DataTable2 {
        $columns = $this->getDataFields($fields);
        return new DataTable2($this, $columns);
    }

    private function getJoinTable(DataField $field): string {
        Assert::isTrue($field->mapping->isManyToMany(), 'Join table only available for ManyToMany');

        $table_name = $this->getTableName();
        $target_name = $field->data_type->getTargetMapper()->getTableName();

        if (null !== $field->mapping->join_table) {
            return $field->mapping->join_table;
        }

        if ($field->mapping->isOwningSide()) {
            return "{$table_name}_{$target_name}";
        } 
        
        return "{$target_name}_{$table_name}";
    }

    private function getJoin(DataField $field): string {
        // ManyToMany column -> LEFT JOIN book_author ON book.id = book_author.book_id 
        //                      LEFT JOIN $on_table $field->name ON authors.id = book_author.author_id
        // ManyToOne column ->  LEFT JOIN publisher $field->name ON publisher.id = book.publisher_id

        $table_name = $this->getTableName();
        $target_name = $field->data_type->getTargetMapper()->getTableName();

        if ($field->mapping->isManyToMany()) {
            $join_table = $this->getJoinTable($field);
            return <<<SQL
                LEFT JOIN {$join_table} ON {$table_name}.id = {$join_table}.{$table_name}_id
                LEFT JOIN {$target_name} {$field->property} ON {$field->property}.id = {$join_table}.{$target_name}_id
            SQL;
        }

        return "LEFT JOIN {$target_name} {$field->property} ON {$field->property}.id = {$table_name}.{$target_name}_id";
    }

    public function createQuery(array $fields): Query {
        $result = new Query($this->getTableName());

        /** @var DataField $field */
        foreach ($fields as $field) {
            if (null === $field->mapping) continue;
            
            if ($field->mapping->isAssociative()) {
                $target_mapper = $field->data_type->getTargetMapper();
                foreach ($target_mapper->getDefaultSelect() as $select) {
                    $result->addSelect("{$field->property}.{$select}", "{$field->name}:{$select}");
                }

                $result->addJoin($this->getJoin($field));
            } else {
                $result->addSelect('.' . $field->property);
            }
        }

        return $result;
    }

    private function update(PinakesEntity $entity, array $data) {
        $update = new Update($this->getTableName(), $entity->getId());

        foreach ($data as $name => $value) {
            if (is_array($value)) $value = Helper::filterEmpty($value);
            if (Helper::isEmpty($value)) $value = null;
            
            $field = $this->getDataField($name);
            // not used for now
            if ($field->mapping->isOneToMany()) {
                Assert::error('Unused');   
            }

            if ($field->mapping->isManyToMany()) {
                $target_mapper = $field->data_type->getTargetMapper();
                $join_table = $this->getJoinTable($field);

                $delete = new Delete($join_table, "{$this->getTableName()}_id = :id");
                $delete->setParameter(':id', $entity->getId());
                Database::getInstance()->delete($delete);

                foreach ($value ?? [] as $v) {
                    $upsert = new Upsert($target_mapper->getTableName());
                    $upsert->add($target_mapper->getSearchKey(), $v);

                    $upsert_id = Database::getInstance()->upsert($upsert);

                    $insert = new Insert($join_table);
                    $insert->add($this->getTableName() . '_id', $entity->getId());
                    $insert->add($target_mapper->getTableName() . '_id', $upsert_id);

                    Database::getInstance()->insert($insert);
                }
                
                continue;
            }

            if ($field->mapping->isManyToOne()) {
                $target_mapper = $field->data_type->getTargetMapper();
                $upsert = new Upsert($target_mapper->getTableName());
                $upsert->add($target_mapper->getSearchKey(), $value);
                $upsert_id = Database::getInstance()->upsert($upsert);

                $update->add($field->property . '_id', $upsert_id);
                continue;
            }
            $update->add($field->property, $field->data_type->parse($value));
        }

        Database::getInstance()->update($update);
    }

    public function updateEntity(PinakesEntity $entity, array $data) {

        if (null !== $entity->getId()) {
            Database::getInstance()->asTransaction(fn() => $this->update($entity, $data));
        } else {
            var_dump('insert'); die();
        }
    }

    protected function composeDataFields(array $names): array {
        $result = [];
        foreach ($names as $name) {
            assert(array_key_exists($name, $this->data_fields), 'Unknown data field ' . $name);
            $result[$name] = $this->data_fields[$name];
        }
        return $result;
    }

    public function getDataField(string $name): ?DataField {
        return $this->data_fields[$name] ?? null;
    }

    public function getDataFields(string $fields): array {
        $func = 'getDataFields' . str_replace('_', '', ucwords($fields, '_'));
        assert(method_exists($this, $func), $func . ' missing for ' . $this::class);
        $result = $this->$func();
        return $result;
    }

    public static function getMapper(string $model): static {
        return match($model) {
            Book::class => new BookMapper(),
            Author::class => new AuthorMapper(),
            Publisher::class => new PublisherMapper(),
            Tag::class => new TagMapper(),
            Series::class => new SeriesMapper(),
        };
    }
}
