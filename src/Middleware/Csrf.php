<?php
namespace App\Middleware;
use App\Helpers\HttpError;
final class Csrf {
    public static function token(): string {
        if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
    public static function verify($token): void {
        if (!is_string($token) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$token))
            throw new HttpError(403,'Token de segurança inválido. Atualize a página e tente novamente.');
    }
}
