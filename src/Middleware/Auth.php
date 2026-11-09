<?php
namespace App\Middleware;
use App\Helpers\{HttpError,Logger};
use App\Repositories\UserRepository;
final class Auth {
    public static function user(UserRepository $repo): array {
        $id=$_SESSION['user_id'] ?? null;
        $user=$id ? $repo->find((int)$id) : null;
        if (!$user) throw new HttpError(401,'Faça login para continuar.');
        return $user;
    }
    public static function admin(array $user): void {
        if ($user['perfil']!=='admin') {
            Logger::write('acesso_admin_negado',['usuario_id'=>$user['id']]);
            throw new HttpError(403,'Acesso permitido apenas ao administrador.');
        }
    }
}
