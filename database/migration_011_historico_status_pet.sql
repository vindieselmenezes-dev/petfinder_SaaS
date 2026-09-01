/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 011 — Histórico de status do pet

   Registra toda mudança de status de um pet (Com Tutor, Perdido,
   Encontrado, Para Adoção, Adotado), append-only — nada aqui é
   apagado ou sobrescrito, mesmo espírito da retificação de
   prontuário (migração 008).
   ============================================================ */

USE petfinder;

CREATE TABLE IF NOT EXISTS pets_status_historico (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    status_anterior VARCHAR(30) NULL,

    status_novo VARCHAR(30) NOT NULL,

    alterado_por INT NULL,

    motivo VARCHAR(255) NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_status_historico_pet
        FOREIGN KEY (pet_id)
        REFERENCES pets(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_status_historico_usuario
        FOREIGN KEY (alterado_por)
        REFERENCES usuarios(id)
        ON DELETE SET NULL

);

CREATE INDEX idx_status_historico_pet ON pets_status_historico (pet_id);
