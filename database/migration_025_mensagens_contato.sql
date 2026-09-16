-- PetFinder: mensagens do formulário público de contato
-- (rodapé "Contato" -> public/contato.php).
--
-- Aplicar depois da migration_024_parceiros_campanhas.sql.

CREATE TABLE mensagens_contato (
    id INT NOT NULL AUTO_INCREMENT,
    -- Preenchido automaticamente quando quem envia está logado;
    -- o formulário funciona também para visitantes sem conta.
    usuario_id INT DEFAULT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL,
    assunto VARCHAR(150) DEFAULT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('novo', 'em_andamento', 'respondido') NOT NULL DEFAULT 'novo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensagens_contato_status (status, criado_em),
    CONSTRAINT fk_mensagens_contato_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
