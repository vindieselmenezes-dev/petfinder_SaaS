-- PetFinder: modulo de Prestadores de Servico (Passeador e Pet Sitter)
-- Ideia trazida do projeto "projetointegrador" e integrada de verdade ao
-- banco do SaaS. Diferente de "empresas" (pessoa juridica, CNPJ), aqui o
-- profissional se cadastra com o proprio CPF, atrelado a conta de usuario
-- que ja existe (usuarios.id) -- e pode, inclusive, ser tutor e prestador
-- ao mesmo tempo.
-- Aplicar depois da migration_013_produto_operacional.sql.
--
-- IMPORTANTE (encoding): esta migration tem ENUMs com acentos (Manhã,
-- Terça, Sábado). Se for aplicar via linha de comando, use
-- --default-character-set=utf8mb4, senao os acentos podem ser gravados
-- com encoding duplicado (ex: "Ã£" em vez de "ã") e toda insercao que
-- tentar usar esses valores vai falhar com "Data truncated for column":
--   mysql -u root --default-character-set=utf8mb4 petfinder < migration_014_prestadores_servico.sql
-- Se for aplicar pelo phpMyAdmin, isso nao costuma ser um problema
-- (ele ja usa utf8mb4 na conexao por padrao).

CREATE TABLE prestadores_servico (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('passeador', 'pet_sitter') NOT NULL,
    cpf CHAR(11) DEFAULT NULL,
    genero ENUM('feminino', 'masculino', 'nao-informar') DEFAULT 'nao-informar',
    data_nascimento DATE DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    email VARCHAR(180) DEFAULT NULL,
    cep VARCHAR(9) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    numero VARCHAR(20) DEFAULT NULL,
    complemento VARCHAR(120) DEFAULT NULL,
    bairro VARCHAR(120) DEFAULT NULL,
    cidade VARCHAR(120) DEFAULT NULL,
    estado CHAR(2) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    tempo_experiencia VARCHAR(60) DEFAULT NULL,
    formacao VARCHAR(180) DEFAULT NULL,
    experiencia TEXT DEFAULT NULL,
    apresentacao TEXT DEFAULT NULL,
    diferencial TEXT DEFAULT NULL,
    valor_hora DECIMAL(10,2) DEFAULT NULL,
    valor_diaria DECIMAL(10,2) DEFAULT NULL,
    forma_pagamento VARCHAR(180) DEFAULT NULL,
    area_atendimento VARCHAR(255) DEFAULT NULL,
    instagram VARCHAR(180) DEFAULT NULL,
    facebook VARCHAR(180) DEFAULT NULL,
    avaliacao DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    total_avaliacoes INT NOT NULL DEFAULT 0,
    status ENUM('pendente', 'aprovado', 'inativo') NOT NULL DEFAULT 'pendente',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prestador_usuario_tipo (usuario_id, tipo),
    KEY idx_prestador_tipo_cidade (tipo, cidade),
    CONSTRAINT fk_prestador_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Servicos oferecidos (multipla escolha: passeio matinal, hospedagem, etc)
CREATE TABLE prestador_servicos (
    id INT NOT NULL AUTO_INCREMENT,
    prestador_id INT NOT NULL,
    servico VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_prestador_servicos_prestador (prestador_id),
    CONSTRAINT fk_prestador_servicos_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Portes/especies de animais que o prestador atende
CREATE TABLE prestador_animais_atendidos (
    id INT NOT NULL AUTO_INCREMENT,
    prestador_id INT NOT NULL,
    animal VARCHAR(60) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_prestador_animais_prestador (prestador_id),
    CONSTRAINT fk_prestador_animais_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dias e periodos de disponibilidade
CREATE TABLE prestador_disponibilidade (
    id INT NOT NULL AUTO_INCREMENT,
    prestador_id INT NOT NULL,
    dia_semana ENUM('Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo') NOT NULL,
    periodo ENUM('Manhã', 'Tarde', 'Noite') NOT NULL,
    PRIMARY KEY (id),
    KEY idx_prestador_disp_prestador (prestador_id),
    CONSTRAINT fk_prestador_disp_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avaliacoes de tutores sobre o prestador (mesmo espirito de "avaliacoes",
-- so que apontando pra prestador_id em vez de empresa_id)
CREATE TABLE prestador_avaliacoes (
    id INT NOT NULL AUTO_INCREMENT,
    prestador_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nota TINYINT NOT NULL,
    comentario TEXT DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prestador_avaliacao_usuario (prestador_id, usuario_id),
    KEY idx_prestador_avaliacoes_prestador (prestador_id),
    CONSTRAINT fk_prestador_avaliacoes_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE,
    CONSTRAINT fk_prestador_avaliacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitacoes de contato/orcamento feitas por tutores para um prestador
-- (equivalente simples ao "Solicite um passeio" / "Solicite o servico de
-- Pet Sitter" do projetointegrador, ja gravando no banco em vez de so um
-- formulario estatico)
CREATE TABLE prestador_solicitacoes (
    id INT NOT NULL AUTO_INCREMENT,
    prestador_id INT NOT NULL,
    usuario_id INT NOT NULL,
    pet_id INT DEFAULT NULL,
    data_desejada DATE DEFAULT NULL,
    periodo ENUM('Manhã', 'Tarde', 'Noite') DEFAULT NULL,
    mensagem TEXT DEFAULT NULL,
    status ENUM('pendente', 'aceita', 'recusada', 'concluida', 'cancelada') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_prestador_solic_prestador (prestador_id),
    KEY idx_prestador_solic_usuario (usuario_id),
    CONSTRAINT fk_prestador_solic_prestador FOREIGN KEY (prestador_id) REFERENCES prestadores_servico (id) ON DELETE CASCADE,
    CONSTRAINT fk_prestador_solic_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_prestador_solic_pet FOREIGN KEY (pet_id) REFERENCES pets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PetFinder: "Identidade Pet" -- ideia da pagina identidadepet.html do
-- projetointegrador, aproveitando o que ja existe (pets.microchip,
-- pets_status_historico, pet_alertas_perdidos) e acrescentando um token
-- publico para gerar a carteirinha/QR de identificacao do pet.
ALTER TABLE pets
    ADD COLUMN token_identidade CHAR(32) DEFAULT NULL AFTER microchip,
    ADD UNIQUE KEY uq_pets_token_identidade (token_identidade);

UPDATE pets
SET token_identidade = MD5(CONCAT(id, '-', usuario_id, '-', UUID()))
WHERE token_identidade IS NULL;
