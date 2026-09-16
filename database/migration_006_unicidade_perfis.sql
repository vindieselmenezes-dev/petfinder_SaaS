/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 006 — Unicidade em perfis.usuario_id

   `perfis` nunca teve trava impedindo o mesmo usuário ter mais
   de uma linha de perfil. Isso causava duplicação silenciosa em
   qualquer JOIN com perfis — no caso encontrado, notificações
   de novo chamado de suporte chegando em dobro pro mesmo admin.

   Mesmo padrão de bug já corrigido antes em `enderecos`
   (endereço "principal" duplicado) e `favoritos` (sem unicidade).
   ============================================================ */

USE petfinder;

-- 1. Remove duplicatas existentes, mantendo a linha de menor id
--    (a mais antiga) de cada usuário
DELETE p1 FROM perfis p1
INNER JOIN perfis p2
    ON p1.usuario_id = p2.usuario_id
    AND p1.id > p2.id;

-- 2. Trava de unicidade real
ALTER TABLE perfis
    ADD CONSTRAINT uq_perfis_usuario UNIQUE (usuario_id);
