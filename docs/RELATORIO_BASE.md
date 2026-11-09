# Meu Treino 2 — Trabalho Individual II de SCOM
**Autor:** Daniel Gonçalves da Silva  
**Instituição:** UNESP — Instituto de Ciência e Tecnologia de Sorocaba  
**Curso:** Engenharia de Controle e Automação  
**Versão:** evolução com registro rápido e sugestões confirmadas  
**Repositório:** [INSERIR LINK REAL]  
**Vídeo:** [INSERIR LINK/LOCAL]

> RELATÓRIO-BASE. Há resultados reais de testes em ambiente Linux de preparação. Ainda faltam capturas do navegador/XAMPP, revisão do aluno, medições, histórico Git e anexos finais. O banco real do aluno não foi acessado.

## 1. Introdução
O Meu Treino organiza registros de musculação e sugestões simples de exercícios. O sistema busca responder o que o usuário treinou e que combinação de exercícios pode organizar para hoje. A implementação utiliza PHP e MySQL/MariaDB, executável no XAMPP, com autenticação, autorização, persistência relacional e API.

## 2. Evolução em relação ao Trabalho 1
No Trabalho I, o registro era realizado pela marcação do grupo muscular. Na primeira implementação do Trabalho II, foi criado um cadastro mais detalhado com exercício, séries, repetições e carga. Durante a evolução da interface, verificou-se que exigir todas essas informações tornava o registro mais demorado. O fluxo foi então redesenhado para permitir um registro rápido, mantendo detalhes adicionais como opcionais. Também foi criada uma funcionalidade separada para montagem de treinos, destinada ao usuário que deseja sugestões de exercícios.

Essa foi uma decisão de projeto/usabilidade durante o desenvolvimento, não uma pesquisa com usuários. Foram mantidos sete grupos, imagens originais, identidade azul/branca, responsividade e referência organizacional de 48 horas. Na nova modelagem, um treino contém vários exercícios. Gerar sugestão não grava histórico; somente a confirmação de realização faz isso.

[EVIDÊNCIA A SER INSERIDA: comparação visual e histórico real das versões.]

## 3. Requisitos funcionais
Cadastro/login/logout; registro rápido por grupo com itens do catálogo e/ou texto livre; detalhes opcionais; montagem de sugestões por grupo e tempo; confirmação com seleção do que foi realizado; resumo semanal e descanso; histórico próprio com edição/exclusão; preferências pessoais; administração de exercícios e links de vídeo; consulta administrativa de usuários/totais.

Um treino contém 1–20 itens de um grupo, incluindo no máximo um texto livre. Data/hora são automáticas na criação e mantidas na edição. Apenas registros confirmados contam no histórico, semana e descanso.

## 4. Arquitetura do sistema
A arquitetura em camadas da versão anterior foi preservada. Views PHP, CSS e JavaScript formam a interface. ApiController coordena requisições. Services contêm regras: WorkoutService para registros, SuggestionService para sugestões, RestService para 48h e ProfileService para preferências. Repositories isolam PDO/SQL. Middleware trata sessão/CSRF/autorização; Helpers centralizam configuração, validação, JSON, erros e logs.

SuggestionService só recebe o repositório de exercícios e não possui acesso à gravação de treinos. POST/PUT de treino exige confirmado=true, com dono obtido da sessão. O repositório salva treino e itens dentro de transação, evitando gravação parcial em falha. Consulte ARQUITETURA.md.

## 5. Modelo de dados
usuarios possui identificação, hash, perfil e preferências. exercicios possui nome, grupo, descrição e video_url. treinos possui usuário, grupo, data e observação. treino_exercicios relaciona vários itens ao treino, com exercício do catálogo ou descrição livre e detalhes opcionais. schema_migrations registra a versão aplicada.

Todas as tabelas têm PK. E-mail/nome de exercício e pares treino/exercício têm UNIQUE quando adequado. FKs protegem dono, treino e catálogo. A exclusão do treino remove seus itens por CASCADE; exercício utilizado tem exclusão bloqueada. Há índices usuário/data e usuário/grupo/data. Séries/repetições/carga podem ser NULL; quando fornecidas, são validadas pelo backend e por CHECKs.

A migração 002_registro_rapido.sql preserva usuários, hashes, perfis, IDs e horários e copia os detalhes antigos para treino_exercicios. Quatro colunas antigas ficam como cópia histórica, sem uso pela aplicação nova. Instalação nova usa schema.sql. A migração usa transação na cópia, mas ALTER/CREATE fazem commit automático no MySQL; por isso exige exportação de segurança e ausência de uso do aplicativo durante a atualização.

[EVIDÊNCIA LOCAL A INSERIR: estrutura do phpMyAdmin após migração e conta anterior preservada, sem expor hash.]

## 6. Estratégia de autenticação
password_hash/password_verify continuam em uso. Login regenera ID da sessão e token CSRF. Cookie HttpOnly/SameSite=Lax, Secure com HTTPS; expiração após 30 minutos sem atividade. Logout destrói sessão e cookie. Perfil é consultado no banco a cada requisição protegida.

| Ação | Visitante | Usuário | Admin |
|---|---|---|---|
| Cadastro/login/token | Sim | Sim | Sim |
| Catálogo/sugestão/perfil próprio | Não | Sim | Sim |
| CRUD de treino próprio | Não | Sim | Sim |
| Treino de outra conta | Não | Não | Não |
| CRUD de exercício/vídeo | Não | Não | Sim |
| Usuários/totais gerais | Não | Não | Sim |

Promoção de admin ocorre somente pelo utilitário CLI sobre conta existente. Não há senha padrão.

## 7. Estratégia de segurança
Consultas parametrizadas evitam interpretar entradas como SQL. Validação verifica tipos, tamanhos, limites, grupo e relacionamento dos itens. XSS é mitigado por htmlspecialchars no PHP e textContent no JS, com CSP adicional. CSRF é obrigatório em escrita, inclusive login/cadastro/logout e perfil. Rotas administrativas exigem admin no servidor. Leitura/edição/exclusão de treino restringe ID e usuário autenticado; acesso alheio retorna 404.

Links de vídeo precisam de HTTPS sem credenciais/controles. São abertos com noopener/noreferrer; não se afirma que um link sintaticamente válido exista ou tenha conteúdo adequado. O catálogo entregue deixa links vazios. Configurações ficam em .env fora do Git; diretórios privados continuam protegidos no Apache.

## 8. Endpoints implementados
A API mantém csrf, cadastro, login, logout, eu, resumo, CRUD de treinos, CRUD de exercícios, usuários e estatísticas. Acrescenta GET sugestao e GET/PUT perfil. São 21 operações documentadas, contando leituras por lista e ID. POST treinos registra tanto fluxo rápido quanto sugestão confirmada. GET sugestao é somente leitura.

Respostas contêm success/message/data/errors, com status apropriados 200/201/400/401/403/404/405/409/413/415/422/500. API.md documenta método, URL, perfil, parâmetros, corpos, exemplos e erros. Exemplos são contratos ilustrativos, não evidências.

## 9. Estratégia de testes
A suíte de regras de negócio foi ampliada, mantendo os cenários anteriores de autenticação, validação, posse, perfil e CSRF. Inclui detalhes opcionais, vários exercícios, texto livre, 48h antes/no limite, semana, sugestão sem gravação, confirmação parcial, preservação de horário, perfil e URL de vídeo. Os cenários de data manual passaram a verificar sua rejeição, pois o horário agora é automático.

Resultado real da preparação: **48 testes aprovados, zero falhas**, PHP 8.3.6. Integração real com servidor HTTP PHP e MariaDB 10.11.14: **44 verificações aprovadas no schema novo e 44 no banco migrado**, zero falhas. A sintaxe dos arquivos PHP e JavaScript também foi verificada. Saídas completas estão em evidencias_preparacao.

O ensaio de migração usou uma conta admin e dois treinos anteriores. Comparações exatas confirmaram preservação de identificação, hash, perfil, datas, observações, exercícios e detalhes. Reexecutar a migração não duplicou dados. Esse ambiente é Linux isolado, não o XAMPP do aluno.

## 10. Resultados dos testes de segurança
Os testes de integração confirmaram 401 para API sem login, 403 para perfil insuficiente e CSRF inválido, 404 para leitura/edição/exclusão de treino alheio e 422 para ID malformado com SQL Injection. Logout invalidou a sessão e o token antigo. Usuário comum não acessou a página administrativa; admin acessou. O teste unitário de escaping passou e a URL javascript foi rejeitada.

Esses resultados não substituem verificação de XSS em navegador real. O navegador de testes não iniciou neste ambiente; não foram produzidas capturas falsas. Faça o roteiro TESTES_SEGURANCA.md, registre conteúdo literal no DOM, ausência de execução e capturas reais de pelo menos cinco cenários.

| Teste | Resultado da preparação | Evidência local ainda necessária |
|---|---|---|
| SQLi em ID | HTTP 422 | Captura e teste de texto no banco |
| Sem login | HTTP 401 / página 302 | Captura |
| Perfil insuficiente | HTTP 403 | Captura |
| CSRF/token encerrado | HTTP 403 | Captura |
| Treino de outra conta | HTTP 404 | Captura de registro preservado |
| XSS | Escaping unitário aprovado | Navegador/DOM e captura |

## 11. Logs e observabilidade
Logger registra timestamp e eventos de login, falha, criação, edição, exclusão, logout, acesso administrativo negado e erro interno. Não grava senha ou token. Logs locais não são versionados. As saídas de preparação identificam as verificações; inclua amostra de logs reais da sua demonstração após revisar dados sensíveis.

[EVIDÊNCIA A INSERIR: amostra local com os quatro eventos obrigatórios da rubrica.]

## 12. Desempenho e limitações
Paginação limita histórico a dez treinos e usuários a vinte. Índices apoiam filtros; imagens WebP originais usam lazy loading. Há consultas dos itens por treino, limitadas pela paginação; catálogo foi mantido pequeno. DESEMPENHO.md orienta medições e EXPLAIN; nenhuma nota Lighthouse ou tempo de resposta foi inventado.

Sugestões são combinações simples de até 3/5/6 itens para tempos aproximados; não são prescrição médica. Se o catálogo for insuficiente, não há duplicação para preencher quantidade. O tempo define a quantidade de exercícios; o nível ajusta o volume sugerido de séries; e o objetivo ajusta faixas gerais de repetições e descanso. Não há cálculo de carga ou prescrição individualizada. Não há rate limiting, recuperação de senha ou login persistente. A interface usa Brasília. Conferência visual e medições no XAMPP ainda são necessárias.

## 13. Uso de IA generativa
ChatGPT/Codex auxiliou substancialmente a implementação inicial e esta evolução: modelagem, migração, PHP/SQL, interface, testes e documentação. O aluno definiu o fluxo desejado e deve registrar suas revisões e validações. Não houve pesquisa com usuários nem commits fabricados.

| Ferramenta | Ajuda recebida | Revisões do aluno | Avaliação crítica |
|---|---|---|---|
| ChatGPT/Codex | Código, migração, testes e documentação | [PREENCHER] | [PREENCHER] |

Detalhamento em USO_IA.md. Não apresentar como desenvolvimento sem assistência.

## 14. Conclusões
A evolução separa planejamento de registro real e simplifica o uso, mantendo requisitos acadêmicos e segurança. Testes automatizados e ensaio de migração apresentaram resultados positivos no ambiente de preparação. A conclusão final sobre a demonstração depende da execução no XAMPP, revisão visual, evidências locais e preenchimento dos anexos. Acrescente aqui os resultados reais da sua validação.

## Anexos e pendências da entrega
- SQL de criação e migração; estrutura real do banco.
- Documentação completa da API.
- Resultados automatizados: arquivos reais de evidencias_preparacao e saídas locais.
- Capturas de pelo menos cinco testes de segurança no navegador.
- Amostra de logs, revisada para não expor dados sensíveis.
- Histórico real git log --oneline --graph, link do repositório e marcos.
- Checklist atualizado com evidências; vídeo de demonstração.

Este PDF é uma base revisável. Complete os anexos e seus dados antes de apresentar o relatório como final.
