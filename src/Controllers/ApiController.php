<?php
namespace App\Controllers;
use App\Helpers\{Database,Validator,HttpError,Response,Logger};
use App\Middleware\{Auth,Csrf,Session};
use App\Repositories\{UserRepository,ExerciseRepository,WorkoutRepository};
use App\Services\{AuthService,ExerciseService,WorkoutService,ProfileService,SuggestionService,RestService};
final class ApiController {
    public function dispatch(): void {
        $method=$_SERVER['REQUEST_METHOD'];
        $resource=$_GET['resource']??'';
        if (!is_string($resource)) throw new HttpError(400,'Recurso inválido.');
        $routes=['csrf'=>['GET'],'login'=>['POST'],'cadastro'=>['POST'],'logout'=>['POST'],
            'perfil'=>['GET','PUT'],'sugestao'=>['GET'],'eu'=>['GET'],'resumo'=>['GET'],'treinos'=>['GET','POST','PUT','DELETE'],
            'exercicios'=>['GET','POST','PUT','DELETE'],'usuarios'=>['GET'],'estatisticas'=>['GET']];
        if (!isset($routes[$resource])) throw new HttpError(404,'Endpoint não encontrado.');
        if (!in_array($method,$routes[$resource],true)) {
            header('Allow: '.implode(', ',$routes[$resource]));throw new HttpError(405,'Método não permitido.');
        }
        if ($resource==='csrf') Response::json(['token'=>Csrf::token()]);
        if ($method!=='GET') Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN']??null);
        $body=[];
        if (in_array($method,['POST','PUT'],true)) {
            if (strpos(strtolower($_SERVER['CONTENT_TYPE']??''),'application/json')!==0) throw new HttpError(415,'Envie Content-Type: application/json.');
            $raw=file_get_contents('php://input',false,null,0,16385);
            if (strlen($raw)>16384) throw new HttpError(413,'Requisição muito grande.');
            $obj=json_decode($raw);
            if (!is_object($obj) || json_last_error()!==JSON_ERROR_NONE) throw new HttpError(400,'Corpo JSON inválido; envie um objeto.');
            $body=(array)$obj;
        }
        // Rejeita anônimo antes de abrir a conexão de banco.
        if (!in_array($resource,['login','cadastro'],true) && empty($_SESSION['user_id'])) throw new HttpError(401,'Faça login para continuar.');
        $db=Database::connect();$users=new UserRepository($db);
        if ($resource==='login') {
            try {$u=(new AuthService($users))->login($body);}
            catch (HttpError $e) {Logger::write('login_falha',['status'=>$e->status]);throw $e;}
            Session::login((int)$u['id']);Logger::write('login_sucesso',['usuario_id'=>$u['id']]);
            Response::json(['usuario'=>$u,'csrf'=>Csrf::token()],'Login realizado.');
        }
        if ($resource==='cadastro') {
            $id=(new AuthService($users))->register($body);
            Response::json(['id'=>$id],'Conta criada. Faça login.',201);
        }
        $user=Auth::user($users);$uid=(int)$user['id'];
        if ($resource==='logout') {Logger::write('logout',['usuario_id'=>$uid]);Session::logout();Response::json(null,'Sessão encerrada.');}
        if ($resource==='eu') Response::json($user);
        $admin=in_array($resource,['usuarios','estatisticas'],true) || ($resource==='exercicios' && $method!=='GET');
        if ($admin) Auth::admin($user);
        $id=isset($_GET['id']) ? Validator::integer($_GET['id'],'id') : null;
        $page=isset($_GET['page']) ? Validator::integer($_GET['page'],'page',1,100000) : 1;
        $workouts=new WorkoutRepository($db);$exercises=new ExerciseRepository($db);
        if ($resource==='usuarios') Response::json($users->listing($page));
        if ($resource==='estatisticas') Response::json($workouts->totals());
        if ($resource==='resumo') Response::json(RestService::summary($workouts->summary($uid)));
        if ($resource==='sugestao') {
            $profile=$users->profile($uid);
            $params=$_GET+[
                'nivel'=>$profile['nivel']??'iniciante',
                'objetivo'=>$profile['objetivo']??'geral',
            ];
            Response::json((new SuggestionService($exercises))->generate($params));
        }
        if ($resource==='perfil') {
            if ($method==='GET') Response::json($users->profile($uid));
            Response::json((new ProfileService($users))->save($uid,$body),'Preferências salvas.');
        }
        if ($resource==='treinos') {
            $service=new WorkoutService($workouts,$exercises);
            if ($method==='GET') Response::json($id===null ? $workouts->listing($uid,$page) : $service->owned($id,$uid));
            if ($method==='POST') {if ($id!==null) throw new HttpError(400,'POST não recebe id.');$new=$service->save($uid,$body);Response::json(['id'=>$new],'Treino registrado.',201);}
            if ($id===null) throw new HttpError(400,'Informe o id do treino.');
            if ($method==='PUT') {$service->save($uid,$body,$id);Response::json(['id'=>$id],'Treino atualizado.');}
            $service->delete($id,$uid);Response::json(null,'Treino excluído.');
        }
        $service=new ExerciseService($exercises);
        if ($method==='GET') Response::json($id===null ? $exercises->all() : $service->get($id));
        if ($method==='POST') {if ($id!==null) throw new HttpError(400,'POST não recebe id.');Response::json(['id'=>$service->save($body)],'Exercício criado.',201);}
        if ($id===null) throw new HttpError(400,'Informe o id do exercício.');
        if ($method==='PUT') {Response::json(['id'=>$service->save($body,$id)],'Exercício atualizado.');}
        $service->delete($id);Response::json(null,'Exercício excluído.');
    }
}
