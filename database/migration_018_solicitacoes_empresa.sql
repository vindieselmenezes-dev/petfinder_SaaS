-- PetFinder: Solicitacao de Servico para Empresas (Banho e Tosa, Hotel para
-- Pets/Creche Pet, Adestramento).
--
-- Contexto: o SaaS ja tinha "Agendar Consulta" (tabela consultas, so para
-- as categorias 2/3 - Clinica Veterinaria e Hospital) e "Solicitar Servico"
-- para prestadores autonomos (tabela prestador_solicitacoes, so para
-- passeador/pet_sitter/taxista_pet). As paginas tematicas banho_e_tosa.php,
-- hotelzinho.php e adestramento.php listavam empresas de verdade, mas o
-- perfil da empresa (empresa.php) nao tinha NENHUM jeito de contratar --
-- so o WhatsApp, quando a empresa tinha cadastrado um.
--
-- Esta migration cria uma tabela generica de solicitacao (mesmo espirito de
-- prestador_solicitacoes), que cobre as categorias 4 (Banho e Tosa),
-- 5 (Hotel para Pets), 6 (Creche Pet) e 7 (Adestramento).
-- Aplicar depois da migration_017_trial_com_prazo.sql.

CREATE TABLE empresa_solicitacoes (
    id INT NOT NULL AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    usuario_id INT NOT NULL,
    pet_id INT DEFAULT NULL,
    servico VARCHAR(150) DEFAULT NULL,
    data_desejada DATE DEFAULT NULL,
    periodo ENUM('Manhã', 'Tarde', 'Noite') DEFAULT NULL,
    mensagem TEXT DEFAULT NULL,
    status ENUM('pendente', 'aceita', 'recusada', 'concluida', 'cancelada') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_empresa_solic_empresa (empresa_id),
    KEY idx_empresa_solic_usuario (usuario_id),
    CONSTRAINT fk_empresa_solic_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE,
    CONSTRAINT fk_empresa_solic_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_empresa_solic_pet FOREIGN KEY (pet_id) REFERENCES pets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
