<div class="page-title"><p class="eyebrow">JÁ TREINOU?</p><h1 id="form-title">O que você treinou?</h1><p>Escolha o grupo, marque o que fez e registre. É rápido.</p></div>
<section class="form-card wide"><form id="treino-form">
<label for="grupo">Grupo muscular</label><select id="grupo"><?php foreach ($groups as $key=>$name): ?><option value="<?= e($key) ?>"><?= e($name) ?></option><?php endforeach ?></select>
<fieldset class="exercise-fieldset"><legend>O que você fez?</legend><div id="exercise-options">Carregando exercícios…</div></fieldset>
<label for="outro">Outro exercício / O que você fez? <span class="muted">(opcional)</span></label><textarea id="outro" rows="2" maxlength="500" placeholder="Ex.: remada máquina, remada unilateral"></textarea>
<details id="detalhes-treino"><summary>+ Adicionar detalhes (opcional)</summary><p class="small-text">Preencha apenas se quiser. Campos vazios ficam sem informação.</p><div id="item-details"></div><label for="observacao">Observação (opcional)</label><textarea id="observacao" rows="3" maxlength="1000"></textarea></details>
<p id="registro-horario" class="small-text">A data e hora serão salvas automaticamente quando você registrar.</p>
<div class="actions"><button id="salvar" class="button" type="submit" disabled>Registrar treino</button><a href="index.php?view=treinos">Cancelar</a></div>
</form></section>
