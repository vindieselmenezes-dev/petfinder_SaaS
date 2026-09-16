/*
   MIGRACAO 012 - Historico de eventos e cuidados do pet

   Centraliza banho, tosa, consulta, cuidados especiais,
   adestramento e agenda de passeios.
*/

USE petfinder;

CREATE TABLE IF NOT EXISTS pets_historico_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    detalhes TEXT NULL,
    data_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pet_historico_evento (pet_id, data_evento),
    CONSTRAINT fk_pet_historico_evento_pet
        FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_pet_historico_evento_usuario
        FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
