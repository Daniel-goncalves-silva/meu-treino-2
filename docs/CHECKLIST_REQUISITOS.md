# Checklist completo — requisitos e rubrica

Releitura do PDF oficial realizada na preparação. **Não é atribuição de nota.** “Código presente” significa que há implementação, não que o teste passou. PHP/MariaDB foram executados nesta evolução em ambiente Linux isolado; consulte VERIFICACAO_ENTREGA.md. Atualize a coluna de evidência com arquivos reais após testar no XAMPP. Não marque todos como concluídos apenas por existir código.

| Requisito | Implementado? | Arquivo/local | Como testar | Evidência necessária |
|---|---|---|---|---|
| Obrigatório 1 — arquitetura documentada | Código presente; validar localmente | ARQUITETURA.md; src/ | Explicar fluxo de salvar treino | Captura/saída real pendente |
| Obrigatório 2 — entidade persistente e CRUD | Código presente; validar localmente | WorkoutService; WorkoutRepository; schema.sql | Criar, ler, atualizar, excluir e consultar banco | Captura/saída real pendente |
| Obrigatório 3 — sessão/hash/logout/rotas | Código presente; validar localmente | AuthService; Middleware/Session; Auth | Login, logout e teste sem sessão | Captura/saída real pendente |
| Obrigatório 4 — dois perfis e matriz | Código presente; validar localmente | ARQUITETURA.md; RELATORIO_BASE.md; Auth::admin | Testar usuário e admin | Captura/saída real pendente |
| Obrigatório 5 — tipos, tamanho e sanitização | Código presente; validar localmente | Validator; Response::escape; script.js | Enviar arrays, limites, HTML e campos inválidos | Captura/saída real pendente |
| Obrigatório 6 — SQLi/XSS/CSRF/acesso | Código presente; validar localmente | Repositories; Csrf; Auth; TESTES_SEGURANCA.md | Executar roteiro e anexar prints | Captura/saída real pendente |
| Obrigatório 7 — erros/status/debug | Código presente; validar localmente | ErrorHandler; Response; ApiController | Comparar erro em development/production | Captura/saída real pendente |
| Obrigatório 8 — API documentada | Código presente; validar localmente | API.md | Comparar cada operação documentada com execução | Captura/saída real pendente |
| Obrigatório 9 — pelo menos cinco testes válidos/inválidos | Automação aprovada; repetir no XAMPP | tests/run.php (48 testes) | Executar runner PHP e verificar exit code | docs/evidencias_preparacao |
| Obrigatório 10 — logs com timestamp | Código presente; validar localmente | Logger; ApiController; WorkoutService | Gerar login válido/inválido, criação e erro interno | Captura/saída real pendente |
| A — Separação frontend/backend | Implementado; revisão estática | Views/assets vs Controllers/Services | Inspecionar estrutura e chamadas fetch | Código/documentação fornecidos; apresentação local |
| A — Diretórios organizados | Implementado; revisão estática | config, src, public, database, docs, tests, logs | Ver árvore do README | Código/documentação fornecidos; apresentação local |
| A — Camada de acesso isolada | Implementado; revisão estática | src/Repositories | Localizar consultas SQL | Código/documentação fornecidos; apresentação local |
| A — Configuração separada | Implementado; revisão estática | config/bootstrap.php; Config; .env.example | Alterar .env local | Código/documentação fornecidos; apresentação local |
| A — Sensíveis fora do código | Implementado; revisão estática | .env ignorado; senha admin não fixa | Revisar Git antes de commit | Código/documentação fornecidos; apresentação local |
| A — Script SQL fornecido | Implementado; revisão estática | database/schema.sql | Importar banco novo | Código/documentação fornecidos; apresentação local |
| A — README de execução | Implementado; revisão estática | README.md | Seguir roteiro do zero | Código/documentação fornecidos; apresentação local |
| A — Arquitetura documentada | Implementado; revisão estática | docs/ARQUITETURA.md | Comparar texto com código | Código/documentação fornecidos; apresentação local |
| A — Nomenclatura consistente | Implementado; revisão estática | Namespaces App e classes por pasta | Revisar arquivos | Código/documentação fornecidos; apresentação local |
| A — Ausência de temporários | Implementado; revisão estática | ZIP final | Listar conteúdo | Código/documentação fornecidos; apresentação local |
| A — Ausência de código morto evidente | Implementado; revisão estática | Views roteadas e helpers usados | Revisão estática e navegação | Código/documentação fornecidos; apresentação local |
| A — Namespaces/módulos consistentes | Implementado; revisão estática | src/**/*.php; autoloader | Rodar PHP/autoload | Código/documentação fornecidos; apresentação local |
| A — Erros centralizados | Implementado; revisão estática | ErrorHandler; HttpError; Response | Provocar erro controlado | Código/documentação fornecidos; apresentação local |
| A — Componentes reutilizados | Implementado; revisão estática | Validator; Csrf; Logger; table/bindForm no JS | Inspecionar chamadas | Código/documentação fornecidos; apresentação local |
| A — Estrutura coerente com padrão | Implementado; revisão estática | src/ e ARQUITETURA.md | Explicar camadas | Código/documentação fornecidos; apresentação local |
| B — PK em todas as tabelas (3) | Código presente; validar localmente | schema.sql: id / schema_migrations.versao | Inspecionar phpMyAdmin | Captura/saída real pendente |
| B — FKs corretas (3) | Código presente; validar localmente | fk_treino_usuario; fk_item_treino/exercicio | Tentar treino sem usuário/exercício válido | Captura/saída real pendente |
| B — Constraints adequadas (3) | Código presente; validar localmente | UNIQUE/NOT NULL/ENUM/CHECK | Tentar duplicata/nulo/limite inválido | Captura/saída real pendente |
| B — Tipos apropriados (2) | Código presente; validar localmente | INT, SMALLINT, DECIMAL, DATETIME | Ver estrutura | Captura/saída real pendente |
| B — Índices relevantes (2) | Código presente; validar localmente | idx_treino_usuario_data e demais | EXPLAIN conforme DESEMPENHO.md | Captura/saída real pendente |
| B — Script executável sem erros (2) | Executado em MariaDB 10.11.14 | schema.sql | Importação em banco vazio | evidencias_preparacao/migracao.txt |
| C1 — Prepared statements (5) | Código presente; validar localmente | Repositories | Testes SQL Injection | Captura/saída real pendente |
| C1 — Escaping HTML (3) | Código presente; validar localmente | Response::escape; textContent | Testes XSS | Captura/saída real pendente |
| C1 — CSRF (3) | Código presente; validar localmente | Middleware/Csrf; ApiController | POST/PUT/DELETE sem token | Captura/saída real pendente |
| C1 — Controle de acesso (4) | Código presente; validar localmente | Auth; findOwned; queries por usuario_id | Testes de perfil e posse | Captura/saída real pendente |
| C2 — evidência: SQL Injection | Automação aprovada; captura pendente | TESTES_SEGURANCA.md | Executar e capturar | Prints reais pendentes |
| C2 — evidência: XSS | Roteiro pronto; comprovação pendente | TESTES_SEGURANCA.md | Executar e capturar | Prints reais pendentes |
| C2 — evidência: Sem login | Automação aprovada; captura pendente | TESTES_SEGURANCA.md | Executar e capturar | Prints reais pendentes |
| C2 — evidência: Perfil insuficiente | Automação aprovada; captura pendente | TESTES_SEGURANCA.md | Executar e capturar | Prints reais pendentes |
| C2 — evidência: Sessão/token inválidos | Automação aprovada; captura pendente | TESTES_SEGURANCA.md | Executar e capturar | Prints reais pendentes |
| D — Hash seguro (4) | Automação aprovada; repetir no XAMPP | AuthService | Inspecionar hash; testar senha | docs/evidencias_preparacao |
| D — Login funcional (3) | Automação aprovada; repetir no XAMPP | login view/API/AuthService | Credenciais corretas/incorretas | docs/evidencias_preparacao |
| D — Logout funcional (2) | Automação aprovada; repetir no XAMPP | Session::logout | Reutilizar acesso após sair | docs/evidencias_preparacao |
| D — Proteção de rotas (3) | Automação aprovada; repetir no XAMPP | public/index.php; API | URL direta sem login | docs/evidencias_preparacao |
| D — Perfis funcionais (3) | Automação aprovada; repetir no XAMPP | Auth::admin; tools/promover-admin.php | Comparar contas | docs/evidencias_preparacao |
| E — Métodos HTTP (2) | Código presente; validar localmente | ApiController routes | GET/POST/PUT/DELETE e método inválido | Captura/saída real pendente |
| E — Status adequados (2) | Automação aprovada; repetir no XAMPP | HttpError; Response | Ver Network e integração | docs/evidencias_preparacao |
| E — JSON consistente (2) | Automação aprovada; repetir no XAMPP | Response::json | Comparar sucessos/erros | docs/evidencias_preparacao |
| E — Documentação completa (4) | Código presente; validar localmente | API.md | Conferir 21 operações documentadas | Captura/saída real pendente |
| F — ≥5 testes executáveis (5) | Automação aprovada; repetir no XAMPP | tests/run.php | Executar 48 testes PHP | docs/evidencias_preparacao |
| F — casos inválidos (3) | Automação aprovada; repetir no XAMPP | tests/run.php | Ver cenários de rejeição | docs/evidencias_preparacao |
| F — evidência da execução (2) | Executado na preparação; repetir no XAMPP | tests/evidencias (criar localmente) | Salvar saída real do runner | docs/evidencias_preparacao |
| G — Commits significativos e logs descritivos (3) | Responsabilidade do aluno; nenhum Git fabricado | Repositório do aluno; README | Revisar mudanças e commitar após testes | git log --oneline --graph real |
| G — Mensagens padronizadas (1) | Responsabilidade do aluno; nenhum Git fabricado | Repositório do aluno; README | Usar mensagens que descrevam mudanças reais | git log --oneline --graph real |
| G — Evolução incremental identificável (2) | Responsabilidade do aluno; nenhum Git fabricado | Repositório do aluno; README | Mostrar histórico real entre Trabalhos I e II | git log --oneline --graph real |
| G — Branches/tags para marcos (4) | Responsabilidade do aluno; nenhum Git fabricado | Repositório do aluno; README | Mostrar main/desenvolvimento e marcos reais | git log --oneline --graph real |
| Bônus — .env | Implementado; validar localmente | Config; .env.example | Configurar credenciais localmente | Não inferir bônus sem validação |
| Bônus — Paginação | Implementado; validar localmente | WorkoutRepository/UserRepository | Criar >10 treinos / >20 usuários de teste | Não inferir bônus sem validação |
| Bônus — Testes de integração | 44 verificações passaram em cada banco | tests/integration.php | Executar com Apache/MySQL | Não inferir bônus sem validação |
| Bônus — Migrações | Implementada e ensaiada | migrations/002_registro_rapido.sql | Importar após backup | evidencias_preparacao/migracao.txt |
| Bônus — Rate limiting | Opcional não implementado | Não incluído | Não aplicável | Não inferir bônus sem validação |
| Relatório — 1. Introdução | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 2. Evolução Trabalho I | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 3. Requisitos funcionais | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 4. Arquitetura | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 5. Modelo de dados | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 6. Autenticação | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 7. Segurança | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 8. Endpoints | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 9. Testes | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 10. Resultados de segurança | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 11. Logs | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 12. Desempenho e limitações | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 13. Uso de IA | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Relatório — 14. Conclusões | Base preparada; conclusão depende dos testes | RELATORIO_BASE.md/.pdf | Revisar seção e preencher evidências | Texto final/evidências pendentes |
| Anexo — Script SQL/exportação | Importação executada em MariaDB; repetir no XAMPP | database/schema.sql | Anexar script e estrutura importada | Arquivo real a anexar |
| Anexo — Documentação API | Fornecida | docs/API.md | Anexar ao relatório final | Arquivo real a anexar |
| Anexo — Resultado dos testes | Saídas reais fornecidas | tests/evidencias/ | Rodar e anexar | Arquivo real a anexar |
| Anexo — Capturas de segurança | Pendente | TESTES_SEGURANCA.md | Executar e capturar ≥5 testes | Arquivo real a anexar |
| Anexo — Amostra de logs | Pendente | logs/app.log | Gerar e revisar dados sensíveis | Arquivo real a anexar |
| Anexo — Histórico Git | Pendente | Repositório real | git log --oneline --graph | Arquivo real a anexar |
| Anexo — Checklist preenchido | Esta versão distingue código/evidência | CHECKLIST_REQUISITOS.md | Atualizar com comprovações | Arquivo real a anexar |
| Entrega — código-fonte completo | Fornecido | ZIP entregue | Instalar no XAMPP | Validação local pendente |
| Entrega — relatório PDF final (ausência: −20) | Rascunho fornecido; não final | RELATORIO_BASE.pdf | Preencher e exportar versão final | PDF final com anexos pendente |
| Entrega — vídeo (ausência: −10) | Não fornecido | Vídeo a gravar | Confirmar formato/duração com professor | Vídeo real pendente |
| Entrega — link Git (ausência: −15) | Não acessado/modificado nesta entrega | Repositório meu-treino-2 do aluno | Informar URL real e publicar commits revisados | Link/histórico real |
| Penalidade — IA não declarada (até zero) | Uso declarado; revisão do aluno pendente | USO_IA.md; relatório seção 13 | Completar revisão crítica real | Registro de uso/revisões |
| Penalidade — credenciais hardcoded (−10) | Sem senha padrão; .env real não entregue | .env.example; .gitignore; promoção CLI | Inspecionar arquivos a commitar | Revisão real do Git |
| Penalidade — não executa localmente (−15) | Executado em Linux; validar XAMPP | README; testes | Executar instalação completa | Demonstração real pendente |


## Evolução solicitada: registro rápido e sugestões
| Requisito | Implementado? | Arquivo/local | Como testar | Evidência necessária |
|---|---|---|---|---|
| Registro rápido sem números obrigatórios | Sim; testes aprovados | Views/registrar, Validator, WorkoutService | Marcar dois exercícios e salvar sem detalhes | Clique real no XAMPP |
| Texto livre opcional | Sim; teste aprovado | Validator::workout | Registrar apenas texto livre | Histórico |
| Um treino com vários itens | Sim; integração aprovada | treino_exercicios, WorkoutRepository | Consultar dois itens no mesmo treino | Saída integração + captura |
| Sugestão não grava histórico | Sim; testes aprovados | SuggestionService, GET sugestao | Gerar repetidamente e comparar histórico | Saídas e clique real |
| Confirmação com desmarcação | Implementada; gravação testada via API | montar.php, script.js, POST treinos | Desmarcar e confirmar na tela | Navegador pendente |
| 48h antes/no limite | Sim; testes aprovados | RestService | Testes determinísticos | unitarios.txt |
| Edição preserva horário | Sim; testes aprovados | WorkoutService, Repository | Editar e comparar data | Saídas reais |
| Perfil e tempo habitual | Sim; persistência testada | ProfileService; perfil.php | Salvar e abrir Montar treino | Clique real |
| Vídeo HTTPS opcional | Sim; validação testada | Validator::video; admin; JS | Vazio, inválido, link real verificado | Navegador pendente |
| Migração preserva conta e treinos | Sim; ensaio aprovado | 002_registro_rapido.sql | Exportar/importar e conferir | migracao.txt + XAMPP |
| Migração não duplica ao repetir | Sim; ensaio aprovado | schema_migrations | Reimportar em banco de teste | migracao.txt |
| Imagens/identidade preservadas | Bytes das imagens iguais | public/assets | Comparar e ver no navegador | Revisão visual pendente |
| Não criar commits por conta do aluno | Respeitado | ZIP sem .git | Revisar GitHub Desktop | Histórico real do aluno |

As linhas da rubrica que pedem capturas locais, relatório final, vídeo, Git e desempenho continuam pendentes desses materiais. Aprovação dos testes de preparação não garante nota nem substitui a demonstração local.
