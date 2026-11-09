<?php
namespace App\Helpers;
final class Config {
    private static array $values = [];
    public static function load(string $file): void {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$key,$value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim(trim($value), "\"'");
        }
    }
    public static function get(string $key, string $default = ''): string {
        $external = getenv($key);
        return $external !== false ? $external : (self::$values[$key] ?? $default);
    }
}
