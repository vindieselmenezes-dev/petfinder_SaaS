-- PetFinder: período de teste com prazo real (30 dias), pra empresas
-- E prestadores (passeador/pet sitter/taxi pet) -- ninguém fica no
-- Grátis pra sempre. Depois do prazo sem assinar um plano pago, o
-- perfil some das buscas públicas e o painel/gestão fica travado.
--
-- IMPORTANTE (encoding): aplique com --default-character-set=utf8mb4.
--   mysql -u root --default-character-set=utf8mb4 petfinder < migration_017_trial_com_prazo.sql

-- 1) O plano Grátis passa a ter prazo (antes era 0 = permanente)
UPDATE planos SET dias_trial = 30 WHERE slug = 'gratis';

-- 2) Prestadores (passeador/pet sitter/taxi pet) ganham o mesmo esquema
--    de plano que as empresas já tinham
ALTER TABLE prestadores_servico
    ADD COLUMN plano_id INT DEFAULT NULL AFTER tipo,
    ADD COLUMN plano_iniciado_em DATE DEFAULT NULL,
    ADD COLUMN plano_expira_em DATE DEFAULT NULL,
    ADD KEY idx_prestador_plano (plano_id),
    ADD CONSTRAINT fk_prestador_plano FOREIGN KEY (plano_id) REFERENCES planos (id) ON DELETE SET NULL;

-- 3) Empresas já existentes: quem estava no Grátis sem data de expiração
--    ganha uma janela justa de 30 dias a partir de hoje (não é retroativo
--    de verdade, senão todo mundo cairia suspenso no mesmo segundo)
UPDATE empresas e
INNER JOIN planos p ON p.id = e.plano_id
SET e.plano_expira_em = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
WHERE p.slug = 'gratis' AND e.plano_expira_em IS NULL;

-- 4) Prestadores já existentes: entram no plano Grátis com a mesma
--    janela de 30 dias
UPDATE prestadores_servico ps
SET ps.plano_id = (SELECT id FROM planos WHERE slug = 'gratis' LIMIT 1),
    ps.plano_iniciado_em = CURDATE(),
    ps.plano_expira_em = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
WHERE ps.plano_id IS NULL;
