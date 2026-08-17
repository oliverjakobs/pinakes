<?php

namespace App\Tests;

use App\Database\MappedProperty;
use App\Database\Mapping\Column;
use App\Database\Mapping\Count;
use App\Database\Mapping\ManyToMany;
use App\Database\Mapping\ManyToOne;
use App\Database\Mapping\OneToMany;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Publisher;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class MappedPropertyTest extends TestCase {

    public function testColumn(): void {
        $mapping = MappedProperty::create(Book::class, 'title');

        $this->assertEquals(Book::class, $mapping->entity);
        $this->assertEquals('title', $mapping->name);
        $this->assertEquals('string', $mapping->type);
        $this->assertEquals(Column::class, $mapping->mapping_type);

        $this->assertFalse($mapping->is_owning);
        $this->assertNull($mapping->target);
        $this->assertNull($mapping->join_table);
        $this->assertNull($mapping->target_property);
    }

    public function testManyToMany(): void {
        $mapping = MappedProperty::create(Book::class, 'authors');

        $this->assertEquals(Book::class, $mapping->entity);
        $this->assertEquals('authors', $mapping->name);
        $this->assertEquals(Collection::class, $mapping->type);
        $this->assertEquals(ManyToMany::class, $mapping->mapping_type);

        $this->assertTrue($mapping->is_owning);
        $this->assertEquals(Author::class, $mapping->target);
        $this->assertNull($mapping->join_table);

        $this->assertNull($mapping->target_property);
    }

    public function testManyToOne(): void {
        $mapping = MappedProperty::create(Book::class, 'publisher');

        $this->assertEquals(Book::class, $mapping->entity);
        $this->assertEquals('publisher', $mapping->name);
        $this->assertEquals(Publisher::class, $mapping->type);
        $this->assertEquals(ManyToOne::class, $mapping->mapping_type);

        $this->assertTrue($mapping->is_owning);
        $this->assertEquals(Publisher::class, $mapping->target);
        $this->assertNull($mapping->join_table);

        $this->assertNull($mapping->target_property);
    }

    public function testOneToMany(): void {
        $mapping = MappedProperty::create(Publisher::class, 'books');

        $this->assertEquals(Publisher::class, $mapping->entity);
        $this->assertEquals('books', $mapping->name);
        $this->assertEquals(Collection::class, $mapping->type);
        $this->assertEquals(OneToMany::class, $mapping->mapping_type);

        $this->assertFalse($mapping->is_owning);
        $this->assertEquals(Book::class, $mapping->target);
        $this->assertNull($mapping->join_table);

        $this->assertNull($mapping->target_property);
    }

    public function testCount(): void {
        $mapping = MappedProperty::create(Author::class, 'book_count');

        $this->assertEquals(Author::class, $mapping->entity);
        $this->assertEquals('book_count', $mapping->name);
        $this->assertEquals('int', $mapping->type);
        $this->assertEquals(Count::class, $mapping->mapping_type);

        $this->assertFalse($mapping->is_owning);
        $this->assertNull($mapping->target);
        $this->assertNull($mapping->join_table);

        $this->assertNotNull($mapping->target_property);
        $this->assertEquals('books', $mapping->target_property->name);
    }
}