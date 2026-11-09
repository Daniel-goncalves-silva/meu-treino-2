# Guia de apresentação e termos

## Roteiro demonstrável
1. Abra o Trabalho I no histórico e mostre os mesmos grupos/imagens. Na versão II, explique que as marcações locais viraram registros completos por conta.
2. Cadastre e entre com usuário comum. Registre um treino marcando vários exercícios, sem preencher detalhes. Mostre histórico, edição, exclusão, semana e último treino.
3. Mostre no phpMyAdmin o registro persistido e as relações das tabelas. Atualize a página: não depende de armazenamento do navegador.
4. Entre com admin e altere um exercício de teste. Faça login comum e mostre a negação da URL administrativa.
5. Mostre uma requisição na aba Network, o token sem expor seu valor, o JSON e o status HTTP.
6. Abra os arquivos abaixo e explique o caminho da ação “Salvar treino”.
7. Execute `tests/run.php` e, com instalação de testes preparada, `tests/integration.php`. Mostre a saída real.
8. Mostre cinco evidências de segurança, logs, Git real e sua declaração de IA.

| Termo | Explicação simples no projeto | Onde mostrar |
|---|---|---|
| Frontend | Parte que aparece e responde aos cliques no navegador | `src/Views/` e `public/assets/` |
| Backend | PHP que recebe pedidos, verifica regras e salva dados | `public/api.php`, `src/` |
| API | Porta de comunicação: recebe pedidos e retorna dados JSON | `ApiController.php`, `docs/API.md` |
| JSON | Formato de texto estruturado com os dados da resposta | `Response::json`, Network |
| Controller | Coordena o pedido recebido | `src/Controllers/ApiController.php` |
| Service | Aplica regras, como só editar o próprio treino | `src/Services/WorkoutService.php` |
| Repository | Parte que fala com o MySQL | `src/Repositories/WorkoutRepository.php` |
| PDO / Prepared statement | Consulta preparada; conteúdo dos campos vai separado do comando SQL | `prepare` + `execute` nos repositórios |
| Hash | Representação da senha para verificação sem guardar seu texto original | `AuthService.php` |
| Sessão | Identifica a conta autenticada entre requisições | `Middleware/Session.php` |
| CSRF | Impede ação enviada de outro site sem o token da sessão | `Middleware/Csrf.php` e cabeçalho X-CSRF-Token |
| XSS / escaping | Impede que texto informado vire HTML ou script executável | `Response::escape` e `textContent` no JS |
| IDOR | Tentativa de mudar ID para atingir registro de outra pessoa | `WorkoutRepository::findOwned` e WHERE usuario_id |
| Grid | Organiza os cards em colunas | `.treinos-grid` no CSS |
| Media query | Muda as colunas e espaços quando a tela é menor | `@media` no CSS |
| Lazy loading | Adia carregamento de imagens fora da tela | `loading="lazy"` em `Views/painel.php` |
| localStorage | Armazenamento só daquele navegador, usado no Trabalho I | Substituído pelo MySQL para dados de negócio |
| Paginação | Divide o histórico em páginas de 10 linhas | `WorkoutRepository::listing` e tela Meus treinos |
| Namespace | Agrupa classes por responsabilidade e evita nomes conflitantes | `namespace App\\Services` etc. |
| Logs | Linhas com data/hora de eventos relevantes | `Helpers/Logger.php`, `logs/app.log` |
| Lighthouse | Ferramenta do navegador para medir qualidade da página | Resultado novo a coletar em localhost |
| Commit / branch | Registro real de mudança / linha de desenvolvimento | GitHub Desktop e `git log --oneline --graph` |

## Perguntas prováveis
**Por que PHP puro?** Atende ao ambiente XAMPP da disciplina e permite mostrar as camadas sem dependências extras.

**Esconder o botão admin basta?** Não. `Auth::admin` também bloqueia a página e a API no servidor.

**E se alguém mudar o ID?** O banco procura ID junto com usuário da sessão; dados alheios não são retornados. Em edição e exclusão o WHERE repete essa restrição.

**Por que o administrador não altera qualquer treino?** O perfil administra catálogo e usuários; a regra de privacidade do histórico permanece para todos.

**O que conta como treino no painel?** Cada registro é um treino confirmado de um grupo, com um ou vários exercícios. As linhas antigas foram preservadas como treinos com um item.

**O que ainda precisa melhorar?** Rate limiting, recuperação de senha, mais filtros. Antes de exposição pública, é necessário HTTPS e revisão operacional.

**Como usou IA?** Explique a contribuição real conforme `USO_IA.md` e mostre suas revisões/execuções, sem afirmar autoria independente da implementação gerada.


## Demonstrar a nova evolução
1. Diga: “O sistema responde o que eu treinei e me ajuda a montar uma sugestão para hoje.”
2. Registre dois exercícios de um grupo em poucos cliques. Mostre que detalhes são opcionais.
3. Mostre último horário, Em descanso e disponibilidade após 48h.
4. Monte uma sugestão e volte ao histórico: nada novo deve aparecer.
5. Monte novamente, clique em Registrar este treino, desmarque um exercício e confirme. Agora há um único treino com os itens realizados.
6. Edite sem alterar horário; exclua um registro de teste. Mostre isolamento entre contas.
7. Abra Perfil, salve o tempo e mostre o preenchimento em Montar treino.
8. No admin, mostre video_url vazio ou um link real que você verificou. Não invente um link para apresentação.
9. Explique a migração: conta/horário preservados; um treino passou a ter vários itens, usando treino_exercicios.

| Termo adicional | Explicação simples | Local |
|---|---|---|
| Transação | Ou salva treino e todos os itens, ou desfaz tudo em falha | WorkoutRepository::create/update |
| Relação 1:N | Um treino possui vários exercícios registrados | schema.sql, treino_exercicios |
| Migração | Atualiza a estrutura do banco já existente preservando dados | migrations/002_registro_rapido.sql |
| Sugestão somente leitura | Consulta catálogo sem gravar histórico | SuggestionService; GET sugestao |
| NULL | Detalhe não informado, diferente de carga zero | treino_exercicios.series/repeticoes/carga |

A mudança de interface foi uma decisão de projeto/usabilidade durante o desenvolvimento, não resultado de pesquisa com usuários. Registre a assistência real de IA.
