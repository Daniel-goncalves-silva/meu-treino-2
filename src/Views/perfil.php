<div class="page-title"><p class="eyebrow">SUAS PREFERÊNCIAS</p><h1>Perfil</h1><p><?= e($user['nome']) ?> · <?= e($user['email']) ?></p></div>
<section class="form-card narrow profile-card"><form id="perfil-form">
<label for="nivel">Nível</label><select id="nivel" name="nivel"><option value="iniciante">Iniciante</option><option value="intermediario">Intermediário</option><option value="avancado">Avançado</option></select>
<label for="objetivo">Objetivo</label><select id="objetivo" name="objetivo"><option value="hipertrofia">Hipertrofia</option><option value="forca">Força</option><option value="condicionamento">Condicionamento</option><option value="geral">Geral</option></select>
<label for="tempo_disponivel">Tempo normalmente disponível</label><select id="tempo_disponivel" name="tempo_disponivel"><option value="30">30 minutos</option><option value="60">1 hora</option><option value="90">Mais de 1 hora</option></select>
<p class="small-text">As três preferências influenciam “Montar treino”: o tempo define quantos exercícios aparecem; o nível ajusta o volume sugerido; e o objetivo ajusta repetições e descanso.</p><button class="button" type="submit" disabled>Salvar preferências</button>
</form></section>
