/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 010 — Solicitações de adoção

   Liga com as tabelas `conversas`/`mensagens`, que já existiam
   no banco prontas mas nunca tinham sido usadas por nenhuma
   tela — cada solicitação de adoção já nasce com uma conversa
   entre o interessado e o dono do pet.
   ============================================================ */

USE petfinder;

CREATE TABLE IF NOT EXISTS solicitacoes_adocao (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    usuario_solicitante_id INT NOT NULL,

    conversa_id INT NULL,

    mensagem TEXT NULL,

    status ENUM('Pendente', 'Aprovada', 'Rejeitada', 'Cancelada') NOT NULL DEFAULT 'Pendente',

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_solicitacao_pet
        FOREIGN KEY (pet_id)
        REFERENCES pets(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_solicitacao_usuario
        FOREIGN KEY (usuario_solicitante_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_solicitacao_conversa
        FOREIGN KEY (conversa_id)
        REFERENCES conversas(id)
        ON DELETE SET NULL

);

CREATE INDEX idx_solicitacao_pet ON solicitacoes_adocao (pet_id);
CREATE INDEX idx_solicitacao_usuario ON solicitacoes_adocao (usuario_solicitante_id);
