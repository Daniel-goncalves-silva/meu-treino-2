# Arquitetura da evolução 2

A organização em camadas foi mantida; o projeto não foi recriado.

| Camada | Responsabilidade | Local |
|---|---|---|
| Interface | menu, cards, checkbox, confirmação, histórico e perfil | `src/Views`, `public/assets` |
| Controller | métodos, sessão, CSRF, perfil e coordenação | `ApiController` |
| Services | validar treino, sugestão somente leitura, 48h, preferências | `WorkoutService`, `SuggestionService`, `RestService`, `ProfileService` |
| Repositories | SQL parametrizado e transação de treino/itens | `src/Repositories` |
| Middleware | sessão segura, CSRF e autorização | `Session`, `Csrf`, `Auth` |
| Helpers | validação, ambiente, conexão, JSON, erros e logs | `src/Helpers` |

## Dois caminhos
**Planejar:** formulário Montar treino → GET sugestao → SuggestionService → catálogo → resposta. O serviço só recebe ExerciseRepository: não tem acesso a gravação de treinos. Não há tabela de sugestões ou gravação automática no histórico.

**Confirmar:** Registrar treino ou confirmação de sugestão → POST treinos com `confirmado: true` → WorkoutService → valida grupo/itens → WorkoutRepository → transação INSERT treino + itens → log. Um item errado faz a operação inteira falhar, sem treino parcial.

Editar usa PUT e substitui os itens em transação, mantendo data original. Excluir usa DELETE com usuário no WHERE; FK CASCADE apaga os itens do treino excluído. O painel calcula o último treino restante de cada grupo.

## Modelagem
- usuarios 1:N treinos; treinos 1:N treino_exercicios; exercicios 1:N treino_exercicios (FK opcional para texto livre).
- Um treino contém um grupo e 1–20 itens. Um item tem exercício do catálogo OU descrição livre; no máximo um texto livre por treino, podendo listar vários exercícios.
- Detalhes podem ser NULL. UNIQUE(treino_id,exercicio_id) evita repetir exercício do catálogo; NULL é válido para texto livre. CHECKs e backend validam origem e limites.
- Índices: usuário/data; usuário/grupo/data; FKs dos itens e grupo do catálogo.
- usuarios guarda nível/objetivo/tempo. O tempo preenche a sugestão; o nível e o objetivo são lidos no backend para montar uma orientação geral de volume, repetições e descanso. Não há cálculo de carga nem prescrição individualizada.
- schema_migrations guarda a versão. A migração conserva quatro colunas legadas, não usadas pela aplicação nova, para evitar apagar informação durante a transição. Instalações novas não precisam dessas colunas.

## Regra das 48h
`RestService` usa o último horário confirmado, soma 48h e compara com agora. Antes do limite: Em descanso. No instante do limite e depois: Pode treinar novamente. Sem registro: Pode treinar. Semana começa segunda-feira 00h em Brasília. O frontend atualiza o status periodicamente usando a referência de relógio do servidor. É uma regra organizacional, não orientação médica.

## Matriz de permissões
| Ação | Visitante | Usuário | Admin |
|---|---|---|---|
| Cadastro/login/token | Sim | Sim | Sim |
| Ver catálogo/montar sugestão | Não | Sim | Sim |
| Ler/alterar próprio perfil | Não | Sim | Sim |
| CRUD de treino próprio | Não | Sim | Sim |
| Treino de outra conta | Não | Não | Não |
| CRUD de exercício/vídeo | Não | Não | Sim |
| Ver usuários/totais | Não | Não | Sim |
| Promover conta pela API | Não | Não | Não |

A promoção continua somente via utilitário CLI. Autorização é conferida no servidor, além do menu. `findOwned`, UPDATE e DELETE restringem usuário. Leituras dos itens também incluem o dono; sessões não armazenam perfil de forma definitiva.

## Segurança preservada
Prepared statements, password_hash/verify, ID de sessão regenerado, expiração, logout, CSRF em escritas, escaping PHP e textContent JS, CSP, mensagens JSON e logs. Vídeo aceita só URL HTTPS sem credenciais e sem controles; navegador abre com noopener/noreferrer. O sistema valida sintaxe da URL, não garante disponibilidade nem qualidade do vídeo. Não foram preenchidos links de tutorial.

Diretórios privados continuam protegidos por `.htaccess`; servidor embutido exige `-t public`. Aplicação usa `.env` ignorado no Git. Detalhes técnicos de erros só aparecem em development.
