/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 1
   ============================================================ */

-- ============================================================
-- CRIAÇÃO DO BANCO
-- ============================================================

CREATE DATABASE IF NOT EXISTS petfinder
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE petfinder;

-- ============================================================
-- CONFIGURAÇÕES
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ============================================================
-- TABELA DE USUÁRIOS
-- ============================================================

CREATE TABLE usuarios (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL,

    sobrenome VARCHAR(150) NOT NULL,

    email VARCHAR(180) NOT NULL UNIQUE,

    senha VARCHAR(255) NOT NULL,

    telefone VARCHAR(20),

    cpf CHAR(11) UNIQUE,

    foto VARCHAR(255),

    data_nascimento DATE,

    genero ENUM(
        'Masculino',
        'Feminino',
        'Outro',
        'Prefiro não informar'
    ) DEFAULT 'Prefiro não informar',

    status ENUM(
        'ativo',
        'inativo',
        'bloqueado'
    ) DEFAULT 'ativo',

    ultimo_login DATETIME NULL,

    token_recuperacao VARCHAR(255),

    token_expira DATETIME,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);

-- ============================================================
-- PERFIS
-- ============================================================

CREATE TABLE perfis (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    tipo ENUM(

        'cliente',

        'empresa',

        'veterinario',

        'administrador'

    ) DEFAULT 'cliente',

    biografia TEXT,

    website VARCHAR(255),

    instagram VARCHAR(150),

    facebook VARCHAR(150),

    youtube VARCHAR(150),

    linkedin VARCHAR(150),

    FOREIGN KEY (usuario_id)

        REFERENCES usuarios(id)

        ON DELETE CASCADE

);

-- ============================================================
-- ENDEREÇOS
-- ============================================================

CREATE TABLE enderecos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    cep VARCHAR(9),

    logradouro VARCHAR(255),

    numero VARCHAR(20),

    complemento VARCHAR(255),

    bairro VARCHAR(150),

    cidade VARCHAR(150),

    estado CHAR(2),

    latitude DECIMAL(10,8),

    longitude DECIMAL(11,8),

    principal BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (usuario_id)

        REFERENCES usuarios(id)

        ON DELETE CASCADE

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_usuario_email
ON usuarios(email);

CREATE INDEX idx_usuario_status
ON usuarios(status);

CREATE INDEX idx_endereco_cidade
ON enderecos(cidade);

CREATE INDEX idx_endereco_estado
ON enderecos(estado);

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

INSERT INTO usuarios (

    nome,

    sobrenome,

    email,

    senha,

    status

)

VALUES (

    'Administrador',

    'Sistema',

    'admin@petfinder.com',

    '$2y$10$TroqueEstaSenhaPorHashReal',

    'ativo'

);

INSERT INTO perfis (

    usuario_id,

    tipo,

    biografia

)

VALUES (

    1,

    'administrador',

    'Administrador principal do sistema.'

);

-- ============================================================
-- FIM DA PARTE 1
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 2
   Categorias e Empresas
   ============================================================ */

-- ============================================================
-- CATEGORIAS
-- ============================================================

CREATE TABLE categorias (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(120) NOT NULL UNIQUE,

    descricao TEXT,

    icone VARCHAR(120),

    imagem VARCHAR(255),

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- SUBCATEGORIAS
-- ============================================================

CREATE TABLE subcategorias (

    id INT AUTO_INCREMENT PRIMARY KEY,

    categoria_id INT NOT NULL,

    nome VARCHAR(120) NOT NULL,

    descricao TEXT,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_subcategoria_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON DELETE CASCADE

);

-- ============================================================
-- EMPRESAS
-- ============================================================

CREATE TABLE empresas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    categoria_id INT NOT NULL,

    nome_fantasia VARCHAR(180) NOT NULL,

    razao_social VARCHAR(180),

    cnpj CHAR(14) UNIQUE,

    descricao TEXT,

    telefone VARCHAR(20),

    whatsapp VARCHAR(20),

    email VARCHAR(180),

    site VARCHAR(255),

    logo VARCHAR(255),

    capa VARCHAR(255),

    endereco VARCHAR(255),

    numero VARCHAR(20),

    complemento VARCHAR(120),

    bairro VARCHAR(120),

    cidade VARCHAR(120),

    estado CHAR(2),

    cep VARCHAR(9),

    latitude DECIMAL(10,8),

    longitude DECIMAL(11,8),

    avaliacao DECIMAL(2,1) DEFAULT 0,

    total_avaliacoes INT DEFAULT 0,

    verificada BOOLEAN DEFAULT FALSE,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_empresa_usuario
        FOREIGN KEY(usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_empresa_categoria
        FOREIGN KEY(categoria_id)
        REFERENCES categorias(id)

);

-- ============================================================
-- HORÁRIOS DE FUNCIONAMENTO
-- ============================================================

CREATE TABLE empresa_horarios (

    id INT AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT NOT NULL,

    dia_semana ENUM(

        'Segunda',
        'Terça',
        'Quarta',
        'Quinta',
        'Sexta',
        'Sábado',
        'Domingo'

    ) NOT NULL,

    abertura TIME,

    fechamento TIME,

    fechado BOOLEAN DEFAULT FALSE,

    CONSTRAINT fk_horario_empresa

        FOREIGN KEY (empresa_id)

        REFERENCES empresas(id)

        ON DELETE CASCADE

);

-- ============================================================
-- GALERIA DE IMAGENS
-- ============================================================

CREATE TABLE empresa_galeria (

    id INT AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT NOT NULL,

    imagem VARCHAR(255) NOT NULL,

    legenda VARCHAR(255),

    ordem INT DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_galeria_empresa

        FOREIGN KEY (empresa_id)

        REFERENCES empresas(id)

        ON DELETE CASCADE

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_categoria_nome
ON categorias(nome);

CREATE INDEX idx_empresa_nome
ON empresas(nome_fantasia);

CREATE INDEX idx_empresa_cidade
ON empresas(cidade);

CREATE INDEX idx_empresa_estado
ON empresas(estado);

CREATE INDEX idx_empresa_categoria
ON empresas(categoria_id);

-- ============================================================
-- CATEGORIAS PADRÃO
-- ============================================================

INSERT INTO categorias
(nome, descricao, icone)

VALUES

('Pet Shop','Lojas especializadas','bi-shop'),

('Clínica Veterinária','Consultas e exames','bi-heart-pulse'),

('Hospital Veterinário','Atendimento 24 horas','bi-hospital'),

('Banho e Tosa','Higiene e estética','bi-scissors'),

('Hotel para Pets','Hospedagem','bi-house'),

('Creche Pet','Day Care','bi-balloon-heart'),

('Adestramento','Treinamento','bi-award'),

('Adoção','ONGs e protetores','bi-heart'),

('Marketplace','Produtos para pets','bi-cart'),

('Serviços','Diversos','bi-grid');

-- ============================================================
-- FIM DA PARTE 2
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 3
   Veterinários e Consultas
============================================================ */

-- ============================================================
-- ESPECIALIDADES
-- ============================================================

CREATE TABLE especialidades (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(120) NOT NULL UNIQUE,

    descricao TEXT,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- VETERINÁRIOS
-- ============================================================

CREATE TABLE veterinarios (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    crmv VARCHAR(30) NOT NULL UNIQUE,

    biografia TEXT,

    experiencia INT DEFAULT 0,

    valor_consulta DECIMAL(10,2),

    atendimento_domicilio BOOLEAN DEFAULT FALSE,

    atendimento_online BOOLEAN DEFAULT FALSE,

    foto VARCHAR(255),

    avaliacao DECIMAL(2,1) DEFAULT 0,

    total_avaliacoes INT DEFAULT 0,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_veterinario_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE

);

-- ============================================================
-- RELAÇÃO VETERINÁRIO X ESPECIALIDADE
-- ============================================================

CREATE TABLE veterinario_especialidades (

    veterinario_id INT NOT NULL,

    especialidade_id INT NOT NULL,

    PRIMARY KEY (

        veterinario_id,

        especialidade_id

    ),

    CONSTRAINT fk_ve_veterinario
        FOREIGN KEY (veterinario_id)
        REFERENCES veterinarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ve_especialidade
        FOREIGN KEY (especialidade_id)
        REFERENCES especialidades(id)
        ON DELETE CASCADE

);

-- ============================================================
-- AGENDA
-- ============================================================

CREATE TABLE agenda_veterinaria (

    id INT AUTO_INCREMENT PRIMARY KEY,

    veterinario_id INT NOT NULL,

    data DATE NOT NULL,

    hora_inicio TIME NOT NULL,

    hora_fim TIME NOT NULL,

    disponivel BOOLEAN DEFAULT TRUE,

    observacoes VARCHAR(255),

    CONSTRAINT fk_agenda_veterinario
        FOREIGN KEY (veterinario_id)
        REFERENCES veterinarios(id)
        ON DELETE CASCADE

);

-- ============================================================
-- CONSULTAS
-- ============================================================

CREATE TABLE consultas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    veterinario_id INT NOT NULL,

    usuario_id INT NOT NULL,

    pet_id INT NULL,

    data_consulta DATE NOT NULL,

    hora_consulta TIME NOT NULL,

    status ENUM(

        'Agendada',

        'Confirmada',

        'Em Atendimento',

        'Concluída',

        'Cancelada'

    ) DEFAULT 'Agendada',

    motivo TEXT,

    observacoes TEXT,

    valor DECIMAL(10,2),

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_consulta_veterinario
        FOREIGN KEY (veterinario_id)
        REFERENCES veterinarios(id),

    CONSTRAINT fk_consulta_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- PRONTUÁRIOS
-- ============================================================

CREATE TABLE prontuarios (

    id INT AUTO_INCREMENT PRIMARY KEY,

    consulta_id INT NOT NULL,

    diagnostico TEXT,

    tratamento TEXT,

    medicamentos TEXT,

    recomendacoes TEXT,

    retorno DATE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_prontuario_consulta
        FOREIGN KEY (consulta_id)
        REFERENCES consultas(id)
        ON DELETE CASCADE

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_veterinario_crmv
ON veterinarios(crmv);

CREATE INDEX idx_veterinario_avaliacao
ON veterinarios(avaliacao);

CREATE INDEX idx_consulta_data
ON consultas(data_consulta);

CREATE INDEX idx_consulta_status
ON consultas(status);

CREATE INDEX idx_agenda_data
ON agenda_veterinaria(data);

-- ============================================================
-- ESPECIALIDADES PADRÃO
-- ============================================================

INSERT INTO especialidades
(nome)

VALUES

('Clínico Geral'),

('Cardiologia'),

('Dermatologia'),

('Ortopedia'),

('Neurologia'),

('Oftalmologia'),

('Odontologia'),

('Oncologia'),

('Cirurgia'),

('Animais Silvestres'),

('Felinos'),

('Caninos'),

('Animais Exóticos'),

('Diagnóstico por Imagem'),

('Emergência 24 Horas');

-- ============================================================
-- FIM DA PARTE 3
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 4
   Pets e Histórico Médico
============================================================ */

-- ============================================================
-- ESPÉCIES
-- ============================================================

CREATE TABLE especies (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL UNIQUE,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- RAÇAS
-- ============================================================

CREATE TABLE racas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    especie_id INT NOT NULL,

    nome VARCHAR(150) NOT NULL,

    porte ENUM(

        'Pequeno',

        'Médio',

        'Grande',

        'Gigante'

    ) DEFAULT 'Médio',

    ativo BOOLEAN DEFAULT TRUE,

    CONSTRAINT fk_raca_especie

        FOREIGN KEY (especie_id)

        REFERENCES especies(id)

        ON DELETE CASCADE

);

-- ============================================================
-- PETS
-- ============================================================

CREATE TABLE pets (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    especie_id INT NOT NULL,

    raca_id INT NOT NULL,

    nome VARCHAR(150) NOT NULL,

    sexo ENUM(

        'Macho',

        'Fêmea'

    ) NOT NULL,

    cor VARCHAR(100),

    peso DECIMAL(6,2),

    altura DECIMAL(6,2),

    data_nascimento DATE,

    microchip VARCHAR(100),

    castrado BOOLEAN DEFAULT FALSE,

    foto VARCHAR(255),

    observacoes TEXT,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pet_usuario

        FOREIGN KEY (usuario_id)

        REFERENCES usuarios(id)

        ON DELETE CASCADE,

    CONSTRAINT fk_pet_especie

        FOREIGN KEY (especie_id)

        REFERENCES especies(id),

    CONSTRAINT fk_pet_raca

        FOREIGN KEY (raca_id)

        REFERENCES racas(id)

);

-- ============================================================
-- VACINAS
-- ============================================================

CREATE TABLE pet_favoritos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    pet_id INT NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_favorito (usuario_id, pet_id),

    FOREIGN KEY (usuario_id)

        REFERENCES usuarios(id)

        ON DELETE CASCADE,

    FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE

);

CREATE TABLE pet_imagens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    arquivo VARCHAR(255) NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE

);

CREATE TABLE vacinas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL UNIQUE,

    descricao TEXT,

    periodicidade VARCHAR(100),

    obrigatoria BOOLEAN DEFAULT FALSE

);

-- ============================================================
-- PET X VACINAS
-- ============================================================

CREATE TABLE pet_vacinas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    vacina_id INT NOT NULL,

    data_aplicacao DATE,

    proxima_dose DATE,

    veterinario VARCHAR(150),

    lote VARCHAR(100),

    observacoes TEXT,

    CONSTRAINT fk_petvacina_pet

        FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE,

    CONSTRAINT fk_petvacina_vacina

        FOREIGN KEY (vacina_id)

        REFERENCES vacinas(id)

);

-- ============================================================
-- HISTÓRICO MÉDICO
-- ============================================================

CREATE TABLE historico_medico (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    veterinario_id INT NULL,

    data_atendimento DATE,

    diagnostico TEXT,

    tratamento TEXT,

    medicamentos TEXT,

    observacoes TEXT,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_historico_pet

        FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE,

    CONSTRAINT fk_historico_veterinario

        FOREIGN KEY (veterinario_id)

        REFERENCES veterinarios(id)

);

-- ============================================================
-- ALERGIAS
-- ============================================================

CREATE TABLE alergias (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    descricao TEXT NOT NULL,

    severidade ENUM(

        'Baixa',

        'Média',

        'Alta'

    ) DEFAULT 'Baixa',

    CONSTRAINT fk_alergia_pet

        FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE

);

-- ============================================================
-- PESAGENS
-- ============================================================

CREATE TABLE pesagens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pet_id INT NOT NULL,

    peso DECIMAL(6,2) NOT NULL,

    data_pesagem DATE NOT NULL,

    observacoes VARCHAR(255),

    CONSTRAINT fk_pesagem_pet

        FOREIGN KEY (pet_id)

        REFERENCES pets(id)

        ON DELETE CASCADE

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_pet_nome
ON pets(nome);

CREATE INDEX idx_pet_usuario
ON pets(usuario_id);

CREATE INDEX idx_pet_raca
ON pets(raca_id);

CREATE INDEX idx_historico_pet
ON historico_medico(pet_id);

CREATE INDEX idx_pesagem_pet
ON pesagens(pet_id);

-- ============================================================
-- ESPÉCIES PADRÃO
-- ============================================================

INSERT INTO especies (nome)

VALUES

('Cachorro'),

('Gato'),

('Ave'),

('Peixe'),

('Coelho'),

('Hamster'),

('Porquinho-da-Índia'),

('Réptil'),

('Cavalo'),

('Animal Silvestre');

-- ============================================================
-- VACINAS PADRÃO
-- ============================================================

INSERT INTO vacinas (

    nome,

    obrigatoria

)

VALUES

('V10',TRUE),

('V8',TRUE),

('Antirrábica',TRUE),

('Giárdia',FALSE),

('Gripe Canina',FALSE),

('Quádrupla Felina',TRUE),

('Leucemia Felina',FALSE),

('Raiva Felina',TRUE);

-- ============================================================
-- FIM DA PARTE 4
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 5
   Marketplace
============================================================ */

-- ============================================================
-- MARCAS
-- ============================================================

CREATE TABLE marcas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL UNIQUE,

    descricao TEXT,

    logo VARCHAR(255),

    site VARCHAR(255),

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- FORNECEDORES
-- ============================================================

CREATE TABLE fornecedores (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(180) NOT NULL,

    cnpj CHAR(14),

    telefone VARCHAR(20),

    email VARCHAR(180),

    contato VARCHAR(150),

    endereco VARCHAR(255),

    cidade VARCHAR(120),

    estado CHAR(2),

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- PRODUTOS
-- ============================================================

CREATE TABLE produtos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    empresa_id INT NOT NULL,

    categoria_id INT NOT NULL,

    subcategoria_id INT DEFAULT NULL,

    marca_id INT,

    fornecedor_id INT,

    nome VARCHAR(200) NOT NULL,

    descricao TEXT,

    sku VARCHAR(80) UNIQUE,

    codigo_barras VARCHAR(50),

    peso DECIMAL(8,2),

    altura DECIMAL(8,2),

    largura DECIMAL(8,2),

    comprimento DECIMAL(8,2),

    preco_custo DECIMAL(10,2),

    preco_venda DECIMAL(10,2) NOT NULL,

    preco_promocional DECIMAL(10,2),

    destaque BOOLEAN DEFAULT FALSE,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id),

    CONSTRAINT fk_produto_marca
        FOREIGN KEY (marca_id)
        REFERENCES marcas(id),

    CONSTRAINT fk_produto_fornecedor
        FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id)

);

-- ============================================================
-- IMAGENS DOS PRODUTOS
-- ============================================================

CREATE TABLE produto_imagens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    produto_id INT NOT NULL,

    imagem VARCHAR(255) NOT NULL,

    principal BOOLEAN DEFAULT FALSE,

    ordem INT DEFAULT 1,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_imagem_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE CASCADE

);

-- ============================================================
-- ESTOQUE
-- ============================================================

CREATE TABLE estoque (

    id INT AUTO_INCREMENT PRIMARY KEY,

    produto_id INT NOT NULL,

    quantidade INT DEFAULT 0,

    estoque_minimo INT DEFAULT 0,

    estoque_maximo INT DEFAULT 0,

    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_estoque_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE CASCADE

);

-- ============================================================
-- PROMOÇÕES
-- ============================================================

CREATE TABLE promocoes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    produto_id INT NOT NULL,

    titulo VARCHAR(200),

    descricao TEXT,

    desconto DECIMAL(5,2),

    data_inicio DATE,

    data_fim DATE,

    ativa BOOLEAN DEFAULT TRUE,

    CONSTRAINT fk_promocao_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE CASCADE

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_produto_nome
ON produtos(nome);

CREATE INDEX idx_produto_categoria
ON produtos(categoria_id);

CREATE INDEX idx_produto_preco
ON produtos(preco_venda);

CREATE INDEX idx_produto_destaque
ON produtos(destaque);

CREATE INDEX idx_estoque_produto
ON estoque(produto_id);

-- ============================================================
-- MARCAS PADRÃO
-- ============================================================

INSERT INTO marcas (nome)

VALUES

('Royal Canin'),

('Premier Pet'),

('Golden'),

('Purina'),

('Pedigree'),

('Whiskas'),

('GranPlus'),

('Special Dog'),

('Baw Waw'),

('Pet Society');

-- ============================================================
-- FIM DA PARTE 5
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 6
   Carrinho • Pedidos • Pagamentos
============================================================ */

-- ============================================================
-- FORMAS DE PAGAMENTO
-- ============================================================

CREATE TABLE formas_pagamento (

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL,

    descricao VARCHAR(255),

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- CARRINHO
-- ============================================================

CREATE TABLE carrinho (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_carrinho_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE

);

-- ============================================================
-- ITENS DO CARRINHO
-- ============================================================

CREATE TABLE carrinho_itens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    carrinho_id INT NOT NULL,

    produto_id INT NOT NULL,

    quantidade INT NOT NULL DEFAULT 1,

    preco_unitario DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_item_carrinho
        FOREIGN KEY (carrinho_id)
        REFERENCES carrinho(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_item_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)

);

-- ============================================================
-- PEDIDOS
-- ============================================================

CREATE TABLE pedidos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    numero_pedido VARCHAR(30) UNIQUE,

    valor_produtos DECIMAL(10,2) DEFAULT 0,

    valor_frete DECIMAL(10,2) DEFAULT 0,

    valor_desconto DECIMAL(10,2) DEFAULT 0,

    valor_total DECIMAL(10,2) DEFAULT 0,

    status ENUM(

        'Aguardando Pagamento',

        'Pago',

        'Separação',

        'Enviado',

        'Entregue',

        'Cancelado'

    ) DEFAULT 'Aguardando Pagamento',

    observacoes TEXT,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pedido_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- ITENS DO PEDIDO
-- ============================================================

CREATE TABLE pedido_itens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pedido_id INT NOT NULL,

    produto_id INT NOT NULL,

    quantidade INT NOT NULL,

    preco_unitario DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_pedido_item
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pedido_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)

);

-- ============================================================
-- PAGAMENTOS
-- ============================================================

CREATE TABLE pagamentos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pedido_id INT NOT NULL,

    forma_pagamento_id INT NOT NULL,

    valor DECIMAL(10,2) NOT NULL,

    status ENUM(

        'Pendente',

        'Aprovado',

        'Recusado',

        'Estornado'

    ) DEFAULT 'Pendente',

    codigo_transacao VARCHAR(120),

    data_pagamento DATETIME,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pagamento_pedido
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pagamento_forma
        FOREIGN KEY (forma_pagamento_id)
        REFERENCES formas_pagamento(id)

);

-- ============================================================
-- CUPONS
-- ============================================================

CREATE TABLE cupons (

    id INT AUTO_INCREMENT PRIMARY KEY,

    codigo VARCHAR(50) UNIQUE NOT NULL,

    descricao VARCHAR(255),

    tipo ENUM(

        'Percentual',

        'Valor'

    ) DEFAULT 'Percentual',

    valor DECIMAL(10,2) NOT NULL,

    valor_minimo DECIMAL(10,2),

    data_inicio DATE,

    data_fim DATE,

    quantidade INT DEFAULT 0,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- CUPONS UTILIZADOS
-- ============================================================

CREATE TABLE cupons_utilizados (

    id INT AUTO_INCREMENT PRIMARY KEY,

    cupom_id INT NOT NULL,

    pedido_id INT NOT NULL,

    usuario_id INT NOT NULL,

    data_utilizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_utilizado_cupom
        FOREIGN KEY (cupom_id)
        REFERENCES cupons(id),

    CONSTRAINT fk_utilizado_pedido
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id),

    CONSTRAINT fk_utilizado_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_pedido_usuario
ON pedidos(usuario_id);

CREATE INDEX idx_pedido_status
ON pedidos(status);

CREATE INDEX idx_pagamento_status
ON pagamentos(status);

CREATE INDEX idx_cupom_codigo
ON cupons(codigo);

-- ============================================================
-- FORMAS DE PAGAMENTO PADRÃO
-- ============================================================

INSERT INTO formas_pagamento (nome, descricao)

VALUES

('PIX','Pagamento instantâneo'),

('Cartão de Crédito','Visa, Mastercard, Elo e outros'),

('Cartão de Débito','Débito em conta'),

('Boleto Bancário','Pagamento via boleto'),

('Dinheiro','Pagamento na entrega');

-- ============================================================
-- CUPONS INICIAIS
-- ============================================================

INSERT INTO cupons (

    codigo,

    descricao,

    tipo,

    valor,

    valor_minimo,

    quantidade

)

VALUES

('BEMVINDO10','10% de desconto na primeira compra','Percentual',10.00,50.00,1000),

('FRETEGRATIS','Cupom promocional de lançamento','Valor',20.00,150.00,500);

-- ============================================================
-- FIM DA PARTE 6
-- ============================================================


/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 7
   Avaliações • Favoritos • Chat • Suporte
============================================================ */

-- ============================================================
-- FAVORITOS
-- ============================================================

CREATE TABLE favoritos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    empresa_id INT NULL,

    produto_id INT NULL,

    veterinario_id INT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorito_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorito_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorito_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorito_veterinario
        FOREIGN KEY (veterinario_id)
        REFERENCES veterinarios(id)
        ON DELETE CASCADE

);

-- ============================================================
-- AVALIAÇÕES
-- ============================================================

CREATE TABLE avaliacoes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    empresa_id INT NULL,

    produto_id INT NULL,

    veterinario_id INT NULL,

    nota TINYINT NOT NULL,

    titulo VARCHAR(200),

    comentario TEXT,

    recomendado BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_avaliacao_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id),

    CONSTRAINT fk_avaliacao_empresa
        FOREIGN KEY (empresa_id)
        REFERENCES empresas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_avaliacao_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_avaliacao_veterinario
        FOREIGN KEY (veterinario_id)
        REFERENCES veterinarios(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_nota
        CHECK (nota BETWEEN 1 AND 5)

);

-- ============================================================
-- CONVERSAS
-- ============================================================

CREATE TABLE conversas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_origem INT NOT NULL,

    usuario_destino INT NOT NULL,

    assunto VARCHAR(200),

    status ENUM(

        'Aberta',

        'Fechada',

        'Arquivada'

    ) DEFAULT 'Aberta',

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_conversa_origem
        FOREIGN KEY (usuario_origem)
        REFERENCES usuarios(id),

    CONSTRAINT fk_conversa_destino
        FOREIGN KEY (usuario_destino)
        REFERENCES usuarios(id)

);

-- ============================================================
-- MENSAGENS
-- ============================================================

CREATE TABLE mensagens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    conversa_id INT NOT NULL,

    remetente_id INT NOT NULL,

    mensagem TEXT NOT NULL,

    lida BOOLEAN DEFAULT FALSE,

    enviado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mensagem_conversa
        FOREIGN KEY (conversa_id)
        REFERENCES conversas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mensagem_usuario
        FOREIGN KEY (remetente_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- NOTIFICAÇÕES
-- ============================================================

CREATE TABLE notificacoes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    titulo VARCHAR(200),

    mensagem TEXT,

    tipo ENUM(

        'Sistema',

        'Pedido',

        'Pagamento',

        'Consulta',

        'Promoção'

    ) DEFAULT 'Sistema',

    lida BOOLEAN DEFAULT FALSE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE

);

-- ============================================================
-- CHAMADOS DE SUPORTE
-- ============================================================

CREATE TABLE suporte (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    assunto VARCHAR(200),

    descricao TEXT,

    prioridade ENUM(

        'Baixa',

        'Média',

        'Alta',

        'Urgente'

    ) DEFAULT 'Média',

    status ENUM(

        'Aberto',

        'Em Atendimento',

        'Resolvido',

        'Fechado'

    ) DEFAULT 'Aberto',

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_suporte_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- RESPOSTAS DO SUPORTE
-- ============================================================

CREATE TABLE suporte_respostas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    chamado_id INT NOT NULL,

    usuario_id INT NOT NULL,

    resposta TEXT NOT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resposta_chamado
        FOREIGN KEY (chamado_id)
        REFERENCES suporte(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_resposta_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_favoritos_usuario
ON favoritos(usuario_id);

CREATE INDEX idx_avaliacoes_usuario
ON avaliacoes(usuario_id);

CREATE INDEX idx_notificacoes_usuario
ON notificacoes(usuario_id);

CREATE INDEX idx_suporte_status
ON suporte(status);

CREATE INDEX idx_mensagens_conversa
ON mensagens(conversa_id);

-- ============================================================
-- FIM DA PARTE 7
-- ============================================================

/* ============================================================
   PETFINDER BRASIL
   DATABASE
   PARTE 8 (FINAL)
   Configurações • Newsletter • Logs • Views
============================================================ */

-- ============================================================
-- CONFIGURAÇÕES DO SISTEMA
-- ============================================================

CREATE TABLE configuracoes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    chave_config VARCHAR(120) NOT NULL UNIQUE,

    valor_config TEXT,

    descricao VARCHAR(255),

    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

);

-- ============================================================
-- NEWSLETTER
-- ============================================================

CREATE TABLE newsletter (

    id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(180) NOT NULL UNIQUE,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- LOG DE ACESSO
-- ============================================================

CREATE TABLE log_acessos (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NULL,

    ip VARCHAR(45),

    navegador VARCHAR(255),

    sistema_operacional VARCHAR(120),

    url VARCHAR(255),

    data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_usuario

        FOREIGN KEY (usuario_id)

        REFERENCES usuarios(id)

        ON DELETE SET NULL

);

-- ============================================================
-- LOG DE AUDITORIA
-- ============================================================

CREATE TABLE auditoria (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NULL,

    tabela VARCHAR(120),

    acao ENUM(

        'INSERT',

        'UPDATE',

        'DELETE'

    ),

    registro_id INT,

    detalhes TEXT,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auditoria_usuario

        FOREIGN KEY(usuario_id)

        REFERENCES usuarios(id)

        ON DELETE SET NULL

);

-- ============================================================
-- BANNERS
-- ============================================================

CREATE TABLE banners (

    id INT AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(180),

    subtitulo VARCHAR(255),

    imagem VARCHAR(255),

    link VARCHAR(255),

    ordem INT DEFAULT 1,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- ============================================================
-- FAQ
-- ============================================================

CREATE TABLE faq (

    id INT AUTO_INCREMENT PRIMARY KEY,

    pergunta VARCHAR(255) NOT NULL,

    resposta TEXT NOT NULL,

    ordem INT DEFAULT 1,

    ativo BOOLEAN DEFAULT TRUE

);

-- ============================================================
-- VIEW PRODUTOS
-- ============================================================

CREATE VIEW vw_produtos AS

SELECT

    p.id,

    p.nome,

    p.preco_venda,

    p.preco_promocional,

    c.nome AS categoria,

    m.nome AS marca

FROM produtos p

LEFT JOIN categorias c

ON p.categoria_id = c.id

LEFT JOIN marcas m

ON p.marca_id = m.id;

-- ============================================================
-- VIEW EMPRESAS
-- ============================================================

CREATE VIEW vw_empresas AS

SELECT

    e.id,

    e.nome_fantasia,

    e.cidade,

    e.estado,

    c.nome AS categoria,

    e.avaliacao

FROM empresas e

INNER JOIN categorias c

ON e.categoria_id = c.id;

-- ============================================================
-- TRIGGER
-- ============================================================

DELIMITER $$

CREATE TRIGGER trg_atualiza_estoque

AFTER INSERT ON pedido_itens

FOR EACH ROW

BEGIN

    UPDATE estoque

    SET quantidade = quantidade - NEW.quantidade

    WHERE produto_id = NEW.produto_id;

END$$

DELIMITER ;

-- ============================================================
-- CONFIGURAÇÕES INICIAIS
-- ============================================================

INSERT INTO configuracoes

(chave_config, valor_config, descricao)

VALUES

('nome_sistema','PetFinder Brasil','Nome da plataforma'),

('email_suporte','suporte@petfinder.com','Contato principal'),

('telefone','(31) 99999-9999','Telefone principal'),

('manutencao','false','Modo manutenção'),

('versao','1.0.0','Versão do sistema');

-- ============================================================
-- FAQ PADRÃO
-- ============================================================

INSERT INTO faq

(pergunta,resposta)

VALUES

('Como cadastrar meu pet?',
'Crie uma conta e utilize o menu "Meu Pet".'),

('Como marcar consulta?',
'Escolha um veterinário e selecione um horário disponível.'),

('Como comprar produtos?',
'Adicione os itens ao carrinho e finalize o pedido.'),

('Como anunciar minha empresa?',
'Cadastre-se como empresa e envie sua documentação.');

-- ============================================================
-- BANNERS INICIAIS
-- ============================================================

INSERT INTO banners

(titulo,subtitulo,imagem,ordem)

VALUES

('Bem-vindo ao PetFinder Brasil',
'A maior plataforma para quem ama pets.',
'banner1.jpg',1),

('Marketplace Pet',
'Milhares de produtos com entrega rápida.',
'banner2.jpg',2),

('Veterinários Próximos',
'Agende consultas em poucos minutos.',
'banner3.jpg',3);

-- ============================================================
-- ÍNDICES FINAIS
-- ============================================================

CREATE INDEX idx_newsletter_email

ON newsletter(email);

CREATE INDEX idx_banner_ordem

ON banners(ordem);

CREATE INDEX idx_log_usuario

ON log_acessos(usuario_id);

-- ============================================================
-- FIM DO BANCO
-- ============================================================

/*
==============================================================

PETFINDER BRASIL
VERSÃO 1.0

Estrutura criada:

✔ Usuários
✔ Perfis
✔ Endereços

✔ Empresas
✔ Categorias
✔ Subcategorias
✔ Horários
✔ Galeria

✔ Veterinários
✔ Especialidades
✔ Agenda
✔ Consultas
✔ Prontuários

✔ Pets
✔ Espécies
✔ Raças
✔ Vacinas
✔ Histórico Médico

✔ Marketplace
✔ Produtos
✔ Marcas
✔ Fornecedores
✔ Estoque
✔ Promoções

✔ Carrinho
✔ Pedidos
✔ Pagamentos
✔ Cupons

✔ Favoritos
✔ Avaliações
✔ Chat
✔ Notificações
✔ Suporte

✔ Newsletter
✔ FAQ
✔ Banners
✔ Logs
✔ Auditoria
✔ Views
✔ Trigger

Banco preparado para:

PHP 8+
MySQL 8+
MariaDB
PDO
Bootstrap
API REST

==============================================================
FIM DO ARQUIVO
==============================================================
*/