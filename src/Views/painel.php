<section class="hero"><div><p class="eyebrow">OLÁ, <?= e($user['nome']) ?></p><h1>Seu ritmo,<br>sua evolução.</h1><p>Escolha um grupo, registre seu treino<br>ou monte uma sugestão para hoje.</p><a class="button" href="index.php?view=registrar">+ Registrar treino</a></div><div class="hero-note"><span aria-hidden="true">↗</span><p>Constância se constrói<br>um registro de cada vez.</p></div></section>
<section class="stats" aria-label="Resumo dos seus treinos"><article><p>Total de registros</p><strong id="total">—</strong><small>Um registro por treino confirmado</small></article><article><p>Último treino</p><strong id="ultimo" class="stat-date">—</strong><small id="ultimo-nome">Carregando…</small></article><article><p>Grupos nesta semana</p><strong id="semana-total">—</strong><small>Semana de segunda a domingo</small></article></section>
<section id="treinos"><div class="section-heading"><div><p class="eyebrow">SUA ROTINA</p><h2>Grupos musculares</h2></div><a href="#semana">Ver minha semana ↓</a></div><p>Consulte os exercícios e acompanhe o intervalo desde o último registro.</p>
<div class="treinos-grid">
<?php foreach ($groups as $key=>$name): ?>
<article class="grupo-card" data-card="<?= e($key) ?>">
<figure><img src="assets/imagens_otimizadas/<?= e($key) ?>.webp" alt="Musculatura de <?= e($name) ?> destacada em vermelho" width="800" height="600" loading="lazy"></figure>
<div class="card-body"><h3><?= e($name) ?></h3><p class="status-treino" id="status-<?= e($key) ?>">Carregando…</p><p class="small-text" id="data-<?= e($key) ?>"></p><p class="small-text" id="disponivel-<?= e($key) ?>"></p><a class="button secondary full" href="index.php?view=registrar&amp;grupo=<?= e($key) ?>">Registrar treino →</a><a class="suggest-link" href="index.php?view=montar&amp;grupo=<?= e($key) ?>">Montar sugestão</a></div>
</article>
<?php endforeach ?>
</div></section>
<section id="semana" class="panel"><p class="eyebrow">SEMANA ATUAL</p><h2>Minha semana</h2><ul id="lista-semana" class="week-list"></ul></section>
<section class="panel"><div class="section-heading"><h2>Registros recentes</h2><a href="index.php?view=treinos">Ver histórico →</a></div><div id="recentes">Carregando…</div></section>
<aside>O intervalo de <strong>48 horas</strong> é uma referência organizacional adotada pelo aplicativo, não uma orientação médica.</aside>
