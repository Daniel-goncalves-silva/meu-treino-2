# Arquivos desta atualização

Comparação com o primeiro ZIP Meu-Treino-2. Arquivos não listados foram mantidos. Configuração privada e logs de execução não integram a entrega.

## Alterados

- `README.md`
- `config/groups.php`
- `database/schema.sql`
- `docs/API.md`
- `docs/ARQUITETURA.md`
- `docs/CHECKLIST_REQUISITOS.md`
- `docs/DESEMPENHO.md`
- `docs/GUIA_APRESENTACAO.md`
- `docs/RELATORIO_BASE.md`
- `docs/RELATORIO_BASE.pdf`
- `docs/TESTES_SEGURANCA.md`
- `docs/USO_IA.md`
- `docs/VERIFICACAO_ENTREGA.md`
- `public/assets/script.js`
- `public/assets/style.css`
- `public/index.php`
- `src/Controllers/ApiController.php`
- `src/Helpers/Validator.php`
- `src/Repositories/ExerciseRepository.php`
- `src/Repositories/UserRepository.php`
- `src/Repositories/WorkoutRepository.php`
- `src/Services/WorkoutService.php`
- `src/Views/admin.php`
- `src/Views/painel.php`
- `src/Views/registrar.php`
- `tests/integration.php`
- `tests/run.php`

## Adicionados

- `database/migrations/002_registro_rapido.sql`
- `docs/ALTERACOES.md`
- `docs/ATUALIZACAO.md`
- `docs/evidencias_preparacao/integracao_mt_validacao_migracao.txt`
- `docs/evidencias_preparacao/integracao_mt_validacao_nova.txt`
- `docs/evidencias_preparacao/migracao.txt`
- `docs/evidencias_preparacao/sintaxe.txt`
- `docs/evidencias_preparacao/unitarios.txt`
- `src/Services/ProfileService.php`
- `src/Services/RestService.php`
- `src/Services/SuggestionService.php`
- `src/Views/montar.php`
- `src/Views/perfil.php`

## Instalação

Siga ATUALIZACAO.md. Em banco existente, importe somente database/migrations/002_registro_rapido.sql após backup. Preserve .env e .git locais. Nenhum commit foi criado.
