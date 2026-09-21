<?php declare(strict_types=1);
require(dirname(__DIR__).'/vendor/autoload.php');

use App\Database\Database;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__) . '/.env.dev');

Database::connect($_ENV['DB_URL']);
$db = Database::getInstance();

$db->exec(<<<SQL
    CREATE TABLE user (
      id        INTEGER PRIMARY KEY AUTOINCREMENT,
      username  TEXT NOT NULL,
      roles     TEXT NOT NULL,
      password  TEXT NOT NULL
    );
SQL);

$db->exec('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username);');

$db->exec(<<<SQL
    CREATE TABLE migration (
      version     TEXT NOT NULL,
      executed_at DATETIME DEFAULT NULLL,
      PRIMARY KEY(version)
    );
SQL);

$user = new User();
$user->username = 'admin';