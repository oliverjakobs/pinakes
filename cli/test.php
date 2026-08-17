<?php declare(strict_types=1);
require(dirname(__DIR__).'/vendor/autoload.php');

use App\Entity\Book;
use Symfony\Component\Dotenv\Dotenv;
use App\Database\Database;
use App\Database\SQL\Query;
use App\Database\SQL\Update;
use App\Entity\Author;
use App\Mapper\AuthorMapper;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/.env.dev');

Database::connect($_ENV['DB_URL']);
$db = Database::getInstance();

$update = new Update('author', 23);
$update->add('name', 'George Orwell');

var_dump($update->build());


exit;

$sql = <<<SQL
SELECT
    book.id,
    book.title,
    authors.authors as authors,
    json_object( 'id', publisher.id, 'name', publisher.name ) AS "publisher",
    book.published,
    book.first_published,
    book.isbn,
    tags.tags as tags,
    json_object( 'id', series.id, 'name', series.name ) AS "series",
    book.series_volume
FROM (
    SELECT * 
    FROM book 
    ORDER BY book.created_at DESC, book.title ASC
    LIMIT 5 OFFSET 0
) AS book
LEFT JOIN (
    SELECT
        book_author.book_id,
        json_group_array( json_object( 'id', author.id, 'name', author.name )) AS "authors"
    FROM book_author
    JOIN author ON author.id = book_author.author_id
    GROUP BY book_author.book_id
) AS authors ON authors.book_id = book.id 
LEFT JOIN publisher publisher ON publisher.id = book.publisher_id 
LEFT JOIN (
    SELECT
        book_tag.book_id,
        json_group_array( json_object( 'id', tag.id, 'name', tag.name, 'color', tag.color )) AS "tags"
    FROM book_tag
    JOIN tag ON tag.id = book_tag.tag_id
    GROUP BY book_tag.book_id
) AS tags ON tags.book_id = book.id
LEFT JOIN series series ON series.id = book.series_id
ORDER BY book.created_at DESC, book.title ASC
SQL;

$sql = <<<SQL
SELECT
    book.id,
    book.title,
    json_object('id', series.id, 'name', series.name ) AS series,
    book.series_volume
FROM (
    SELECT * 
    FROM book 
    ORDER BY book.created_at DESC, book.title ASC
    LIMIT 5 OFFSET 0
) AS book
LEFT JOIN series series ON series.id = book.series_id
ORDER BY book.created_at DESC, book.title ASC
SQL;

$stmt = $db->execute($sql);
// $rows = $db->fetch($stmt);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// $book = Database::hydrate(new Book(), $rows[0]);
var_dump($rows[0]);

exit;


$sql = <<<SQL
SELECT
    author.id,
    author.name,
    author.book_count
FROM (SELECT *, (SELECT COUNT(*) FROM book_author WHERE author_id = author.id) AS book_count FROM author ORDER BY book_count asc LIMIT 30 OFFSET 0) AS author
ORDER BY book_count asc;
SQL;

$stmt = $db->execute($sql);
// $rows = $db->fetch($stmt);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

var_dump($rows);
exit;

$target = new AuthorMapper();

$table_name = 'book';
$target_name = $target->getTableName();

$property = 'authors';
$join_table = 'book_author';

$subquery = new Query($join_table);
$subquery->addSelect(".{$table_name}_id");
foreach ($target->getDefaultSelect() as $select) {
    $subquery->addSelect("GROUP_CONCAT({$target_name}.{$select}, ';') AS {$property}_{$select}");
}
$subquery->addJoin("JOIN {$target_name} ON {$target_name}.id = {$join_table}.{$target_name}_id");
$subquery->addGroupBy(".{$table_name}_id");

var_dump($subquery->build());

exit;


$sql = <<<SQL
SELECT
    author.id,
    author.name,
    COUNT(book_author.id) AS book_count
FROM author
LEFT JOIN book_author ON book_author.author_id = author.id
GROUP BY author.id
ORDER BY author.name
LIMIT 5 OFFSET 0;
SQL;

/*
SELECT a.id, a.name
FROM authors a
WHERE EXISTS (
    SELECT 1
    FROM book_authors ba
    JOIN books b ON b.id = ba.book_id
    WHERE ba.author_id = a.id
      AND b.series_id = :series_id
);

SELECT json_group_array(
    json_object(
        'id', author.id,
        'name', author.name
    )
)
FROM (
    SELECT DISTINCT a.id, a.name
    FROM book b
    JOIN book_authors ba ON ba.book_id = b.id
    JOIN authors a ON a.id = ba.author_id
    WHERE b.series_id = s.id
    ORDER BY a.name
) AS author

SELECT
    book.id,
    book.title,
    GROUP_CONCAT(authors.id, ';') AS "authors:id",
    GROUP_CONCAT(authors.name, ';') AS "authors:name"
FROM book
LEFT JOIN book_author ON book.id = book_author.book_id
LEFT JOIN author authors ON authors.id = book_author.author_id
GROUP BY book.id, book.title
ORDER BY book.created_at DESC, book.title ASC
LIMIT 30 OFFSET 0;

*/

$from_query = "SELECT * FROM book ORDER BY created_at DESC, title ASC LIMIT 30 OFFSET 0";

$author_join_table = <<<SQL
SELECT
    book_author.book_id,
    GROUP_CONCAT(author.id, ';')   AS authors_id,
    GROUP_CONCAT(author.name, ';') AS authors_name
FROM book_author
JOIN author ON author.id = book_author.author_id
GROUP BY book_author.book_id
SQL;

$tags_join_table = <<<SQL
SELECT
    book_tag.book_id,
    GROUP_CONCAT(tag.id, ';')    AS tags_id,
    GROUP_CONCAT(tag.name, ';')  AS tags_name,
    GROUP_CONCAT(tag.color, ';') AS tags_color
FROM book_tag
JOIN tag ON tag.id = book_tag.tag_id
GROUP BY book_tag.book_id
SQL;

$sql2 = <<<SQL
SELECT
    _book.id,
    _book.title,
    authors.authors_id     AS "authors:id",
    authors.authors_name   AS "authors:name",

    publisher.id            AS "publisher:id",
    publisher.name          AS "publisher:name",

    tags.tags_id           AS "tags:id",
    tags.tags_name         AS "tags:name",
    tags.tags_color        AS "tags:color",

    _book.published,
    _book.first_published,
    _book.isbn,

    series.id                   AS "series:id",
    series.name                 AS "series:name",

    _book.series_volume

FROM ($from_query) AS _book
LEFT JOIN publisher ON publisher.id = _book.publisher_id
LEFT JOIN series ON series.id = _book.series_id

LEFT JOIN ($author_join_table) AS authors ON authors.book_id = _book.id

LEFT JOIN ($tags_join_table) AS tags ON tags.book_id = b.id

ORDER BY _book.created_at DESC, _book.title ASC;
SQL;


$stmt = $db->execute($author_join_table);
// $rows = $db->fetch($stmt);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

var_dump($rows);
exit;

$entities = [];
foreach ($rows as $row) {
    $entity = Database::hydrate(new Book(), $row);
    $entities[] = $entity;
}

// var_dump(Database::hydrate(new Book(), $rows[614]));
exit;

$sql = <<<SQL
CREATE UNIQUE INDEX idx_tag_name 
    ON tag (name);
SQL;

$db->execute($sql);


exit;

/*

authors.id AS 

$col->getSelect()
 - normal column -> $col->property AS $col->name
 - target column -> authors.id AS "authors:id", authors.name AS "authors:name"


$col->getJoin()
 - normal column -> ''
 - ManyToMany column -> LEFT JOIN book_author ON book.id = book_author.book_id LEFT JOIN author $col->name ON authors.id = book_author.author_id
 - ManyToOne column -> LEFT JOIN publisher ON book.publisher_id = publisher.id 

*/

// $rows = $db->query(<<<SQL
// SELECT book.id, book.title, authors.id AS "authors:id", authors.name AS "authors:name", series.id AS "series:id", series.name AS "series:name" FROM book 
//     LEFT JOIN book_author ON book.id = book_author.book_id LEFT JOIN author authors ON authors.id = book_author.author_id 
//     LEFT JOIN series series ON series.id = book.series_id 
//     ORDER BY book.created_at DESC 
//     LIMIT 10 OFFSET 5;
// SQL);

$rows = $db->fetch(<<<SQL
SELECT book.id, book.title, book.isbn, authors.id AS "authors:id", authors.name AS "authors:name", series.id AS "series:id", series.name AS "series:name" FROM book 
    LEFT JOIN book_author ON book.id = book_author.book_id LEFT JOIN author authors ON authors.id = book_author.author_id 
    LEFT JOIN series series ON series.id = book.series_id 
    WHERE book.id = 633;
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

var_dump($rows);

exit;

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