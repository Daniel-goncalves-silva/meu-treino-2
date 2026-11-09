# Verificações da evolução 2

## Executado de verdade na preparação
Ambiente isolado Linux, PHP 8.3.6 e MariaDB 10.11.14. Não acessou computador, repositório ou banco do aluno.

- Sintaxe PHP: todos os arquivos aprovados por `php -l`; JS aprovado por `node --check`.
- Regras de negócio: **48 aprovados / 0 falhas** (`unitarios.txt`).
- Integração HTTP/PDO/SQL no banco novo: **44 aprovados / 0 falhas**.
- A mesma integração no banco migrado: **44 aprovados / 0 falhas**.
- Importação do schema novo executada sem erro.
- Migração de schema v1 com 1 usuário admin e 2 treinos: preservação exata dos campos da conta (inclusive hash/perfil), IDs/datas/observações e dos detalhes dos dois exercícios.
- Reexecução da migração: não duplicou dados; retornou “já aplicada”.
- Páginas PHP de painel, registrar, montar, histórico e perfil retornaram 200 autenticadas. Página admin retornou 403 ao usuário comum e 200 ao admin. Página privada anônima redirecionou.
- Imagens originais comparadas byte a byte; permanecem íntegras.

Saídas completas: `docs/evidencias_preparacao/`. Elas não contêm credenciais, cookies ou tokens. As contas usadas são fixtures temporárias de teste.

## Correções durante a verificação
A primeira tentativa de integração neste ambiente isolado retornou CSRF inválido porque a pasta de sessões do PHP local não estava configurada. Foi configurada uma pasta gravável no runtime de teste e a suíte inteira foi reexecutada com sucesso. Essa configuração não altera o projeto/XAMPP do aluno.

## Ainda precisa no XAMPP
- Exportar banco, importar migração no phpMyAdmin e conferir a própria conta/dados.
- Navegação visual desktop/celular e teclado; cliques dos dois fluxos e confirmação/desmarcação.
- XSS refletido/armazenado no navegador, sem execução; capturas reais de segurança.
- Bloqueio Apache de .env/diretórios privados via .htaccess.
- Logs completos da demonstração, desempenho/Lighthouse e histórico Git real.

O navegador de teste baixado não iniciou neste ambiente (falha do processo); não foi possível fazer revisão visual em navegador. A verificação de páginas HTTP não executa o JavaScript nem substitui inspeção visual. Não são alegados prints ou resultados inexistentes.
