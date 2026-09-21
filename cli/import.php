<?php declare(strict_types=1);
require(dirname(__DIR__).'/vendor/autoload.php');

use App\Database\Database;
use App\Database\SQL\Insert;
use App\Pinakes\Csv;
use App\Pinakes\Helper;
use Symfony\Component\Dotenv\Dotenv;

const CSV_SEPARATOR = ';';
const BASE_PATH = '/home/oliver/Projects/pinakes/var/import/';

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__) . '/.env.dev');

Database::connect($_ENV['DB_URL']);
$db = Database::getInstance();

// $db->asTransaction(fn() => import_csv($db, 'user.csv', 'user'));

// $db->asTransaction(fn() => import_csv($db, 'author.csv', 'author'));
// $db->asTransaction(fn() => import_csv($db, 'publisher.csv', 'publisher'));
// $db->asTransaction(fn() => import_csv($db, 'series.csv', 'series'));
// $db->asTransaction(fn() => import_csv($db, 'tag.csv', 'tag'));
// $db->asTransaction(fn() => import_csv($db, 'book.csv', 'book'));
// $db->asTransaction(fn() => import_csv($db, 'book_author.csv', 'book_author'));
// $db->asTransaction(fn() => import_csv($db, 'book_translator.csv', 'book_translator'));
// $db->asTransaction(fn() => import_csv($db, 'book_tag.csv', 'book_tag'));

$db->asTransaction(fn() => import_csv($db, 'transaction.csv', 'transaction'));

$db->asTransaction(fn() => import_csv($db, 'boardgame_publisher.csv', 'boardgame_publisher'));
$db->asTransaction(fn() => import_csv($db, 'boardgame.csv', 'boardgame'));

$db->asTransaction(fn() => import_csv($db, 'artist.csv', 'artist'));
$db->asTransaction(fn() => import_csv($db, 'record_label.csv', 'record_label'));
$db->asTransaction(fn() => import_csv($db, 'record.csv', 'record'));

function import_csv(Database $db, string $filename, string $table_name) {
    $rows = Csv::read_file(BASE_PATH . $filename, CSV_SEPARATOR);
    foreach ($rows as $row) {
        $insert = new Insert($table_name, false);
        foreach ($row as $name => $value) {
            if (Helper::isEmpty($value)) continue;
            $insert->add($name, $value);
        }
        $db->execute($insert);
    }
}
