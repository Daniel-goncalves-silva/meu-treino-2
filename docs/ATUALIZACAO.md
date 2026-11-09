# Atualizar sem perder conta, dados ou Git

Esta atualização parte exatamente da estrutura do ZIP anterior: `usuarios`, `exercicios` e `treinos` com um exercício por registro. **Não apague o banco nem reimporte o schema novo.**

## 1. Cópias de segurança
1. XAMPP: Apache e MySQL iniciados. Saia do Meu Treino e feche as abas do aplicativo.
2. Abra `http://localhost/phpmyadmin`, selecione **meu_treino** na esquerda → **Exportar** → método Rápido, formato SQL → Exportar. Guarde o arquivo fora da pasta que será sobrescrita.
3. Copie a pasta atual do projeto para outra pasta de segurança. Inclua seu `.env` e arquivos locais. A pasta `.git` permanece no repositório original; não é fornecida neste ZIP.
4. Não use a aplicação nem permita registros durante a migração. Isso evita escrita pela versão antiga enquanto a estrutura muda.

## 2. Importar somente a migração
1. No phpMyAdmin, selecione o banco **meu_treino**.
2. Clique em **Importar** → **Escolher arquivo**.
3. Selecione **`database/migrations/002_registro_rapido.sql`** do novo ZIP extraído.
4. Mantenha SQL e UTF-8 e clique em Executar/Importar.
5. Aguarde a mensagem `Migração 002 concluída...` (ou sucesso geral do phpMyAdmin). Se aparecer erro, não prossiga usando o app: guarde a mensagem e confira o estado do banco.
6. Verifique no SQL:
```sql
SELECT * FROM schema_migrations WHERE versao='002_fluxos';
SELECT id,nome,email,perfil FROM usuarios;
SELECT id,usuario_id,grupo_muscular,data_treino FROM treinos ORDER BY id;
SELECT treino_id,exercicio_id,descricao_livre,series,repeticoes,carga
FROM treino_exercicios ORDER BY treino_id,id;
```
A versão deve aparecer. Sua conta deve manter o mesmo ID/e-mail/perfil. Cada treino antigo terá um item com exercício, séries, repetições e carga antigos. Não publique hashes/senhas em evidências.

**Caso o nome do seu banco não seja `meu_treino`:** ajuste apenas `USE meu_treino;` no início de uma cópia do SQL para o nome correto; mantenha `.env` apontando para esse mesmo banco. Não use busca/substituição indiscriminada nos nomes das tabelas/classes.

## 3. Copiar os arquivos novos
1. No GitHub Desktop, confirme seu repositório `meu-treino-2` e branch `desenvolvimento`. Preserve alterações locais próprias antes de sobrescrever; este ZIP parte da versão entregue anteriormente, não inclui edições que você tenha feito depois e não enviado.
2. Copie **o conteúdo** da pasta `Meu-Treino-2` extraída para a pasta atual do projeto. Aceite substituir arquivos com os mesmos caminhos.
3. **Não exclua a pasta atual inteira. Não apague `.git`. Não sobrescreva `.env`.** O pacote não contém esses arquivos de dados locais.
4. Se seu repositório e a pasta em `htdocs` forem separados, atualize ambas com o mesmo conteúdo; o `.env` de execução fica na pasta do XAMPP.
5. Abra `http://localhost/Meu-Treino-2/`, Ctrl+F5, entre com a conta e senha existentes.
6. Verifique registro rápido, sugestão sem gravar, confirmação, histórico, edição/exclusão e perfil. Só então faça commits reais das mudanças revisadas, se desejar.

## Estrutura alterada
| Tabela | Alteração | Dados existentes |
|---|---|---|
| usuarios | adiciona `nivel`, `objetivo`, `tempo_disponivel` | ID/nome/e-mail/hash/perfil/criado_em não são alterados |
| exercicios | adiciona `video_url`; complementa catálogo até 6 nomes por grupo | exercícios existentes não são sobrescritos; URL inicia vazia |
| treinos | adiciona `grupo_muscular` e índice usuário/grupo/data; torna campos antigos opcionais | IDs, dono, data, observação e criação são mantidos |
| treino_exercicios | nova tabela, relação 1:N com treino, FK catálogo opcional, detalhes opcionais | copia cada exercício antigo e seus detalhes para um item |
| schema_migrations | nova tabela com versão aplicada | impede reaplicação/duplicação após conclusão |

As colunas antigas `treinos.exercicio_id/series/repeticoes/carga` são mantidas como cópia histórica e ficam nulas nos treinos novos. A FK antiga `fk_treino_exercicio` é removida **depois de conferir a cópia**; a FK ativa passa a ser `treino_exercicios.exercicio_id`. A aplicação só lê/escreve os itens da nova tabela. Não edite as colunas legadas nem volte a executar a versão antiga sobre esse banco.

## O que a migração protege e seus limites
- Não contém DROP TABLE, DROP DATABASE ou exclusão de usuário/treino.
- Confere exercício/séries/repetições/carga copiados antes de marcar a versão.
- Pode ser executada novamente após conclusão: informa que já foi aplicada e não duplica dados.
- A DDL de MySQL/MariaDB (ALTER/CREATE) faz commit automático; portanto a migração inteira não é uma transação única. A cópia dos dados usa transação. Se a conexão cair, o banco pode ficar parcialmente atualizado.
- Após interrupção, mantenha o app fechado. Em muitos casos reimportar o mesmo arquivo retoma as etapas existentes; se houver divergência/estrutura inesperada, pare e revise a mensagem antes de tentar novamente. Não execute schema.sql para “consertar”.
- O arquivo usa uma procedure temporária e `DELIMITER`, aceitos pelo importador SQL do phpMyAdmin. A conta de banco precisa de ALTER/CREATE/CREATE ROUTINE e permissão de execução; no XAMPP local normalmente é root. A procedure é removida ao final.

## Voltar à versão anterior se necessário
Use o backup **do banco e do código juntos**. Prefira restaurar o dump em outro banco local de recuperação e apontar uma cópia da versão antiga para ele, preservando o banco migrado para diagnóstico. Não basta voltar o código antigo mantendo o banco novo. Qualquer registro feito depois da exportação não estará no backup; preserve-o antes de decidir restaurar.

## Validação já realizada
Uma instalação MariaDB de testes foi criada com o schema anterior, uma conta admin e dois treinos. A migração preservou exatamente os campos de identificação/hash/perfil/horário da conta e IDs/datas/observações dos treinos; os dois itens mantiveram exercício e detalhes. Uma segunda execução não duplicou dados. Integração HTTP passou no banco novo e no migrado. Os arquivos de saída estão em `docs/evidencias_preparacao`.

Isso não significa que seu banco real foi acessado. Faça sua exportação e a conferência acima no XAMPP.
