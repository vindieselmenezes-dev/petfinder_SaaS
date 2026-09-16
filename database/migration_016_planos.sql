-- PetFinder: sistema de Planos para empresas (base pra futura cobrança
-- automatizada via gateway de pagamento). Por enquanto a troca de plano
-- é simulada (igual o simular_faturamento.php já fazia pra
-- status_pagamento) -- quando o gateway for escolhido, é só plugar o
-- webhook em cima do que já existe aqui.
--
-- IMPORTANTE (encoding): aplique com --default-character-set=utf8mb4.
--   mysql -u root --default-character-set=utf8mb4 petfinder < migration_016_planos.sql

CREATE TABLE planos (
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(60) NOT NULL,
    slug VARCHAR(30) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    preco_mensal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    limite_produtos INT DEFAULT NULL COMMENT 'NULL = ilimitado',
    destaque TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'aparece com selo e no topo das buscas',
    prioridade INT NOT NULL DEFAULT 0 COMMENT 'usado no ORDER BY das listagens, maior = aparece antes',
    dias_trial INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_planos_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO planos (nome, slug, descricao, preco_mensal, limite_produtos, destaque, prioridade, dias_trial) VALUES
('Grátis', 'gratis', 'Perfil básico visível nas buscas, com até 5 produtos/serviços no catálogo.', 0.00, 5, 0, 0, 0),
('Profissional', 'profissional', 'Catálogo ilimitado de produtos/serviços e prioridade nas buscas.', 49.90, NULL, 0, 5, 14),
('Destaque', 'destaque', 'Tudo do Profissional + selo de destaque e o topo das buscas da categoria.', 99.90, NULL, 1, 10, 14);

ALTER TABLE empresas
    ADD COLUMN plano_id INT DEFAULT NULL AFTER categoria_id,
    ADD COLUMN plano_iniciado_em DATE DEFAULT NULL,
    ADD COLUMN plano_expira_em DATE DEFAULT NULL,
    ADD KEY idx_empresa_plano (plano_id),
    ADD CONSTRAINT fk_empresa_plano FOREIGN KEY (plano_id) REFERENCES planos (id) ON DELETE SET NULL;

-- Toda empresa que já existia entra automaticamente no plano Grátis
UPDATE empresas
SET plano_id = (SELECT id FROM planos WHERE slug = 'gratis' LIMIT 1),
    plano_iniciado_em = CURDATE()
WHERE plano_id IS NULL;
