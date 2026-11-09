<?php
require dirname(__DIR__).'/config/bootstrap.php';
use App\Helpers\{Database,Response,HttpError};
use App\Middleware\{Auth,Csrf};
use App\Repositories\UserRepository;
$view=$_GET['view']??'painel';
if (!is_string($view) || !in_array($view,['login','cadastro','painel','treinos','registrar','montar','perfil','admin'],true)) throw new HttpError(404,'Página não encontrada.');
$public=in_array($view,['login','cadastro'],true);
$user=null;
if (!$public) {
    if (empty($_SESSION['user_id'])) {header('Location: index.php?view=login');exit;}
    try {$user=Auth::user(new UserRepository(Database::connect()));}
    catch (HttpError $e) {if ($e->status===401) {header('Location: index.php?view=login');exit;}throw $e;}
    if ($view==='admin') Auth::admin($user);
}
$groups=require APP_ROOT.'/config/groups.php';
function e($v): string {return Response::escape($v);}
$titles=['login'=>'Entre na sua rotina','cadastro'=>'Crie sua conta','painel'=>'Seu ritmo, sua evolução.','treinos'=>'Meus treinos','registrar'=>'Registrar treino','montar'=>'Montar treino','perfil'=>'Perfil','admin'=>'Administração'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Meu Treino: organize seus exercícios e acompanhe sua semana.">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<title><?= e($titles[$view]) ?> | Meu Treino</title>
<link rel="stylesheet" href="assets/style.css?v=2">
<script src="assets/script.js?v=3" defer></script>
</head>
<body data-view="<?= e($view) ?>" id="topo">
<a class="skip" href="#conteudo">Pular para o conteúdo</a>
<header class="cabecalho">
 <a class="marca" href="index.php"><span class="marca-icone" aria-hidden="true">MT</span><span>Meu Treino<small>UM REGISTRO DE CADA VEZ</small></span></a>
 <nav aria-label="Navegação principal">
 <?php if ($user): ?>
 <?php foreach (['painel'=>'Painel','registrar'=>'Registrar treino','montar'=>'Montar treino','treinos'=>'Meus treinos','perfil'=>'Perfil'] as $v=>$label): ?>
 <a href="index.php?view=<?= e($v) ?>" <?= $view===$v?'aria-current="page"':'' ?>><?= e($label) ?></a>
 <?php endforeach ?>
 <?php if ($user['perfil']==='admin'): ?><a href="index.php?view=admin" <?= $view==='admin'?'aria-current="page"':'' ?>>Administração</a><?php endif ?>
 <button id="logout" class="button secondary small" type="button">Sair</button>
 <?php else: ?><a href="index.php?view=login">Entrar</a><a href="index.php?view=cadastro">Criar conta</a><?php endif ?>
 </nav>
</header>
<main id="conteudo">
<div id="mensagem" class="mensagem" role="status" aria-live="polite" tabindex="-1" hidden></div>
<?php require APP_ROOT.'/src/Views/'.$view.'.php'; ?>
</main>
<footer><span>Meu Treino · SCOM 2026 · Daniel Gonçalves da Silva</span><a href="#topo">↑ Voltar ao topo</a></footer>
</body></html>
