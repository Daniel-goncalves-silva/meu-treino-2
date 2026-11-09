<?php
// Integração HTTP real + MySQL. Rodar apenas contra uma instalação LOCAL de testes.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$base=$argv[1]??'http://localhost/Meu-Treino-2/public';
$parts=parse_url($base);
if(!in_array($parts['host']??'',['localhost','127.0.0.1'],true))die("Use somente localhost ou 127.0.0.1.\n");
if(!extension_loaded('curl'))die("Ative a extensão curl do PHP.\n");
require dirname(__DIR__).'/config/bootstrap.php';
use App\Helpers\Database;
$jar1=tempnam(sys_get_temp_dir(),'mt1');$jar2=tempnam(sys_get_temp_dir(),'mt2');
$emails=[];$ids=[];$pass=0;$fail=0;
function request(string $resource,string $method='GET',?array $body=null,string $token='',?string $jar=null,array $params=[]): array {
 global $base;
 $h=curl_init($base.'/api.php?'.http_build_query(['resource'=>$resource]+$params));
 $headers=['Accept: application/json','Content-Type: application/json'];if($token!=='')$headers[]='X-CSRF-Token: '.$token;
 curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false]);
 if($jar){curl_setopt($h,CURLOPT_COOKIEFILE,$jar);curl_setopt($h,CURLOPT_COOKIEJAR,$jar);}
 if($body!==null)curl_setopt($h,CURLOPT_POSTFIELDS,json_encode($body));
 $raw=curl_exec($h);$status=curl_getinfo($h,CURLINFO_HTTP_CODE);$error=curl_error($h);curl_close($h);
 if($raw===false)throw new RuntimeException($error);
 $json=json_decode($raw,true);if(!is_array($json))throw new RuntimeException('Servidor não retornou JSON: status '.$status);
 return [$status,$json];
}
function expect(string $name,array $response,int $status): void {global $pass,$fail;if($response[0]===$status){$pass++;echo "OK $name ($status)\n";}else{$fail++;echo "FALHA $name: esperado $status, recebido {$response[0]}\n";}}
function pageResult(string $view,?string $jar=null): array {
 global $base;
 $h=curl_init($base.'/index.php?view='.rawurlencode($view));
 curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false]);
 if($jar)curl_setopt($h,CURLOPT_COOKIEFILE,$jar);
 $html=curl_exec($h);$status=curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);
 return [$status,['html'=>$html]];
}
try {
 $db=Database::connect();
 expect('Sem login',request('treinos'),401);
 expect('Página privada sem login',pageResult('painel'),302);
 foreach([$jar1,$jar2] as $i=>$jar){
  $csrf=request('csrf','GET',null,'',$jar)[1]['data']['token'];
  $email='teste-'.bin2hex(random_bytes(6)).'@example.test';$password=bin2hex(random_bytes(12));$emails[]=$email;
  expect('Cadastro',request('cadastro','POST',['nome'=>'Teste Integração','email'=>$email,'senha'=>$password],$csrf,$jar),201);
  expect('Login incorreto',request('login','POST',['email'=>$email,'senha'=>bin2hex(random_bytes(12))],$csrf,$jar),401);
  $r=request('login','POST',['email'=>$email,'senha'=>$password],$csrf,$jar);expect('Login válido',$r,200);
  $tokens[$i]=$r[1]['data']['csrf'];$ids[$i]=$r[1]['data']['usuario']['id'];
 }
 foreach(['painel','registrar','montar','treinos','perfil'] as $view)expect('Página PHP '.$view,pageResult($view,$jar1),200);
 expect('Página admin bloqueada',pageResult('admin',$jar1),403);
 $ex=request('exercicios','GET',null,'',$jar1)[1]['data'][0]['id'];
 $payload=['grupo_muscular'=>'abdomen','exercicios'=>[['exercicio_id'=>$ex]],'confirmado'=>true,'observacao'=>'<img src=x onerror=alert(1)>'];
 $payload['grupo_muscular']=request('exercicios','GET',null,'',$jar1,['id'=>$ex])[1]['data']['grupo_muscular'];
 $before=request('treinos','GET',null,'',$jar1)[1]['data']['total'];
 $suggested=request('sugestao','GET',null,'',$jar1,['grupo_muscular'=>$payload['grupo_muscular'],'tempo'=>'60']);expect('Sugestão disponível',$suggested,200);
 $after=request('treinos','GET',null,'',$jar1)[1]['data']['total'];
 expect('Sugestão não grava histórico',[$before===$after?200:500,[]],200);
 expect('Sugestão não inicia descanso',[request('resumo','GET',null,'',$jar1)[1]['data']['grupos'][0]['ultimo']===null?200:500,[]],200);
 $multi=array_map(fn($e)=>['exercicio_id'=>$e['id']],array_slice($suggested[1]['data']['exercicios'],0,2));
 $confirmed=request('treinos','POST',['grupo_muscular'=>$payload['grupo_muscular'],'exercicios'=>$multi,'confirmado'=>true],$tokens[0],$jar1);expect('Confirmar sugestão com dois itens',$confirmed,201);
 $multiRead=request('treinos','GET',null,'',$jar1,['id'=>$confirmed[1]['data']['id']]);expect('Retorna dois itens',[count($multiRead[1]['data']['exercicios'])===2?200:500,[]],200);
 expect('Detalhes omitidos são NULL',[$multiRead[1]['data']['exercicios'][0]['series']===null && $multiRead[1]['data']['exercicios'][0]['carga']===null?200:500,[]],200);
 expect('Preferências salvas',request('perfil','PUT',['nivel'=>'intermediario','objetivo'=>'geral','tempo_disponivel'=>'60'],$tokens[0],$jar1),200);
 expect('Preferências persistem',[request('perfil','GET',null,'',$jar1)[1]['data']['tempo_disponivel']==='60'?200:500,[]],200);
 expect('Vídeo inválido/perfil comum',request('exercicios','POST',['nome'=>'Teste','grupo_muscular'=>'peito','video_url'=>'javascript:alert(1)'],$tokens[0],$jar1),403);
 expect('CSRF inválido',request('treinos','POST',$payload,'invalido',$jar1),403);
 expect('Entrada inválida',request('treinos','POST',array_replace($payload,['exercicios'=>[['exercicio_id'=>$ex,'series'=>0]]]),$tokens[0],$jar1),422);
 $created=request('treinos','POST',$payload,$tokens[0],$jar1);expect('Criar treino',$created,201);$id=$created[1]['data']['id'];
 expect('Ler treino',request('treinos','GET',null,'',$jar1,['id'=>$id]),200);
 expect('ID de outro usuário (leitura)',request('treinos','GET',null,'',$jar2,['id'=>$id]),404);
 expect('ID de outro usuário (edição)',request('treinos','PUT',$payload,$tokens[1],$jar2,['id'=>$id]),404);
 expect('ID de outro usuário (exclusão)',request('treinos','DELETE',null,$tokens[1],$jar2,['id'=>$id]),404);
 expect('Admin bloqueado',request('usuarios','GET',null,'',$jar1),403);
 expect('ID com SQL Injection',request('treinos','GET',null,'',$jar1,['id'=>'1 OR 1=1']),422);
 $originalDate=request('treinos','GET',null,'',$jar1,['id'=>$id])[1]['data']['data_treino'];
 expect('Editar próprio treino',request('treinos','PUT',array_replace($payload,['exercicios'=>[['exercicio_id'=>$ex,'series'=>4]]]),$tokens[0],$jar1,['id'=>$id]),200);
 $edited=request('treinos','GET',null,'',$jar1,['id'=>$id]);
 expect('Edição mantém horário',[$edited[1]['data']['data_treino']===$originalDate?200:500,[]],200);
 expect('Excluir próprio treino',request('treinos','DELETE',null,$tokens[0],$jar1,['id'=>$id]),200);
 // Promove somente a fixture para exercitar administração; limpeza ao final.
 $q=$db->prepare("UPDATE usuarios SET perfil='admin' WHERE id=?");$q->execute([$ids[0]]);
 expect('Admin autorizado',request('usuarios','GET',null,'',$jar1),200);
 expect('Página admin autorizada',pageResult('admin',$jar1),200);
 expect('Admin rejeita URL insegura',request('exercicios','POST',['nome'=>'Teste','grupo_muscular'=>'peito','video_url'=>'javascript:alert(1)'],$tokens[0],$jar1),422);
 $exBody=['nome'=>'Teste '.bin2hex(random_bytes(6)),'grupo_muscular'=>'peito','descricao'=>'Fixture de integração','video_url'=>''];
 $r=request('exercicios','POST',$exBody,$tokens[0],$jar1);expect('Admin cria exercício',$r,201);$exerciseId=$r[1]['data']['id'];
 expect('Admin edita exercício',request('exercicios','PUT',array_replace($exBody,['descricao'=>'Editado']),$tokens[0],$jar1,['id'=>$exerciseId]),200);
 expect('Admin exclui exercício',request('exercicios','DELETE',null,$tokens[0],$jar1,['id'=>$exerciseId]),200);
 expect('Logout',request('logout','POST',['encerrar'=>true],$tokens[0],$jar1),200);
 expect('Sessão encerrada',request('treinos','GET',null,'',$jar1),401);
 expect('CSRF anterior ao logout',request('treinos','POST',$payload,$tokens[0],$jar1),403);
} catch(Throwable $e){$fail++;echo 'FALHA de execução: '.$e->getMessage()."\n";}
finally {
 if(isset($db)){
  foreach($emails as $email){$q=$db->prepare('DELETE t FROM treinos t JOIN usuarios u ON u.id=t.usuario_id WHERE u.email=?');$q->execute([$email]);$q=$db->prepare('DELETE FROM usuarios WHERE email=?');$q->execute([$email]);}
  if(isset($exerciseId)){$q=$db->prepare('DELETE FROM exercicios WHERE id=?');$q->execute([$exerciseId]);}
 }
 @unlink($jar1);@unlink($jar2);
}
echo "$pass aprovado(s); $fail falha(s).\n";exit($fail?1:0);
