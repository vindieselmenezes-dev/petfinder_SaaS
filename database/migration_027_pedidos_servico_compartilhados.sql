-- Pedidos de serviço enviados primeiro a empresas em destaque e depois aos demais.
-- Aplicar depois da migration_026_moderacao_campanhas.sql.

CREATE TABLE IF NOT EXISTS pedidos_servico (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    pet_id INT DEFAULT NULL,
    servico VARCHAR(150) NOT NULL,
    data_desejada DATE NOT NULL,
    periodo ENUM('Manhã', 'Tarde', 'Noite') DEFAULT NULL,
    mensagem TEXT DEFAULT NULL,
    status ENUM('aguardando_destaques', 'aguardando_demais', 'com_orcamentos', 'cancelado', 'sem_empresas') NOT NULL,
    ampliar_em DATETIME DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedidos_servico_usuario (usuario_id, criado_em),
    KEY idx_pedidos_servico_expansao (status, ampliar_em),
    CONSTRAINT fk_pedidos_servico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_pedidos_servico_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
    CONSTRAINT fk_pedidos_servico_pet FOREIGN KEY (pet_id) REFERENCES pets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos_servico_empresas (
    id INT NOT NULL AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    empresa_id INT NOT NULL,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('aguardando', 'notificada', 'orcamento_enviado', 'recusada') NOT NULL DEFAULT 'aguardando',
    notificada_em DATETIME DEFAULT NULL,
    valor_orcado DECIMAL(10,2) DEFAULT NULL,
    data_hora_proposta DATETIME DEFAULT NULL,
    profissional_responsavel VARCHAR(150) DEFAULT NULL,
    observacoes_empresa TEXT DEFAULT NULL,
    contato_em DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedido_servico_empresa (pedido_id, empresa_id),
    KEY idx_pedido_servico_empresa_status (empresa_id, status, notificada_em),
    CONSTRAINT fk_pedido_servico_empresa_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos_servico (id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_servico_empresa_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;