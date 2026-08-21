/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 001 — Unificação do módulo B2B (pet-saas-nativo)
   Rodar depois de database/petfinder.sql já estar aplicado.
   ============================================================ */

USE petfinder;

SET NAMES utf8mb4;

-- ============================================================
-- 1. EMPRESA_EQUIPE
-- Um usuário pode administrar várias empresas com papéis
-- diferentes em cada uma. `empresas.usuario_id` continua sendo
-- o DONO (não mexemos nele, é usado em todo o código já
-- existente). Esta tabela cobre colaboradores ADICIONAIS.
-- ============================================================

CREATE TABLE empresa_equipe (

    id INT AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT NOT NULL,

    usuario_id INT NOT NULL,

    papel ENUM(
        'proprietario',
        'administrador',
        'veterinario',
        'adestrador',
        'atendente',
        'financeiro'
    ) NOT NULL DEFAULT 'atendente',

    status ENUM('ativo', 'pendente', 'inativo') NOT NULL DEFAULT 'ativo',

    convidado_por INT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_equipe_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_equipe_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_equipe_convidado_por
        FOREIGN KEY (convidado_por)
        REFERENCES usuarios(id)
        ON DELETE SET NULL,

    CONSTRAINT uq_equipe_empresa_usuario
        UNIQUE (empresa_id, usuario_id)

);

-- Popula a equipe com o dono de cada empresa já existente,
-- como 'proprietario', pra toda empresa já ter pelo menos 1 linha.
INSERT INTO empresa_equipe (empresa_id, usuario_id, papel, status)
SELECT id, usuario_id, 'proprietario', 'ativo'
FROM empresas;


-- ============================================================
-- 2. PET_COTUTORES
-- `pets.usuario_id` continua sendo o TUTOR PRINCIPAL (não
-- mexemos nele). Esta tabela cobre CO-TUTORES adicionais.
-- ============================================================

CREATE TABLE pet_cotutores (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    usuario_id INT NOT NULL,

    convidado_por INT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cotutor_pet
        FOREIGN KEY (pet_id)
        REFERENCES pets(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_cotutor_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_cotutor_convidado_por
        FOREIGN KEY (convidado_por)
        REFERENCES usuarios(id)
        ON DELETE SET NULL,

    CONSTRAINT uq_cotutor_pet_usuario
        UNIQUE (pet_id, usuario_id)

);


-- ============================================================
-- 3. CONSULTAS.EMPRESA_ID
-- Liga uma consulta (e, por tabela, o prontuário) à empresa
-- onde ela aconteceu. NULL permitido: cobre atendimento
-- avulso/domiciliar de veterinário autônomo, sem empresa.
-- ============================================================

ALTER TABLE consultas
    ADD COLUMN empresa_id INT NULL AFTER veterinario_id,
    ADD CONSTRAINT fk_consulta_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE SET NULL;


-- ============================================================
-- 4. ÍNDICES DE APOIO
-- Essas tabelas vão ser consultadas o tempo todo em checagens
-- de acesso (login, painel B2B, meus pets) — vale indexar.
-- ============================================================

CREATE INDEX idx_equipe_usuario ON empresa_equipe (usuario_id);
CREATE INDEX idx_cotutor_usuario ON pet_cotutores (usuario_id);
CREATE INDEX idx_consulta_empresa ON consultas (empresa_id);
