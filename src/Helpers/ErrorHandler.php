<?php
namespace App\Helpers;
final class ErrorHandler {
    public static function handle(\Throwable $e): void {
        $status=$e instanceof HttpError ? $e->status : 500;
        $message=$e instanceof HttpError ? $e->getMessage() : 'Erro interno. Tente novamente. Se persistir, consulte o responsável pelo sistema.';
        $errors=$e instanceof HttpError ? $e->errors : [];
        if ($status===500) {
            Logger::write('erro_interno',['tipo'=>get_class($e),'codigo'=>(string)$e->getCode(),'arquivo'=>basename($e->getFile()),'linha'=>$e->getLine()]);
            if (Config::get('APP_ENV')==='development') $errors['debug']=$e->getMessage();
        }
        if (defined('API_REQUEST')) Response::json(null,$message,$status,$errors);
        http_response_code($status);
        if (PHP_SAPI==='cli') {fwrite(STDERR,$message.PHP_EOL); exit(1);}
        echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Meu Treino</title><h1>'.Response::escape($message).'</h1><p><a href="index.php">Voltar</a></p></html>';
    }
}
