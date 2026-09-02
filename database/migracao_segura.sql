-- ============================================================
-- MIGRAÇÃO SEGURA — só adiciona o que falta, não apaga nada.
--
-- Cobre as diferenças encontradas entre o schema antigo do
-- projeto e o schema real/atualizado:
--   1. pets.status              (faltava -> cadastro/edição de pet quebrava)
--   2. usuarios.tipo_usuario    (faltava -> listagem de chamados quebrava)
--   3. tabela pets_status_historico (histórico de status do pet)
--   4. tabela pet_alertas_perdidos  (alertas de pet perdido)
--
-- Como usar: abra o phpMyAdmin, selecione o banco "petfinder",
-- vá na aba "SQL" e cole este arquivo inteiro, depois "Executar".
-- Pode rodar mais de uma vez sem problema — é idempotente.
-- ============================================================

-- Garante que a sessão use utf8mb4, senão acentos podem se corromper
-- ao gravar (alguns clientes MySQL usam latin1 como padrão).
SET NAMES utf8mb4;

USE petfinder;

-- 1) pets.status
ALTER TABLE pets
    ADD COLUMN IF NOT EXISTS status
        ENUM('Com Tutor','Perdido','Encontrado','Para Adoção','Adotado')
        NOT NULL DEFAULT 'Com Tutor'
        AFTER raca_id;

-- 2) usuarios.tipo_usuario
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS tipo_usuario
        VARCHAR(20) NOT NULL DEFAULT 'tutor';

-- 3) pets_status_historico
CREATE TABLE IF NOT EXISTS pets_status_historico (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pet_id INT(11) NOT NULL,
    status_anterior VARCHAR(30) DEFAULT NULL,
    status_novo VARCHAR(30) NOT NULL,
    alterado_por INT(11) DEFAULT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_status_historico_usuario (alterado_por),
    KEY idx_status_historico_pet (pet_id),
    CONSTRAINT fk_status_historico_pet
        FOREIGN KEY (pet_id) REFERENCES pets (id) ON DELETE CASCADE,
    CONSTRAINT fk_status_historico_usuario
        FOREIGN KEY (alterado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) pet_alertas_perdidos
CREATE TABLE IF NOT EXISTS pet_alertas_perdidos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    pet_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    last_seen_location VARCHAR(255) NOT NULL,
    lost_latitude DECIMAL(10,8) DEFAULT NULL,
    lost_longitude DECIMAL(11,8) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('Ativo','Encontrado') DEFAULT 'Ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY pet_id (pet_id),
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) enderecos.referencia (ponto de referência do endereço, usado no checkout)
ALTER TABLE enderecos
    ADD COLUMN IF NOT EXISTS referencia VARCHAR(255) DEFAULT NULL AFTER estado;

-- 6) pedidos.previsao_entrega e pedidos.endereco_id
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS previsao_entrega DATE DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS endereco_id INT(11) DEFAULT NULL AFTER previsao_entrega;

SELECT 'Migração concluída com sucesso.' AS resultado;
