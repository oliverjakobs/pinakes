<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\PinakesEntity;
use Doctrine\ORM\Mapping\MappingAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class Database {

    private \PDO $pdo;

    public function __construct(string $dsn) {
        $this->pdo = new \PDO($dsn);
    }

    private function update_row(array $row, array $fetch): array {
        foreach ($fetch as $name => $value) {
            $row_value = $row[$name] ?? null;

            if (null === $row_value) {
                $row[$name] = $value;
                continue;
            }

            if (is_array($row_value)) {
                $id = $row[$name]['id'] ?? null;
                if (null !== $id) {
                    $row[$name] = [
                        $id => $row[$name],
                    ];
                }

                $id = $value['id'] ?? null;
                if (null !== $id) {
                    $row[$name][$id] = $value;
                }
            }
        }

        return $row;
    }

    private function process_fetch(array $fetch): array {
        $result = [];
        foreach ($fetch as $key => $value) {
            $exploded = explode(':', $key);
            if (count($exploded) <= 1) {
                $result[$key] = $value;
                continue;
            }
            
            $assoc = $result[$exploded[0]] ?? [];
            $assoc[$exploded[1]] = $value;
            $result[$exploded[0]] = $assoc;
        }
        return $result;
    } 
    
    public function query(string $sql): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $result = [];
        while ($fetch = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $fetch = $this->process_fetch($fetch);

            $id = $fetch['id'];
            $row = $result[$id] ?? [];            
            $result[$id] = $this->update_row($row, $fetch);
        }

        return $result;
    }

    public function get_select(string $name, array $fields): string {
        return implode(', ', array_map(fn($field) => $name . '.' . $field . ' as "' . $name . ':' . $field . '"', $fields));
    }

    public function hydrate(Book $entity, array $data): PinakesEntity {

        foreach ($data as $key => $value) {
            try {
                $property = new ReflectionProperty($entity, $key);
            } catch (ReflectionException $ex) {
                continue;
            }

            // not a mapped property
            $mapping = $property->getAttributes(MappingAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0];
            if (Helper::isEmpty($mapping)) continue;

			// echo $property->getName() . PHP_EOL;
            // $property->setValue($entity, $value);
        }


        $reflection = new ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $data['id']);

        $entity->title = $data['title'];

        $authors = $data['authors'];
        if (array_key_exists('id', $authors)) $authors = [ $authors ];

        foreach ($authors as $author_data) {
            $author = new Author();
            $reflection = new ReflectionProperty(Author::class, 'id');
            $reflection->setValue($author, $author_data['id']);

            $author->name = $author_data['name'];

            $entity->authors->add($author);
        }
        return $entity;
    }
}
