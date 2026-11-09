<?php
namespace App\Services;
use App\Repositories\UserRepository;
use App\Helpers\{Validator,HttpError};
final class AuthService {
    private UserRepository $users;
    public function __construct(UserRepository $users) {$this->users=$users;}
    public function login(array $data): array {
        $email=Validator::email($data);$password=Validator::password($data);
        $user=$this->users->byEmail($email);
        if (!$user || !password_verify($password,$user['senha_hash'])) throw new HttpError(401,'E-mail ou senha incorretos.');
        unset($user['senha_hash']);return $user;
    }
    public function register(array $data): int {
        $name=Validator::text($data,'nome',2,100);$email=Validator::email($data);$password=Validator::password($data);
        if ($this->users->byEmail($email)) throw new HttpError(409,'Este e-mail já está cadastrado.');
        try {return $this->users->create($name,$email,password_hash($password,PASSWORD_DEFAULT));}
        catch (\PDOException $e) {if (($e->errorInfo[1]??null)===1062) throw new HttpError(409,'Este e-mail já está cadastrado.');throw $e;}
    }
}
