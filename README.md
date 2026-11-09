# Meu Treino 2 — registro rápido e sugestões
Daniel Gonçalves da Silva · SCOM · UNESP Sorocaba

Evolução direta da versão PHP/MySQL já entregue. Preserva autenticação, camadas, API, CRUD, segurança, administração, imagens e identidade azul/branca.

**Você já possui o banco? Comece por `docs/ATUALIZACAO.md`. Não importe `schema.sql` sobre o banco atual e não apague seu banco.**

## O que mudou
- **Registrar treino:** escolha grupo, marque exercícios e/ou escreva o que fez. Data/hora automáticas. Séries, repetições, carga e observação são opcionais, dentro de “Adicionar detalhes”.
- **Montar treino:** grupo + tempo (30 min, 1h, mais de 1h) → até 3, 5 ou 6 exercícios do catálogo, sem repetição. O perfil acrescenta uma referência simples: nível ajusta o volume sugerido e objetivo ajusta repetições/descanso. Não há IA externa. Pode gerar outra sugestão.
- **Planejamento não é registro:** gerar a sugestão não grava treino nem inicia descanso. “Registrar este treino” abre outra etapa; só “Confirmar treino”, após desmarcar o que não fez, salva no banco.
- **Painel:** último treino, “Em descanso”, data de disponibilidade e “Pode treinar novamente” após 48h. A regra é uma referência organizacional, não orientação médica. A semana começa segunda-feira.
- **Histórico:** um card por treino, com vários exercícios, edição e exclusão. Editar mantém o horário original; excluir recalcula o resumo com os registros restantes.
- **Perfil:** nível, objetivo e tempo habitual. O tempo preenche Montar treino; o nível ajusta o volume de séries sugerido e o objetivo ajusta faixas gerais de repetições/descanso. São referências organizacionais, não prescrição individualizada.
- **Admin:** mantém CRUD de exercícios e acrescenta `video_url`. Sem link válido, nenhum botão de vídeo aparece. Nenhuma URL de tutorial foi inventada.

## Atualizar a instalação existente
1. Exporte seu banco no phpMyAdmin e copie a pasta atual como segurança.
2. Saia do aplicativo e feche suas abas; deixe Apache e MySQL ligados para usar phpMyAdmin. Não use o app durante a migração.
3. Selecione `meu_treino` → **Importar** → `database/migrations/002_registro_rapido.sql` → Executar.
4. Aguarde a mensagem de conclusão. A conta e senha existentes continuam; não é preciso cadastrar novamente.
5. Copie o conteúdo da pasta `Meu-Treino-2` do novo ZIP para a pasta atual, aceitando a substituição dos arquivos correspondentes. Preserve **`.git`**, **`.env`** e seus arquivos de evidências locais. Não apague a pasta inteira. O ZIP não contém `.git`, `.env` real ou logs de uso.
6. Abra `http://localhost/Meu-Treino-2/`, use Ctrl+F5 e faça login com sua conta.

Detalhes, verificação, interrupção e retorno ao backup: `docs/ATUALIZACAO.md`. A migração é identificada em `schema_migrations` e não duplica dados quando já aplicada. Mudanças de estrutura MySQL fazem commit automático; o backup continua necessário.

## Instalação nova (sem banco anterior)
Pré-requisitos: XAMPP com PHP 8.0+, Apache 2.4 e MariaDB 10.4+ ou MySQL 8.0.16+, `pdo_mysql`; para integração, `curl`. Sem Composer, Docker ou framework.
1. Extraia em `C:\xampp\htdocs\Meu-Treino-2`.
2. Inicie Apache e MySQL pelo XAMPP.
3. Copie `.env.example` para `.env` e ajuste suas credenciais. Não crie `.env.txt`.
4. Importe **`database/schema.sql`** uma vez no phpMyAdmin. Ele cria o banco novo com 42 opções de exercícios e marca a versão 002; **não rode também a migração**.
5. Abra `http://localhost/Meu-Treino-2/`, crie uma conta e entre.

Não use Live Server, arquivo local ou GitHub Pages para executar PHP. Se houver `index.html` da versão estática antiga na raiz, remova somente esse arquivo antigo depois de guardar a cópia/histórico: a entrada atual é `index.php`.

## Administrador
Cadastre uma conta comum e, no **cmd**, execute:
```bat
cd /d C:\xampp\htdocs\Meu-Treino-2
C:\xampp\php\php.exe tools\promover-admin.php seu-email@example.com
```
Use o e-mail real da sua conta. Saia/entre novamente. Não há senha padrão nem promoção pela API pública. Usuário comum recebe 403 ao acessar administração por URL.

## Como usar
**Já treinou:** Registrar treino → grupo → marque exercícios ou escreva no campo livre → Registrar treino. Não é necessário informar horário, séries, repetições ou carga.

**Quer uma sugestão:** Montar treino → grupo/tempo → Montar treino. Nada foi salvo. Clique em Registrar este treino apenas depois de realizá-lo; desmarque o que não fez e confirme. Se não confirmar, o histórico/descanso não mudam.

**Editar/excluir:** Meus treinos → Editar ou Excluir. Você só acessa seus próprios registros, mesmo com perfil admin. Exclusão precisa de confirmação. Edição preserva a data original.

**Vídeos:** admin pode inserir URL HTTPS verificada. “Ver como fazer” abre nova aba. Não há incorporação de scripts de terceiros nem busca de vídeos automática.

## Arquitetura e pastas
```text
public/index.php          páginas protegidas e menu
public/api.php            entrada da API JSON
public/assets/            CSS/JS e imagens originais
src/Views/                telas PHP/HTML
src/Controllers/          coordenação das requisições
src/Services/             regras, sugestão, descanso e perfil
src/Repositories/         consultas PDO e transações
src/Middleware/           sessão, CSRF e autorização
src/Helpers/              validação, configuração, respostas, erros e logs
config/                   inicialização e grupos musculares
database/schema.sql       instalações novas
database/migrations/      atualização de banco existente
tests/                    testes unitários e integração HTTP
docs/                     guias, API, relatório e evidências da preparação
tools/                    promoção admin e medição HTTP
logs/                     app.log (não versionado)
```
Repository = parte que fala com o banco. Service = regras do sistema. Controller = recebe o pedido e coordena a resposta. `docs/GUIA_APRESENTACAO.md` explica os termos e mostra os arquivos.

## Testes
No cmd, dentro da pasta do projeto:
```bat
C:\xampp\php\php.exe tests\run.php
C:\xampp\php\php.exe tests\integration.php http://localhost/Meu-Treino-2/public
```
O primeiro usa repositórios em memória e não altera MySQL. O segundo requer Apache/MySQL, schema atualizado e curl; use uma instalação local de testes. Cria duas contas temporárias e remove seus dados em `finally`; interrupção abrupta pode exigir limpeza das fixtures `teste-...@example.test`. Logs e contadores AUTO_INCREMENT permanecem. Não altera contas existentes.

Para salvar a saída real local:
```bat
mkdir tests\evidencias
C:\xampp\php\php.exe tests\run.php > tests\evidencias\unitarios.txt 2>&1
echo %ERRORLEVEL%
```
Esperado: saída 0, sem FALHA. Em `docs/evidencias_preparacao` ficam resultados reais obtidos em ambiente Linux de preparação, **não prints da sua instalação Windows**. Veja `docs/VERIFICACAO_ENTREGA.md` para escopo e limitações.

## API e segurança
`docs/API.md` documenta todos os endpoints e exemplos atualizados. Escritas usam JSON + `X-CSRF-Token`; sessão PHP identifica a conta. Geração de sugestão usa GET e não possui acesso à gravação de treinos. POST/PUT de treino exige `confirmado: true`.

PDO usa consultas preparadas. Inserção/edição de treino e seus itens é transacional (tudo ou nada). IDs e propriedade são conferidos no servidor. Senhas usam hash. Saídas PHP usam escaping; JS usa textContent. Erros têm status HTTP e JSON padronizados; detalhes técnicos apenas com `APP_ENV=development`. O perfil da conta é consultado em cada requisição protegida.

`logs/app.log` registra login/falha, criação/edição/exclusão, logout, acesso administrativo negado e erro interno, com timestamp e sem senha/token. Logs reais são ignorados no Git. `.htaccess` protege diretórios privados no Apache. Verifique `.env`, `config/`, `database/`, `src/`, `logs/`, `tests/` e `tools/` inacessíveis por HTTP.

Servidor PHP alternativo de desenvolvimento:
```bat
C:\xampp\php\php.exe -S localhost:8000 -t public
```
Use obrigatoriamente `-t public`: o servidor embutido não aplica `.htaccess`. Integração: base `http://localhost:8000`.

## Limitações e decisões
- Um treino pertence a um grupo e contém 1–20 itens; no máximo um item de texto livre, que pode listar vários exercícios. Não mistura grupos no mesmo registro.
- Séries/repetições/carga podem ficar nulas; se preenchidas, são validadas. Para exercícios por tempo, descreva duração no texto livre/observação; não invente repetições.
- Data/hora são do servidor, em Brasília; criação usa agora e edição mantém horário original. Não há preenchimento retroativo nesta versão.
- Tempo de sugestão é aproximado. Se catálogo tiver menos opções, retorna as disponíveis sem duplicar. Duas gerações podem coincidir por aleatoriedade.
- Fuso da interface: Brasília. Mantenha `APP_TIMEZONE=America/Sao_Paulo`.
- Sem recuperação de senha, login persistente, rate limiting ou algoritmo de prescrição. Sessão expira após 30 min sem atividade. Exposição pública requer HTTPS e revisão operacional.
- Histórico consulta no máximo dez treinos e seus itens; ainda há uma consulta de itens por treino, limitada pela paginação.
- A migração conserva quatro colunas legadas como cópia histórica em `treinos`; a aplicação nova não lê/escreve nelas. Não use a versão antiga após migrar.

## Entrega acadêmica
`CHECKLIST_REQUISITOS.md` cobre requisitos/rubrica. Complete `RELATORIO_BASE.md`/PDF com seus dados e evidências reais. Inclua SQL/migração, documentação da API, testes, capturas de segurança, logs, Git e checklist. O enunciado também penaliza ausência de vídeo: grave a demonstração e confirme formato/duração com o professor.

O uso substancial de IA está declarado em `USO_IA.md`. Não há commits fabricados nem execução Git por você. Faça commits reais somente após revisar/testar: por exemplo `feat: adiciona registro rapido e sugestoes confirmadas` e `docs: registra evidencias da validacao local`.


### Como o perfil influencia a sugestão

O sistema usa preferências de forma simples, sem calcular carga:

| Preferência | Efeito na sugestão |
|---|---|
| Iniciante | 2–3 séries |
| Intermediário | 3 séries |
| Avançado | 3–4 séries |
| Hipertrofia | 8–12 repetições; 60–90 s de descanso |
| Força | 4–6 repetições; 2–3 min de descanso |
| Condicionamento | 12–20 repetições; 30–60 s de descanso |
| Geral | 8–12 repetições; 60–90 s de descanso |

Esses valores aparecem como referência organizacional na tela **Montar treino**. O usuário não precisa preenchê-los para registrar o treino.
