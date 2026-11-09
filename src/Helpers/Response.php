<?php
namespace App\Helpers;
final class Response {
    public static function json($data=null, string $message='Operação concluída.', int $status=200, array $errors=[]): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>$status<400,'message'=>$message,'data'=>$data,'errors'=>(object)$errors], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    public static function escape($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
