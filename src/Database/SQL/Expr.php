<?php declare(strict_types=1);

namespace App\Database\SQL;


class Expr {

    // TODO replace 'self' with table_name
    public static function parse(string $expr, string $table_name): string {
        return $expr;
    }

    public static function json_object(string $table, array $fields): string {
        $content = implode(', ', array_map(fn($v) => "'{$v}', {$table}.{$v}", $fields));

        return "json_object({$content})";
    }

    public static function json_group(string $table, array $fields): string {
        $content = self::json_object($table, $fields);
        return "json_group_array({$content})";
    }
}