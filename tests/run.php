<?php
// Testes de regras de negócio com repositórios em memória: não alteram MySQL.
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/config/bootstrap.php';
use App\Repositories\{UserRepository,WorkoutRepository,ExerciseRepository};
use App\Services\{AuthService,WorkoutService,SuggestionService,RestService,ProfileService};
use App\Helpers\{Validator,HttpError,Response};
use App\Middleware\{Auth,Csrf};
class MemoryUsers extends UserRepository {
    public array $rows=[];
    public function __construct() {}
    public function byEmail(string $email): ?array {foreach($this->rows as $u) if($u['email']===$email)return $u;return null;}
    public function create(string $name,string $email,string $hash): int {$id=count($this->rows)+1;$this->rows[$id]=['id'=>$id,'nome'=>$name,'email'=>$email,'senha_hash'=>$hash,'perfil'=>'usuario'];return $id;}
    public function find(int $id): ?array {return $this->rows[$id]??null;}
}
class MemoryExercises extends ExerciseRepository {
    public function __construct() {}
    public function find(int $id): ?array {if($id===99)return ['id'=>99,'nome'=>'Remada','grupo_muscular'=>'costas'];return $id>=1 && $id<=6?['id'=>$id,'nome'=>'Exercício '.$id,'grupo_muscular'=>'peito','descricao'=>'','video_url'=>'']:null;}
    public function byGroup(string $group): array {return $group==='peito'?array_map(fn($i)=>$this->find($i),range(1,6)):[];}
}
class MemoryWorkouts extends WorkoutRepository {
    public array $rows=[];
    public function __construct() {}
    public function create(int $user,array $d): int {$id=$this->rows ? max(array_keys($this->rows))+1 : 1;$this->rows[$id]=$d+['id'=>$id,'usuario_id'=>$user];return $id;}
    public function findOwned(int $id,int $user): ?array {$r=$this->rows[$id]??null;return $r && $r['usuario_id']===$user ? $r:null;}
    public function update(int $id,int $user,array $d): void {if($this->findOwned($id,$user))$this->rows[$id]=$d+['id'=>$id,'usuario_id'=>$user];}
    public function delete(int $id,int $user): void {if($this->findOwned($id,$user))unset($this->rows[$id]);}
}
$pass=0;$fail=0;
function check(bool $ok,string $message='Condição não atendida'): void {if(!$ok)throw new RuntimeException($message);}
function rejects(callable $fn,int $status): void {try{$fn();}catch(HttpError $e){check($e->status===$status,'Status inesperado: '.$e->status);return;}throw new RuntimeException('A ação deveria ser rejeitada.');}
function test(string $name,callable $fn): void {global $pass,$fail;try{$fn();$pass++;echo "OK  $name\n";}catch(Throwable $e){$fail++;echo "FALHA  $name: ".$e->getMessage()."\n";}}
$users=new MemoryUsers();$auth=new AuthService($users);
// Credencial aleatória de fixture, criada a cada execução; não é uma conta da aplicação.
$password=bin2hex(random_bytes(12));
$uid=$auth->register(['nome'=>'Pessoa Teste','email'=>'teste@example.test','senha'=>$password,'perfil'=>'admin']);
$repo=new MemoryWorkouts();$service=new WorkoutService($repo,new MemoryExercises());
$valid=['grupo_muscular'=>'peito','exercicios'=>[['exercicio_id'=>1,'series'=>3,'repeticoes'=>12,'carga'=>'25.50']],'observacao'=>'Treino de teste','confirmado'=>true];
test('Cadastro gera hash e ignora tentativa de escolher perfil admin',function()use($users,$uid,$password){check($users->rows[$uid]['perfil']==='usuario');check($users->rows[$uid]['senha_hash']!==$password);check(password_verify($password,$users->rows[$uid]['senha_hash']));});
test('Login válido devolve usuário sem hash',function()use($auth,$password){$u=$auth->login(['email'=>'teste@example.test','senha'=>$password]);check($u['id']===1 && !isset($u['senha_hash']));});
test('Senha incorreta é rejeitada',fn()=>rejects(fn()=>$auth->login(['email'=>'teste@example.test','senha'=>bin2hex(random_bytes(12))]),401));
test('Conta inexistente é rejeitada',fn()=>rejects(fn()=>$auth->login(['email'=>'ausente@example.test','senha'=>$password]),401));
test('E-mail duplicado é rejeitado',fn()=>rejects(fn()=>$auth->register(['nome'=>'Outro','email'=>'teste@example.test','senha'=>$password]),409));
test('Treino válido é criado e recuperado',function()use($service,$valid){$id=$service->save(1,$valid);check($service->owned($id,1)['exercicios'][0]['series']===3);});
test('Séries zero são rejeitadas',fn()=>rejects(fn()=>Validator::workout(array_replace($valid,['exercicios'=>[['exercicio_id'=>1,'series'=>0]]])),422));
test('Carga negativa é rejeitada',fn()=>rejects(fn()=>Validator::workout(array_replace($valid,['exercicios'=>[['exercicio_id'=>1,'carga'=>-1]]])),422));
test('Tentativa de enviar data futura é rejeitada (horário automático)',fn()=>rejects(fn()=>Validator::workout(array_replace($valid,['data_treino'=>date('Y-m-d\TH:i',time()+86400)])),422));
test('Tentativa de enviar data inexistente é rejeitada',fn()=>rejects(fn()=>Validator::workout(array_replace($valid,['data_treino'=>'2025-02-30T12:00'])),422));
test('Arrays em campos escalares são rejeitados',fn()=>rejects(fn()=>Validator::workout(array_replace($valid,['exercicios'=>[['exercicio_id'=>1,'series'=>[3]]]])),422));
test('Exercício inexistente é rejeitado',fn()=>rejects(fn()=>$service->save(1,array_replace($valid,['exercicios'=>[['exercicio_id'=>999]]])),422));
test('Leitura de outro usuário é negada',fn()=>rejects(fn()=>$service->owned(1,2),404));
test('Edição de outro usuário é negada sem alterar registro',function()use($service,$valid,$repo){$before=$repo->rows[1];rejects(fn()=>$service->save(2,array_replace($valid,['exercicios'=>[['exercicio_id'=>1,'carga'=>50]]]),1),404);check($before===$repo->rows[1]);});
test('Exclusão de outro usuário é negada',function()use($service,$repo){rejects(fn()=>$service->delete(1,2),404);check(isset($repo->rows[1]));});
test('Proprietário consegue editar',function()use($service,$valid){$service->save(1,array_replace($valid,['exercicios'=>[['exercicio_id'=>1,'series'=>4]]]),1);check($service->owned(1,1)['exercicios'][0]['series']===4);});
test('Proprietário consegue excluir',function()use($service,$repo){$service->delete(1,1);check(!isset($repo->rows[1]));});
test('Usuário comum não passa pela proteção admin',fn()=>rejects(fn()=>Auth::admin(['id'=>1,'perfil'=>'usuario']),403));
test('Administrador passa pela proteção',fn()=>Auth::admin(['id'=>1,'perfil'=>'admin']));
test('Requisição sem sessão não é autenticada',function()use($users){$_SESSION=[];rejects(fn()=>Auth::user($users),401);});
test('Token CSRF válido é aceito',function(){$_SESSION=[];$t=Csrf::token();Csrf::verify($t);});
test('Token CSRF inválido é rejeitado',fn()=>rejects(fn()=>Csrf::verify('invalido'),403));
test('Token antigo é rejeitado após rotação',function(){$old=Csrf::token();unset($_SESSION['csrf']);Csrf::token();rejects(fn()=>Csrf::verify($old),403);});
test('Escaping neutraliza HTML em saída',function(){check(Response::escape('<script>alert("x")</script>')==='&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;');});
test('Grupo muscular fora da lista é rejeitado',fn()=>rejects(fn()=>Validator::exercise(['nome'=>'Exercício','grupo_muscular'=>'invalido']),422));

$quick=['grupo_muscular'=>'peito','exercicios'=>[['exercicio_id'=>1]],'confirmado'=>true];
test('Registro rápido sem séries/repetições/carga',function()use($service,$quick){$id=$service->save(1,$quick);$r=$service->owned($id,1);check($r['exercicios'][0]['series']===null && $r['exercicios'][0]['carga']===null);});
test('Treino com vários exercícios',function()use($service,$quick){$id=$service->save(1,array_replace($quick,['exercicios'=>[['exercicio_id'=>1],['exercicio_id'=>2],['descricao_livre'=>'Remada da máquina pessoal']]]));check(count($service->owned($id,1)['exercicios'])===3);});
test('Texto livre sem exercício do catálogo',function()use($service,$quick){$id=$service->save(1,array_replace($quick,['exercicios'=>[['descricao_livre'=>'Flexões em casa']]]));check($service->owned($id,1)['exercicios'][0]['exercicio_id']===null);});
test('Registro vazio é rejeitado',fn()=>rejects(fn()=>$service->save(1,array_replace($quick,['exercicios'=>[]])),422));
test('Registro não confirmado é rejeitado',fn()=>rejects(fn()=>$service->save(1,array_replace($quick,['confirmado'=>false])),422));
test('Exercício de outro grupo é rejeitado',fn()=>rejects(fn()=>$service->save(1,array_replace($quick,['exercicios'=>[['exercicio_id'=>99]]])),422));
test('Exercício repetido é rejeitado',fn()=>rejects(fn()=>$service->save(1,array_replace($quick,['exercicios'=>[['exercicio_id'=>1],['exercicio_id'=>1]]])),422));
test('48 horas: antes do limite está em descanso',function(){check(RestService::status('2026-10-19 18:00:00',new DateTimeImmutable('2026-10-21 17:59:59'))['em_descanso']);});
test('48 horas: no limite está disponível',function(){check(!RestService::status('2026-10-19 18:00:00',new DateTimeImmutable('2026-10-21 18:00:00'))['em_descanso']);});
test('48 horas: sem registro não há descanso',function(){check(RestService::status(null)['disponivel_em']===null);});
test('Semana começa na segunda',function(){check(!RestService::status('2026-10-18 23:59:59',new DateTimeImmutable('2026-10-19 10:00:00'))['treinado_semana']);});
$suggestions=new SuggestionService(new MemoryExercises());
test('Gerar sugestão não grava treino nem altera registros',function()use($suggestions,$repo){$before=$repo->rows;$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'30']);check(count($s['exercicios'])===3);check($repo->rows===$before);});
test('Tempo 60 monta cinco itens distintos',function()use($suggestions){$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'60']);check(count(array_unique(array_column($s['exercicios'],'id')))===5);});
test('Tempo 90 monta seis itens distintos',function()use($suggestions){check(count($suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'90'])['exercicios'])===6);});
test('Nível intermediário altera volume sugerido',function()use($suggestions){$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'30','nivel'=>'intermediario','objetivo'=>'geral']);check($s['orientacao']['series']==='3 séries');});
test('Objetivo força altera repetições e descanso',function()use($suggestions){$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'30','nivel'=>'intermediario','objetivo'=>'forca']);check($s['orientacao']['repeticoes']==='4–6 repetições');check($s['orientacao']['descanso']==='2–3 min');});
test('Objetivo condicionamento altera orientação',function()use($suggestions){$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'30','nivel'=>'iniciante','objetivo'=>'condicionamento']);check($s['orientacao']['repeticoes']==='12–20 repetições');check($s['orientacao']['descanso']==='30–60 s');});
test('Catálogo vazio não inventa exercícios',function()use($suggestions){check($suggestions->generate(['grupo_muscular'=>'costas','tempo'=>'60'])['exercicios']===[]);});
test('Confirmar parte da sugestão cria um único treino',function()use($suggestions,$service,$repo){$s=$suggestions->generate(['grupo_muscular'=>'peito','tempo'=>'30']);$before=count($repo->rows);$id=$service->save(1,['grupo_muscular'=>'peito','confirmado'=>true,'exercicios'=>[['exercicio_id'=>$s['exercicios'][0]['id']]]]);check(count($repo->rows)===$before+1);check(count($service->owned($id,1)['exercicios'])===1);});
test('Edição preserva data confirmada',function()use($quick){$r=new MemoryWorkouts();$s=new WorkoutService($r,new MemoryExercises(),fn()=>new DateTimeImmutable('2026-10-19 18:00:00'));$id=$s->save(1,$quick);$s2=new WorkoutService($r,new MemoryExercises(),fn()=>new DateTimeImmutable('2026-10-20 22:00:00'));$s2->save(1,$quick,$id);check($r->rows[$id]['data_treino']==='2026-10-19 18:00:00');});
test('Preferências válidas',function(){check(Validator::profile(['nivel'=>'avancado','objetivo'=>'forca','tempo_disponivel'=>'60'])['objetivo']==='forca');});
test('Preferência inválida',fn()=>rejects(fn()=>Validator::profile(['nivel'=>'alien','objetivo'=>'geral','tempo_disponivel'=>'30']),422));
test('Vídeo vazio é aceito',function(){check(Validator::video([])==='');});
test('Vídeo javascript é rejeitado',fn()=>rejects(fn()=>Validator::video(['video_url'=>'javascript:alert(1)']),422));
test('Vídeo HTTPS é aceito sintaticamente',function(){check(Validator::video(['video_url'=>'https://example.com/tutorial'])==='https://example.com/tutorial');});
test('Dois campos livres são rejeitados sem mesclar detalhes',fn()=>rejects(fn()=>Validator::workout(['grupo_muscular'=>'peito','confirmado'=>true,'exercicios'=>[['descricao_livre'=>'Um'],['descricao_livre'=>'Dois']]]),422));
echo "\n$pass aprovado(s); $fail falha(s). PHP ".PHP_VERSION."\n";
exit($fail?1:0);
