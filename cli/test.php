<?php declare(strict_types=1);
require(dirname(__DIR__).'/vendor/autoload.php');

use App\Entity\Author;
use App\Entity\Book;
use Symfony\Component\Dotenv\Dotenv;
use App\Pinakes\Database;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/.env');

$db = new Database($_ENV['DB_URL']);

function get_select(string $name, array $fields): string {
    return implode(', ', array_map(fn($field) => $name . '.' . $field . ' as "' . $name . ':' . $field . '"', $fields));
}

$authors_select = get_select('authors', [ 'id', 'name' ]);
$publisher_select = get_select('publisher', [ 'id', 'name' ]);
$tags_select = get_select('tags', [ 'id', 'name' ]);

$rows = $db->query(<<<SQL
    SELECT book.*, $authors_select, $publisher_select, $tags_select FROM book 
        LEFT JOIN book_author ON book.id = book_author.book_id LEFT JOIN author authors ON authors.id = book_author.author_id
        LEFT JOIN publisher ON book.publisher_id = publisher.id 
        LEFT JOIN book_tag ON book.id = book_tag.book_id LEFT JOIN tag tags ON tags.id = book_tag.tag_id
        ORDER BY book.created_at DESC 
        LIMIT 6;
SQL);


// $rows = $db->query(<<<'SQL'
//     SELECT b0_.id AS id_0, b0_.title AS title_1, b0_.published AS published_2, b0_.first_published AS first_published_3, b0_.isbn AS isbn_4, b0_.series_volume AS series_volume_5, b0_.created_at AS created_at_6, a1_.id AS id_7, a1_.name AS name_8, a1_.openlibrary AS openlibrary_9, p2_.id AS id_10, p2_.name AS name_11, s3_.id AS id_12, s3_.name AS name_13, t4_.id AS id_14, t4_.name AS name_15, t4_.color AS color_16, b0_.publisher_id AS publisher_id_17, b0_.series_id AS series_id_18 FROM book b0_ 
//     LEFT JOIN book_author b5_ ON b0_.id = b5_.book_id LEFT JOIN author a1_ ON a1_.id = b5_.author_id 
//     LEFT JOIN publisher p2_ ON b0_.publisher_id = p2_.id 
//     LEFT JOIN series s3_ ON b0_.series_id = s3_.id 
//     LEFT JOIN book_tag b6_ ON b0_.id = b6_.book_id LEFT JOIN tag t4_ ON t4_.id = b6_.tag_id
//         ORDER BY b0_.created_at DESC 
//         LIMIT 5;
// SQL);

$entities = [];
foreach ($rows as $row) {
    $book = new Book();
    $entities[] = $db->hydrate($book, $row);
    break;
}



var_dump($entities[0]);


/*
SELECT b0_.id AS id_0, b0_.title AS title_1, a1_.id AS id_7, a1_.name AS name_8 FROM book b0_
    LEFT JOIN book_author b5_ ON b0_.id = b5_.book_id LEFT JOIN author a1_ ON a1_.id = b5_.author_id 



SELECT b0_.id AS id_0, b0_.title AS title_1, b0_.published AS published_2, b0_.first_published AS first_published_3, b0_.isbn AS isbn_4, b0_.series_volume AS series_volume_5, b0_.created_at AS created_at_6, a1_.id AS id_7, a1_.name AS name_8, a1_.openlibrary AS openlibrary_9, p2_.id AS id_10, p2_.name AS name_11, s3_.id AS id_12, s3_.name AS name_13, t4_.id AS id_14, t4_.name AS name_15, t4_.color AS color_16, b0_.publisher_id AS publisher_id_17, b0_.series_id AS series_id_18 FROM book b0_ 
    LEFT JOIN book_author b5_ ON b0_.id = b5_.book_id LEFT JOIN author a1_ ON a1_.id = b5_.author_id 
    LEFT JOIN publisher p2_ ON b0_.publisher_id = p2_.id 
    LEFT JOIN series s3_ ON b0_.series_id = s3_.id 
    LEFT JOIN book_tag b6_ ON b0_.id = b6_.book_id LEFT JOIN tag t4_ ON t4_.id = b6_.tag_id

  
    
*/