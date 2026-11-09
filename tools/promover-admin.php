<?php
// Apenas CLI: o administrador escolhe uma conta existente, sem senha fixa.
if (PHP_SAPI !== 'cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/config/bootstrap.php';
use App\Helpers\{Database,Validator};
use App\Repositories\UserRepository;
$email=Validator::email(['email'=>$argv[1]??'']);
$repo=new UserRepository(Database::connect());
$user=$repo->byEmail($email);
if (!$user) {fwrite(STDERR,"Conta inexistente. Cadastre-se pela interface primeiro.\n");exit(1);}
$repo->promote($email);
echo "Perfil admin atribuído à conta informada. Faça login novamente.\n";
