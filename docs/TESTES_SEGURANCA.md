# Roteiro de segurança — preencher com resultados reais

**Nenhum resultado abaixo é alegação de execução.** São ações e expectativas. Faça os testes no seu próprio localhost, com banco de testes e duas contas comuns A/B. Crie um treino com A, anote o ID e faça logout. Use uma conta admin separada. Não teste sistemas de terceiros.

Abra F12 → Network (Rede), marque preservar log quando necessário, execute e capture a requisição com método/URL/status e resposta. Oculte senhas, cookies e tokens antes de compartilhar. Não publique o `.env`.

Para chamadas com corpo, use a função `chamada` de `API.md` no Console. Para testes com token intencionalmente incorreto, use os comandos completos abaixo. O token real fica apenas no navegador.

## 1. SQL Injection
**Ação A:** como usuário autenticado, acesse:
```text
http://localhost/Meu-Treino-2/public/api.php?resource=treinos&id=1%20OR%201%3D1
```
Esperado: HTTP 422, id precisa ser inteiro; nenhuma listagem ampla é retornada.

**Ação B:** na tela de login, inicialize `chamada` conforme API.md e envie:
```js
await chamada('api.php?resource=login','POST',{
 email:"' OR '1'='1", senha:'texto-de-teste'
});
```
Esperado: 422 (e-mail inválido), sem autenticação.

**Ação C (parâmetro que chega ao banco):** em um treino próprio, salve a observação `x'); DROP TABLE treinos; --`. Ela deve ser armazenada como texto; confirme que a tabela continua existindo e outros registros continuam acessíveis. Observação não é concatenada no SQL.

**Prints:** 422 em Network, observação exibida como texto e tabela `treinos` ainda acessível. Não basta o print do formulário: inclua resposta/status. A/B demonstram validação; C exercita armazenamento via prepared statement.

## 2. XSS armazenado e refletido
**Armazenado:** registre observação `<img src=x onerror=alert(1)>`. No histórico, ela deve aparecer literalmente, sem imagem e sem alerta. Repita com nome da conta contendo `<script>alert(1)</script>` (cabe no limite). O cabeçalho PHP deve mostrar texto, não executar script. Use conta de teste.

**Refletido:** abra:
```text
http://localhost/Meu-Treino-2/public/index.php?view=%3Cscript%3Ealert(1)%3C%2Fscript%3E
```
Esperado: 404 com mensagem genérica; o valor de `view` não é inserido na página. Repita `api.php?resource=treinos&id=%3Cscript%3Ealert(1)%3C%2Fscript%3E` autenticado: esperado 422. Não há alerta.

**Prints:** conteúdo literal no histórico, status do teste refletido, inspeção DOM mostrando texto escapado. A política CSP é defesa adicional; a proteção principal é `htmlspecialchars`/`textContent`. Um teste PHP de escaping sozinho não prova comportamento do navegador.

## 3. Rota privada sem login
Abra janela anônima: `/public/api.php?resource=treinos` → esperado HTTP 401 e JSON. Abra `/public/index.php?view=treinos` → esperado redirecionamento à tela de login. Isso deve funcionar mesmo se você souber o ID de um treino.

**Prints:** 401 da API; redirecionamento da página. Não envie token/cookie de outra sessão.

## 4. Perfil insuficiente
Entre com conta comum. Acesse `/public/index.php?view=admin` → esperado 403. Acesse `/public/api.php?resource=usuarios` → esperado 403. Tente POST de exercício com token válido pela função `chamada` → esperado 403. Com admin, a mesma leitura deve retornar 200.

**Prints:** página negada e resposta JSON 403; comparação com admin 200. Captura do log `acesso_admin_negado` sem dados sensíveis.

## 5. CSRF inválido e ausente
Logado, substitua `exercicio_id` por um exercício existente:
```js
const r = await fetch('api.php?resource=treinos', {
 method:'POST',
 headers:{'Content-Type':'application/json','X-CSRF-Token':'invalido'},
 body:JSON.stringify({grupo_muscular:'peito',exercicios:[{exercicio_id:1}],confirmado:true})
});
console.log(r.status, await r.json());
```
Esperado: 403, nenhuma inserção. Repita omitindo `X-CSRF-Token`: 403. Repita com token correto e item válido: 201. Confirme no histórico que somente o teste válido gerou registro.

**Prints:** requisição inválida/ausente e status 403; requisição válida 201; histórico. Oculte o token válido nos prints.

## 6. Sessão e token antigos após logout
Na conta logada, guarde o token apenas na variável do Console:
```js
const tokenAntigo = document.querySelector('meta[name="csrf-token"]').content;
await fetch('api.php?resource=logout',{
 method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':tokenAntigo},body:'{}'
});
const privado=await fetch('api.php?resource=treinos');
console.log(privado.status, await privado.json());
const antigo=await fetch('api.php?resource=logout',{
 method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':tokenAntigo},body:'{}'
});
console.log(antigo.status, await antigo.json());
```
Esperado: logout 200; GET privado 401; token anterior 403. Atualize a página e confirme que exige login. CSRF continua reutilizável enquanto a sessão é válida; não se promete token de uso único.

**Prints:** 200/401/403. Opcionalmente confirme no navegador que o ID da sessão mudou entre antes e depois do login, sem publicar seu conteúdo.

## 7. Tentativa de acessar treino de outra conta (IDOR)
1. Crie treino com A e anote ID. Saia e entre com B.
2. GET `api.php?resource=treinos&id=ID_DE_A` → 404.
3. PUT para o mesmo ID com token de B e corpo completo válido → 404.
4. DELETE para o mesmo ID com token de B → 404.
5. Volte a A: o treino deve continuar igual e permitir edição/exclusão por A.

**Prints:** três respostas 404, registro preservado de A e controle positivo de A. Admin também não deve poder editar treinos alheios.

## 8. Acesso a arquivos privados e erros de produção
Acesse pela URL da raiz `.env`, `config/bootstrap.php`, `database/schema.sql`, `logs/app.log`, `tests/run.php`, `tools/promover-admin.php`. Esperado: 403 no Apache com `.htaccess` ativo, nunca conteúdo sensível. Em servidor PHP iniciado com `-t public`, esses caminhos não existem na raiz pública (404).

Configure temporariamente `APP_ENV=production` e uma porta de banco inválida **apenas em ambiente de teste**. Uma requisição autenticada que precise de banco deve retornar 500 genérico sem debug. Restaure a porta e ambiente após o teste. Consulte o evento `erro_interno` no log.

**Prints:** bloqueios HTTP e JSON genérico, sem exibir configurações reais.

## Registro dos resultados
| Teste | Data/ambiente | Resultado observado | Status | Arquivo da evidência |
|---|---|---|---|---|
| SQL Injection | Pendente | Pendente | Pendente | Pendente |
| XSS armazenado/refletido | Pendente | Pendente | Pendente | Pendente |
| Sem login | Pendente | Pendente | Pendente | Pendente |
| Perfil insuficiente | Pendente | Pendente | Pendente | Pendente |
| CSRF inválido | Pendente | Pendente | Pendente | Pendente |
| Sessão/token encerrados | Pendente | Pendente | Pendente | Pendente |
| Dados de outra conta | Pendente | Pendente | Pendente | Pendente |
| Arquivos privados/produção | Pendente | Pendente | Pendente | Pendente |

A rubrica C2 pontua até cinco testes comprovados. Escolha pelo menos cinco com resultados e capturas reais; mantenha os demais como verificação adicional.


## 9. Planejamento, confirmação e atomicidade
- Anote a contagem do histórico e último horário de um grupo.
- Gere sugestões várias vezes (30/60/90). Confirme que contagem e horário não mudam.
- Abra Registrar este treino, desmarque tudo e confirme: esperado mensagem/422 e nenhuma gravação.
- Marque dois itens e confirme: esperado 201 e **um** treino com dois itens, iniciando 48h.
- Envie um exercício válido junto de outro ID inexistente: esperado 422 e nenhum treino parcial.
- Edite treino próprio e verifique que o horário original não mudou.
- Na conta B, tente usar o ID do treino de A para ler/editar/excluir: esperado 404 e dados de A preservados.
- Salve preferências sem token válido: 403. Campo `perfil: admin` não promove a conta.
- Admin envia `video_url: javascript:alert(1)`: esperado 422. URL vazia: aceita. URL HTTPS válida sintaticamente: aceita, sem afirmar que o site existe.

Corpo mínimo atual:
```json
{"grupo_muscular":"peito","exercicios":[{"exercicio_id":1},{"descricao_livre":"Flexões em casa"}],"confirmado":true}
```
Atualize IDs e grupo conforme catálogo real. `data_treino` não é aceito no corpo; horário vem do servidor. Detalhes opcionais ficam dentro de cada item em `exercicios`.

## Resultados da preparação vs. sua instalação
`docs/evidencias_preparacao` contém saídas reais de testes PHP/API/migração em ambiente Linux isolado. Use-as identificando o ambiente. Testes visuais e capturas de segurança no seu navegador/XAMPP continuam necessários; não transforme expectativas deste roteiro em resultados observados sem executar.
