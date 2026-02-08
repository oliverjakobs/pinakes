<?php

namespace App\Tests;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DataTypeTest extends KernelTestCase {

    public function testDataType(): void {
        $repository = BookRepository::getInstance();
        $this->assertNotEmpty($repository->getDataFields('show'));
    }
}