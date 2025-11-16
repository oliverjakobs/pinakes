<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase {

    public function testList(): void {
        $client = static::createClient();
        $crawler = $client->request('GET', '/book');

        $this->assertResponseIsSuccessful();
    }

    public function testListTag(): void {
        $client = static::createClient();
        $crawler = $client->request('GET', '/book/tag/1');

        $this->assertResponseIsSuccessful();
    }

    public function testEmptyList(): void {
        $client = static::createClient();
        $crawler = $client->request('GET', '/book', [
            'search' => 'No Book with this title exists'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(1, 'td');
        $this->assertSelectorTextContains('td', 'Nothing found!');
    }
}
