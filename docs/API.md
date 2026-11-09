# API — evolução 2

Base: `http://localhost/Meu-Treino-2/public/api.php`. As URLs abaixo são relativas à pasta public. Sessão via cookie `meutreino_session`. Não há mudança de rota/rewrite no Apache.

## Contrato comum
Todas as respostas JSON têm `success`, `message`, `data`, `errors`. Erro exemplo: `{"success":false,"message":"Treino não encontrado.","data":null,"errors":{}}`.

POST/PUT/DELETE exigem `X-CSRF-Token`. POST/PUT exigem corpo JSON **objeto** e `Content-Type: application/json`. GET csrf cria/consulta o token da sessão; login gera outro token; logout invalida o anterior. Token é reutilizável durante a sessão, não é de uso único.

Escritas sem token válido retornam 403 antes de autenticação. GET privado anônimo retorna 401. Com token válido, escrita anônima retorna 401. Recurso inexistente: 404. Método errado: 405 + Allow. JSON malformado: 400. Corpo acima de 16 KiB: 413. Content-Type incorreto: 415. Erros inesperados: 500; debug só em development. Senhas/hashes nunca são retornados.

Exemplos são **ilustrativos**, não evidências de execução; resultados reais estão em evidencias_preparacao.

## Helper para testar no Console (F12) do próprio aplicativo
```js
let csrf = document.querySelector('meta[name="csrf-token"]').content;
async function chamada(url, method='GET', body) {
  const opts={method,credentials:'same-origin',headers:{Accept:'application/json'}};
  if(method!=='GET')opts.headers['X-CSRF-Token']=csrf;
  if(body!==undefined){opts.headers['Content-Type']='application/json';opts.body=JSON.stringify(body);}
  const r=await fetch(url,opts),j=await r.json();
  if(j.data?.csrf)csrf=j.data.csrf;
  console.log(r.status,j);return j;
}
```
Use IDs reais do seu catálogo/conta. Crie dados de teste antes de excluir.

## Modelo de treino
```json
{"grupo_muscular":"peito","exercicios":[{"exercicio_id":1},{"exercicio_id":2,"series":3,"repeticoes":12,"carga":20},{"descricao_livre":"Flexões em casa"}],"confirmado":true}
```
- Um grupo: peito, costas, pernas, ombros, bracos, triceps, abdomen.
- 1–20 itens, todos do grupo. Cada item tem **exercicio_id OU descricao_livre** (texto 1–500); no máximo um item livre, que pode descrever vários exercícios.
- Não repetir ID do catálogo. IDs inteiros positivos até 2147483647.
- `series` opcional 1–30; `repeticoes` opcional 1–1000; `carga` opcional 0–1000, até duas casas decimais. Omitido/vazio/null → NULL. Zero kg é válido e diferente de não informado.
- `observacao` opcional 0–1000 caracteres. `confirmado` deve ser booleano true, não texto "true".
- **Não envie `data_treino`**: POST usa horário do servidor em Brasília; PUT mantém o horário do registro original. Tentativa de envio retorna 422.
- PUT substitui grupo, observação e todos os itens de forma transacional. Envie o conjunto desejado; não há PATCH.
- Campo `usuario_id` enviado pelo cliente é ignorado: dono vem da sessão.
- Dados de outra conta retornam 404, inclusive para admin.

## Outros campos
Cadastro: nome 2–100 caracteres, e-mail válido até 190, senha 8–72 bytes sem byte nulo. Perfil público de cadastro não é aceito: nova conta sempre usuario.

Exercício: nome 2–100 único, grupo válido, descrição opcional até 1000; `video_url` opcional até 1000, URL completa HTTPS sem credenciais/controles. Validação é sintática, não verifica o conteúdo/disponibilidade do site.

Preferências: nivel iniciante/intermediario/avancado; objetivo hipertrofia/forca/condicionamento/geral; tempo_disponivel "30"/"60"/"90". 90 representa “mais de uma hora”, não duração exata de 90min.

Paginação: page 1–100000. Treinos 10 por página; usuários 20. Valores numéricos de PDO podem vir como strings; DECIMAL normalmente é string. DATETIME retorna `YYYY-MM-DD HH:mm:ss` em Brasília. Resumo retorna ISO 8601 com offset.

## GET api.php?resource=csrf

Consultar token da sessão.

- Autenticação: Não. Perfil: Qualquer.
- Parâmetros: Nenhum.
- Corpo: nenhum.
- Status: 200,500; 405 para método incompatível.

```js
await chamada("api.php?resource=csrf", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "token": "TOKEN_DA_SESSAO"
  },
  "errors": {}
}
```

## POST api.php?resource=cadastro

Cadastrar conta comum.

- Autenticação: Não; exige CSRF. Perfil: Qualquer.
- Parâmetros: Nenhum.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 201,400,403,409,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=cadastro", "POST", {"nome": "Pessoa Exemplo", "email": "pessoa@example.test", "senha": "SUBSTITUA_POR_SENHA_PESSOAL"});
```

Resposta ilustrativa HTTP 201:
```json
{
  "success": true,
  "message": "Conta criada. Faça login.",
  "data": {
    "id": 1
  },
  "errors": {}
}
```

## POST api.php?resource=login

Validar credenciais e regenerar sessão.

- Autenticação: Não; exige CSRF. Perfil: Qualquer.
- Parâmetros: Nenhum.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 200,400,401,403,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=login", "POST", {"email": "pessoa@example.test", "senha": "SUBSTITUA_POR_SENHA_PESSOAL"});
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Login realizado.",
  "data": {
    "usuario": {
      "id": 1,
      "nome": "Pessoa Exemplo",
      "email": "pessoa@example.test",
      "perfil": "usuario"
    },
    "csrf": "NOVO_TOKEN"
  },
  "errors": {}
}
```

## POST api.php?resource=logout

Destruir sessão.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 200,400,401,403,413,415,500; 405 para método incompatível.

```js
await chamada("api.php?resource=logout", "POST", {});
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Sessão encerrada.",
  "data": null,
  "errors": {}
}
```

## GET api.php?resource=eu

Consultar a conta autenticada.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum.
- Corpo: nenhum.
- Status: 200,401,500; 405 para método incompatível.

```js
await chamada("api.php?resource=eu", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "id": 1,
    "nome": "Pessoa Exemplo",
    "email": "pessoa@example.test",
    "perfil": "usuario"
  },
  "errors": {}
}
```

## GET api.php?resource=perfil

Consultar preferências próprias.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum; não recebe ID de usuário.
- Corpo: nenhum.
- Status: 200,401,500; 405 para método incompatível.

```js
await chamada("api.php?resource=perfil", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "nivel": "iniciante",
    "objetivo": "geral",
    "tempo_disponivel": "30"
  },
  "errors": {}
}
```

## PUT api.php?resource=perfil

Substituir preferências próprias.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum; dono vem da sessão.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 200,400,401,403,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=perfil", "PUT", {"nivel": "iniciante", "objetivo": "geral", "tempo_disponivel": "30"});
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Preferências salvas.",
  "data": {
    "nivel": "iniciante",
    "objetivo": "geral",
    "tempo_disponivel": "30"
  },
  "errors": {}
}
```

## GET api.php?resource=sugestao&grupo_muscular=peito&tempo=30

Gerar sugestão sem gravar histórico ou descanso.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: grupo_muscular obrigatório; tempo obrigatório (30/60/90). Nível e objetivo são lidos do perfil autenticado.
- Corpo: nenhum.
- Status: 200,401,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=sugestao&grupo_muscular=peito&tempo=30", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "grupo_muscular": "peito",
    "tempo": "30",
    "nivel": "intermediario",
    "objetivo": "hipertrofia",
    "orientacao": {
      "nivel_label": "Intermediário",
      "objetivo_label": "Hipertrofia",
      "series": "3 séries",
      "repeticoes": "8–12 repetições",
      "descanso": "60–90 s"
    },
    "quantidade_alvo": 3,
    "quantidade_disponivel": 6,
    "exercicios": [
      {
        "id": 1,
        "nome": "Supino reto",
        "grupo_muscular": "peito",
        "descricao": "Exercício do catálogo.",
        "video_url": ""
      }
    ],
    "aviso": "Referência geral de organização do treino. Ajuste conforme sua condição e orientação profissional; só será registrada quando você confirmar que realizou."
  },
  "errors": {}
}
```

A lista ilustrada foi abreviada para um item. A resposta real terá até 3/5/6 itens conforme catálogo. A ordem pode variar; catálogo insuficiente não é preenchido com duplicatas. A orientação usa o perfil salvo: nível ajusta o volume sugerido e objetivo ajusta repetições/descanso. **Confirmar** usa POST treinos, com somente os IDs realmente realizados, não outro endpoint.

## GET api.php?resource=resumo

Consultar último treino/descanso e semana por grupo.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum.
- Corpo: nenhum.
- Status: 200,401,500; 405 para método incompatível.

```js
await chamada("api.php?resource=resumo", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "grupos": [
      {
        "grupo_muscular": "peito",
        "nome": "Peito",
        "quantidade": 1,
        "ultimo": "2026-10-19T18:00:00-03:00",
        "disponivel_em": "2026-10-21T18:00:00-03:00",
        "em_descanso": true,
        "status": "Em descanso",
        "treinado_semana": true
      }
    ],
    "agora": "2026-10-19T20:00:00-03:00"
  },
  "errors": {}
}
```

A lista ilustrada foi abreviada. O endpoint retorna os sete grupos, incluindo aqueles sem registros (datas null).

## GET api.php?resource=treinos&page=1

Listar treinos próprios com seus itens.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: page opcional (padrão 1).
- Corpo: nenhum.
- Status: 200,401,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=treinos&page=1", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "items": [
      {
        "id": 1,
        "usuario_id": 1,
        "grupo_muscular": "peito",
        "data_treino": "2026-10-19 18:00:00",
        "observacao": "",
        "criado_em": "2026-10-19 18:00:00",
        "exercicios": [
          {
            "id": 1,
            "exercicio_id": 1,
            "nome": "Supino reto",
            "descricao": "",
            "video_url": "",
            "descricao_livre": "",
            "series": null,
            "repeticoes": null,
            "carga": null
          }
        ]
      }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10
  },
  "errors": {}
}
```

## GET api.php?resource=treinos&id=1

Consultar treino próprio.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: id obrigatório nesta operação.
- Corpo: nenhum.
- Status: 200,401,404,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=treinos&id=1", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "id": 1,
    "usuario_id": 1,
    "grupo_muscular": "peito",
    "data_treino": "2026-10-19 18:00:00",
    "observacao": "",
    "criado_em": "2026-10-19 18:00:00",
    "exercicios": [
      {
        "id": 1,
        "exercicio_id": 1,
        "nome": "Supino reto",
        "descricao": "",
        "video_url": "",
        "descricao_livre": "",
        "series": null,
        "repeticoes": null,
        "carga": null
      }
    ]
  },
  "errors": {}
}
```

## POST api.php?resource=treinos

Registrar treino realmente realizado.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Não enviar id.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 201,400,401,403,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=treinos", "POST", {"grupo_muscular": "peito", "exercicios": [{"exercicio_id": 1}, {"descricao_livre": "Flexões em casa"}], "confirmado": true});
```

Resposta ilustrativa HTTP 201:
```json
{
  "success": true,
  "message": "Treino registrado.",
  "data": {
    "id": 1
  },
  "errors": {}
}
```

## PUT api.php?resource=treinos&id=1

Editar treino próprio e substituir itens.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: id obrigatório.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 200,400,401,403,404,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=treinos&id=1", "PUT", {"grupo_muscular": "peito", "exercicios": [{"exercicio_id": 1}, {"descricao_livre": "Flexões em casa"}], "confirmado": true});
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Treino atualizado.",
  "data": {
    "id": 1
  },
  "errors": {}
}
```

## DELETE api.php?resource=treinos&id=1

Excluir treino próprio e seus itens.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: id obrigatório.
- Corpo: nenhum.
- Status: 200,400,401,403,404,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=treinos&id=1", "DELETE");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Treino excluído.",
  "data": null,
  "errors": {}
}
```

## GET api.php?resource=exercicios

Listar catálogo.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: Nenhum.
- Corpo: nenhum.
- Status: 200,401,500; 405 para método incompatível.

```js
await chamada("api.php?resource=exercicios", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": [
    {
      "id": 1,
      "nome": "Supino reto",
      "grupo_muscular": "peito",
      "descricao": "Exercício do catálogo.",
      "video_url": ""
    }
  ],
  "errors": {}
}
```

## GET api.php?resource=exercicios&id=1

Consultar exercício.

- Autenticação: Sim. Perfil: usuario/admin.
- Parâmetros: id obrigatório nesta operação.
- Corpo: nenhum.
- Status: 200,401,404,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=exercicios&id=1", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "id": 1,
    "nome": "Supino reto",
    "grupo_muscular": "peito",
    "descricao": "Exercício do catálogo.",
    "video_url": ""
  },
  "errors": {}
}
```

## POST api.php?resource=exercicios

Cadastrar exercício/vídeo.

- Autenticação: Sim. Perfil: admin.
- Parâmetros: Não enviar id.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 201,400,401,403,409,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=exercicios", "POST", {"nome": "Supino reto", "grupo_muscular": "peito", "descricao": "Exercício do catálogo.", "video_url": ""});
```

Resposta ilustrativa HTTP 201:
```json
{
  "success": true,
  "message": "Exercício criado.",
  "data": {
    "id": 43
  },
  "errors": {}
}
```

## PUT api.php?resource=exercicios&id=1

Editar exercício/vídeo.

- Autenticação: Sim. Perfil: admin.
- Parâmetros: id obrigatório.
- Corpo: JSON mostrado na chamada abaixo; regras na seção de campos.
- Status: 200,400,401,403,404,409,413,415,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=exercicios&id=1", "PUT", {"nome": "Supino reto", "grupo_muscular": "peito", "descricao": "Exercício do catálogo.", "video_url": ""});
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Exercício atualizado.",
  "data": {
    "id": 1
  },
  "errors": {}
}
```

## DELETE api.php?resource=exercicios&id=43

Excluir exercício sem treinos vinculados.

- Autenticação: Sim. Perfil: admin.
- Parâmetros: id obrigatório.
- Corpo: nenhum.
- Status: 200,400,401,403,404,409,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=exercicios&id=43", "DELETE");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Exercício excluído.",
  "data": null,
  "errors": {}
}
```

## GET api.php?resource=usuarios&page=1

Listar usuários sem hashes.

- Autenticação: Sim. Perfil: admin.
- Parâmetros: page opcional (padrão 1).
- Corpo: nenhum.
- Status: 200,401,403,422,500; 405 para método incompatível.

```js
await chamada("api.php?resource=usuarios&page=1", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "items": [
      {
        "id": 1,
        "nome": "Pessoa Exemplo",
        "email": "pessoa@example.test",
        "perfil": "usuario",
        "criado_em": "2026-10-19 17:00:00"
      }
    ],
    "total": 1,
    "page": 1,
    "per_page": 20
  },
  "errors": {}
}
```

## GET api.php?resource=estatisticas

Consultar contagens gerais.

- Autenticação: Sim. Perfil: admin.
- Parâmetros: Nenhum.
- Corpo: nenhum.
- Status: 200,401,403,500; 405 para método incompatível.

```js
await chamada("api.php?resource=estatisticas", "GET");
```

Resposta ilustrativa HTTP 200:
```json
{
  "success": true,
  "message": "Operação concluída.",
  "data": {
    "usuarios": 1,
    "exercicios": 42,
    "registros": 1
  },
  "errors": {}
}
```

## Regras de conflito e autorização
409 indica e-mail/nome duplicado, exclusão de exercício em uso ou mudança de grupo de exercício usado. Para treino alheio/inexistente, leitura/edição/exclusão retornam 404. 422 retorna mensagem legível e, quando aplicável, errors por campo. Nenhum POST de sugestão existe: método incorreto retorna 405 e nunca cria treino.
