/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 003 — Trava de unicidade em favoritos

   A tabela `favoritos` nunca teve uma constraint impedindo o
   mesmo usuário de favoritar o mesmo pet duas vezes. O código
   PHP já checa isso antes de inserir (Favorito::adicionar),
   mas isso não protege contra corrida de requisições
   (duplo-clique rápido, abas simultâneas). Esta migração:

   1. Remove duplicatas que já possam existir hoje, mantendo
      só a linha mais antiga de cada (usuario_id, pet_id).
   2. Adiciona a constraint UNIQUE de verdade no banco.
   ============================================================ */

USE petfinder;

-- 1. Remove duplicatas existentes, mantendo a linha de menor id
DELETE f1 FROM favoritos f1
INNER JOIN favoritos f2
    ON f1.usuario_id = f2.usuario_id
    AND f1.pet_id = f2.pet_id
    AND f1.id > f2.id;

-- 2. Trava de unicidade real
ALTER TABLE favoritos
    ADD CONSTRAINT uq_favoritos_usuario_pet UNIQUE (usuario_id, pet_id);

-- Nota: `produto_favoritos` já nasce com essa trava desde a criação
-- (ver FavoritoProduto::garantirTabelaFavoritos), não precisa de ajuste aqui.
