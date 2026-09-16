-- PetFinder: notificacoes externas, onboarding, rastreio, 2FA e metricas
-- Aplicar depois da migration_012_historico_eventos_pet.sql.

ALTER TABLE usuarios
    ADD COLUMN onboarding_concluido TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN dois_fatores_ativo TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN dois_fatores_secret VARCHAR(64) DEFAULT NULL,
    ADD COLUMN dois_fatores_codigo VARCHAR(10) DEFAULT NULL,
    ADD COLUMN dois_fatores_codigo_expira DATETIME DEFAULT NULL;

ALTER TABLE empresas
    ADD COLUMN onboarding_concluido TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN onboarding_etapa TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE pedidos
    ADD COLUMN codigo_rastreio VARCHAR(120) DEFAULT NULL,
    ADD COLUMN transportadora VARCHAR(120) DEFAULT NULL,
    ADD COLUMN enviado_em DATETIME DEFAULT NULL,
    ADD COLUMN entregue_em DATETIME DEFAULT NULL;

CREATE TABLE pedido_status_historico (
    id INT NOT NULL AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    status VARCHAR(40) NOT NULL,
    observacao VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedido_status_pedido (pedido_id),
    CONSTRAINT fk_pedido_status_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE metricas_empresa_eventos (
    id BIGINT NOT NULL AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    tipo ENUM('visualizacao','clique','conversao') NOT NULL,
    pagina VARCHAR(120) DEFAULT NULL,
    referencia_id INT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_metricas_empresa_data (empresa_id, criado_em),
    CONSTRAINT fk_metricas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
    id BIGINT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_endpoint (endpoint(255)),
    KEY idx_push_usuario (usuario_id),
    CONSTRAINT fk_push_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pedido_status_historico (pedido_id, status)
SELECT id, status FROM pedidos
WHERE NOT EXISTS (
    SELECT 1 FROM pedido_status_historico h WHERE h.pedido_id = pedidos.id
);
