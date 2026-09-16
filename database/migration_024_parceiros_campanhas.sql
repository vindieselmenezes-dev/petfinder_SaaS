-- PetFinder: Área de Parceiros (ONGs e empresas apoiadoras) com
-- campanhas, eventos e doações.
--
-- Contexto: a seção "Empresas Parceiras" da index.html era só uma
-- fileira de 4 imagens estáticas (assets/img/parceiros/parceiro0X.jpg),
-- sem nenhum cadastro por trás. Agora ela vira uma área de verdade:
--
--   parceiros            -> ONGs, protetores e empresas que apoiam o
--                           projeto (divulgação, patrocínio, parceria).
--   parceiro_campanhas   -> campanhas de arrecadação, eventos (feira de
--                           adoção, mutirão de castração) e pedidos de
--                           doação publicados por esses parceiros.
--   campanha_apoios      -> quem se ofereceu para doar, ser voluntário
--                           ou divulgar uma campanha.
--
-- Aplicar depois da migration_023_adestrador_prestador_autonomo.sql.

-- ==========================================================
-- 1) PARCEIROS
-- ==========================================================

CREATE TABLE parceiros (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    -- Quando o parceiro também tem empresa cadastrada no SaaS, o vínculo
    -- permite mostrar o perfil comercial junto do selo de parceiro.
    empresa_id INT DEFAULT NULL,
    tipo ENUM('ong', 'empresa', 'apoiador') NOT NULL DEFAULT 'ong',
    nome VARCHAR(150) NOT NULL,
    documento VARCHAR(20) DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    -- Como esse parceiro ajuda a divulgar/crescer o PetFinder
    como_ajuda VARCHAR(255) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    cidade VARCHAR(120) DEFAULT NULL,
    estado CHAR(2) DEFAULT NULL,
    site VARCHAR(255) DEFAULT NULL,
    instagram VARCHAR(120) DEFAULT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    email_contato VARCHAR(180) DEFAULT NULL,
    chave_pix VARCHAR(150) DEFAULT NULL,
    link_doacao VARCHAR(255) DEFAULT NULL,
    aceita_voluntarios TINYINT(1) NOT NULL DEFAULT 1,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pendente', 'aprovado', 'recusado', 'inativo') NOT NULL DEFAULT 'pendente',
    observacao_admin VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_parceiros_usuario (usuario_id),
    KEY idx_parceiros_empresa (empresa_id),
    KEY idx_parceiros_status (status, tipo),
    CONSTRAINT fk_parceiros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_parceiros_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- 2) CAMPANHAS / EVENTOS / DOAÇÕES
-- ==========================================================

CREATE TABLE parceiro_campanhas (
    id INT NOT NULL AUTO_INCREMENT,
    parceiro_id INT NOT NULL,
    -- campanha: arrecadação com meta em R$ (castração, tratamento...)
    -- evento:   data e local (feira de adoção, mutirão, bazar)
    -- doacao:   pedido contínuo de itens (ração, coberta, remédio)
    tipo ENUM('campanha', 'evento', 'doacao') NOT NULL DEFAULT 'campanha',
    titulo VARCHAR(160) NOT NULL,
    resumo VARCHAR(255) DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    imagem VARCHAR(255) DEFAULT NULL,
    -- "local" é palavra-chave no MySQL, por isso o nome completo
    local_evento VARCHAR(220) DEFAULT NULL,
    data_inicio DATETIME DEFAULT NULL,
    data_fim DATETIME DEFAULT NULL,
    meta_valor DECIMAL(10, 2) DEFAULT NULL,
    valor_arrecadado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    -- Itens pedidos quando não é dinheiro (ex: "ração sênior, coleiras")
    itens_desejados VARCHAR(255) DEFAULT NULL,
    chave_pix VARCHAR(150) DEFAULT NULL,
    link_externo VARCHAR(255) DEFAULT NULL,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('rascunho', 'ativa', 'encerrada') NOT NULL DEFAULT 'ativa',
    visualizacoes INT NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_campanhas_parceiro (parceiro_id),
    KEY idx_campanhas_vitrine (status, destaque, data_fim),
    KEY idx_campanhas_tipo (tipo, status),
    CONSTRAINT fk_campanhas_parceiro FOREIGN KEY (parceiro_id) REFERENCES parceiros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- 3) QUEM SE OFERECEU PARA AJUDAR
-- ==========================================================

CREATE TABLE campanha_apoios (
    id INT NOT NULL AUTO_INCREMENT,
    campanha_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    tipo ENUM('doacao', 'voluntariado', 'divulgacao', 'duvida') NOT NULL DEFAULT 'doacao',
    valor DECIMAL(10, 2) DEFAULT NULL,
    mensagem TEXT DEFAULT NULL,
    contato VARCHAR(180) DEFAULT NULL,
    status ENUM('novo', 'em_contato', 'concluido') NOT NULL DEFAULT 'novo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_apoios_campanha (campanha_id, status),
    KEY idx_apoios_usuario (usuario_id),
    CONSTRAINT fk_apoios_campanha FOREIGN KEY (campanha_id) REFERENCES parceiro_campanhas (id) ON DELETE CASCADE,
    CONSTRAINT fk_apoios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
