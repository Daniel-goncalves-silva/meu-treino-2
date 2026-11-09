# Desempenho e limites das medições

Histórico paginado em 10 treinos, usuários em 20. Consultas de itens são limitadas aos treinos da página. Índices usuário/data e usuário/grupo/data atendem histórico e resumo. Catálogo usa imagens WebP originais e loading="lazy". Sugestão consulta um grupo e embaralha opções na memória; catálogo previsto é pequeno.

Testes de correção não equivalem a benchmark. Não se reutilizou Lighthouse do Trabalho I. Execute `tools/medir-api.php` para 30 requisições ao endpoint csrf (um aquecimento descartado); isso mede HTTP/PHP/sessão, não o banco.

Para medir banco, faça login e use F12 → Network, registrando cinco chamadas de treinos/resumo/sugestao. Anote máquina, versões, número de treinos, duração e tamanho. No phpMyAdmin:
```sql
EXPLAIN SELECT id,data_treino FROM treinos
WHERE usuario_id=1 ORDER BY data_treino DESC LIMIT 10;
```
Troque o ID por conta de teste. Registre o índice escolhido, sem inferir ganhos não medidos.

| Medida | Resultado |
|---|---|
| API com banco no XAMPP | A coletar |
| Lighthouse / responsividade real | A coletar |
| Média/mediana/p95 do script | A coletar |

A referência de 30min/1h/mais de 1h na sugestão descreve planejamento aproximado, não uma medição de duração real de treino.
