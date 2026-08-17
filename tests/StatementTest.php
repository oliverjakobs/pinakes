<?php

namespace App\Tests;

use App\Database\SQL\Delete;
use App\Database\SQL\Insert;
use App\Database\SQL\Statement;
use App\Database\SQL\Update;
use App\Database\SQL\Upsert;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase {

    private function assertStatement(Statement $statement, string $sql, array $parameters) {
        $this->assertEquals($sql, $statement->build());
        $this->assertEquals($parameters, $statement->getParameters());
    }

    public function testDelete(): void {
        $delete = new Delete('book', 'id = :id');
        $delete->setParameter(':id', 23);
        
        $sql = <<<SQL
            DELETE FROM book
            WHERE id = :id
        SQL;

        $this->assertStatement($delete, $sql, [
            ':id' => 23
        ]);
    }

    public function testInsert(): void {
        $insert = new Insert('book');
        $insert->add('title', 'Fugitive Telemetry');
        $insert->add('published', 2021);
        
        $sql = <<<SQL
            INSERT INTO book
                (title, published)
            VALUES
                (:title, :published)
            RETURNING id
        SQL;

        $this->assertStatement($insert, $sql, [
            ':title' => 'Fugitive Telemetry',
            ':published' => 2021,
        ]);
    }

    // public function testQuery(): void {
    //     $insert = new Insert('book');
    //     $insert->add('title', 'Fugitive Telemetry');
    //     $insert->add('published', 2021);
        
    //     $sql = <<<SQL
    //         INSERT INTO contact
    //             (title, published)
    //         VALUES
    //             (:title, :published)
    //         RETURNING id;
    //     SQL;

    //     $this->assertStatement($insert, $sql, [
    //         ':title' => 'Fugitive Telemetry',
    //         ':published' => 2021,
    //     ]);
    // }

    public function testUpdate(): void {
        $update = new Update('book', 23);
        $update->add('title', 'Fugitive Telemetry');
        $update->add('published', 2021);
        
        $sql = <<<SQL
            UPDATE book
            SET
                title = :title, published = :published
            WHERE
                id = :id
        SQL;

        $this->assertStatement($update, $sql, [
            ':id' => 23,
            ':title' => 'Fugitive Telemetry',
            ':published' => 2021,
        ]);
    }
    
    public function testUpsert(): void {
        $upsert = new Upsert('author', 'name', 'Martha Wells');
        
        $sql = <<<SQL
            INSERT INTO author
                (name)
            VALUES
                (:value)
            ON CONFLICT (name) DO UPDATE
            SET
                name = excluded.name
            RETURNING id
        SQL;

        $this->assertStatement($upsert, $sql, [
            ':value' => 'Martha Wells'
        ]);
    }
}