<?php declare(strict_types=1);

namespace App\Pinakes;

use Symfony\Component\HttpClient\HttpClient;

class OpenLibrary {

    const FIELDS = [
        'key',
        'title',
        'author_name',
        'author_key',
        'first_publish_year',
        'editions',
        'editions.publisher',
        'editions.publish_year'
    ];

    public static function findByIsbn(string $isbn): array {
        $client = HttpClient::create();

        // https://openlibrary.org/search.json?isbn=0-394-58816-9&fields=key,title,author_name,author_key,first_publish_year,editions,editions.publisher,editions.publish_year
        $url = 'https://openlibrary.org/search.json?' . http_build_query([
            'q' => 'isbn:' . $isbn,
            'fields' => implode(',', self::FIELDS)
        ]);
        $response = $client->request('GET', $url);
        return $response->toArray();
    }

}
