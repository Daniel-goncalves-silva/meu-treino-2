<?php
namespace App\Helpers;
final class Logger {
    public static function write(string $event, array $context=[]): void {
        // Nunca registrar senha, cookie, token CSRF ou corpo completo de requisição.
        $line=json_encode(['timestamp'=>date(DATE_ATOM),'event'=>$event,'context'=>$context], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
        if (file_put_contents(APP_ROOT.'/logs/app.log', $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('Meu Treino: não foi possível gravar o log de aplicação.');
        }
    }
}
