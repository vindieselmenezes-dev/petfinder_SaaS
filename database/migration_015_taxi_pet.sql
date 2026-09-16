-- PetFinder: novo tipo de prestador "Taxi Pet" (transporte de animais)
-- Ideia trazida da home alternativa "index-petfinder.html" do
-- projetointegrador (categoria de servico "TAXI PET" que ainda nao
-- existia em lugar nenhum do sistema).
--
-- IMPORTANTE (encoding): assim como a migration_014, aplique com
-- --default-character-set=utf8mb4 pra nao correr risco de duplicar o
-- encoding dos acentos nos ENUMs.
--   mysql -u root --default-character-set=utf8mb4 petfinder < migration_015_taxi_pet.sql

ALTER TABLE prestadores_servico
    MODIFY COLUMN tipo ENUM('passeador', 'pet_sitter', 'taxista_pet') NOT NULL;

-- Dados especificos de quem presta o servico de Taxi Pet (1:1 com
-- prestadores_servico, so existe quando tipo = 'taxista_pet')
CREATE TABLE prestador_veiculo (
    prestador_id INT NOT NULL,
    tipo_veiculo ENUM('Carro', 'Van', 'Moto com bag pet') NOT NULL DEFAULT 'Carro',
    modelo VARCHAR(100) DEFAULT NULL,
    placa VARCHAR(10) DEFAULT NULL,
    ano VARCHAR(4) DEFAULT NULL,
    capacidade_pets TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ar_condicionado TINYINT(1) NOT NULL DEFAULT 0,
    caixa_transporte TINYINT(1) NOT NULL DEFAULT 0,
    aceita_animais_grandes TINYINT(1) NOT NULL DEFAULT 0,
    valor_km DECIMAL(10,2) DEFAULT NULL,
    valor_corrida_minima DECIMAL(10,2) DEFAULT NULL,
    PRIMARY KEY (prestador_id),
    CONSTRAINT fk_prestador_veiculo_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
