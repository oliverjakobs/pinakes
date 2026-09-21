<?php declare(strict_types=1);
require(dirname(__DIR__) . '/vendor/autoload.php');

use App\Database\Database;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__) . '/.env.dev');

Database::connect($_ENV['DB_URL']);
$db = Database::getInstance();

foreach (glob(dirname(__DIR__) . '/migrations/*.sql') as $filename) {
    $version = basename($filename, '.sql');

    echo "Executing migration $version" . PHP_EOL;

    $db->asTransaction(function () use ($db, $filename) {
        $sql = file_get_contents($filename);
        $db->exec($sql);
    });

    $executed_at = date('Y-m-d H:i:s');
    $db->exec("INSERT INTO migration (version, executed_at) VALUES ('$version', '$executed_at');");
}
