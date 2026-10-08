-- ==========================================================
-- PetFinder Brasil — dados fictícios para demonstração
-- ==========================================================
-- Popula o banco com usuários, pets, empresas e prestadores fictícios,
-- só para dar a impressão de um site "em uso" numa apresentação.
--
-- Seguro pra rodar num banco que já tem dados de teste seus (não
-- sobrescreve nada, não usa IDs fixos — deixa o AUTO_INCREMENT cuidar
-- disso e encadeia os relacionamentos via LAST_INSERT_ID()).
--
-- Senha de todos os usuários fictícios abaixo: Demo@123
-- (hash bcrypt já pronto, compatível com password_verify() do PHP)
--
-- Fotos: as de pets/prestadores/empresas vêm de fotos que você mesmo
-- enviou (banco de imagens Pexels + fotos de uso livre), já copiadas
-- para uploads/pets, uploads/prestadores e uploads/empresas. Alguns
-- pets ficam sem foto de propósito (o site já mostra um ícone da
-- espécie nesse caso) — não tínhamos fotos suficientes pra todo mundo
-- sem repetir a mesma foto em pets diferentes.

SET @senha_demo := '$2b$10$tJW839Ia38qxOcB5RtWtXuLIjpPM5DVIwVkFMffzOvaje3f8QUodG';

-- ==========================================================
-- 1) TUTORES (usuarios comuns, tipo_usuario = 'cliente')
-- ==========================================================

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Mariana', 'Souza', 'mariana.souza.demo@petfinder.local', @senha_demo, '(37) 99123-4501', 'Feminino', 'ativo', 'cliente');
SET @tutor_mariana := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_mariana, '36400-000', 'Rua das Acácias', '120', 'Centro', 'Conselheiro Lafaiete', 'MG', 1);

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Rafael', 'Andrade', 'rafael.andrade.demo@petfinder.local', @senha_demo, '(31) 99123-4502', 'Masculino', 'ativo', 'cliente');
SET @tutor_rafael := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_rafael, '30130-000', 'Av. Afonso Pena', '2400', 'Centro', 'Belo Horizonte', 'MG', 1);

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Juliana', 'Ferreira', 'juliana.ferreira.demo@petfinder.local', @senha_demo, '(31) 99123-4503', 'Feminino', 'ativo', 'cliente');
SET @tutor_juliana := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_juliana, '32010-000', 'Rua Silviano Brandão', '85', 'Eldorado', 'Contagem', 'MG', 1);

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Bruno', 'Costa', 'bruno.costa.demo@petfinder.local', @senha_demo, '(31) 99123-4504', 'Masculino', 'ativo', 'cliente');
SET @tutor_bruno := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_bruno, '36420-000', 'Rua XV de Novembro', '310', 'Centro', 'Ouro Branco', 'MG', 1);

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Camila', 'Rocha', 'camila.rocha.demo@petfinder.local', @senha_demo, '(31) 99123-4505', 'Feminino', 'ativo', 'cliente');
SET @tutor_camila := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_camila, '32600-000', 'Rua Ceará', '540', 'Centro', 'Betim', 'MG', 1);

INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Thiago', 'Almeida', 'thiago.almeida.demo@petfinder.local', @senha_demo, '(37) 99123-4506', 'Masculino', 'ativo', 'cliente');
SET @tutor_thiago := LAST_INSERT_ID();
INSERT INTO enderecos (usuario_id, cep, logradouro, numero, bairro, cidade, estado, principal)
VALUES (@tutor_thiago, '36400-000', 'Rua Barão de Queluz', '77', 'Santo Antônio', 'Conselheiro Lafaiete', 'MG', 1);

-- ==========================================================
-- 2) EMPRESAS (dono novo, tipo_usuario = 'empresa')
-- ==========================================================

-- Petshop Amigo Fiel (categoria 1 = Pet Shop)
INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Patrícia', 'Lima', 'contato.amigofiel.demo@petfinder.local', @senha_demo, '(37) 3333-1001', 'Feminino', 'ativo', 'empresa');
SET @dono_amigofiel := LAST_INSERT_ID();
INSERT INTO empresas (usuario_id, categoria_id, nome_fantasia, descricao, telefone, whatsapp, email, cidade, estado, bairro, endereco, numero, avaliacao, total_avaliacoes, verificada, ativo, status_pagamento)
VALUES (@dono_amigofiel, 1, 'Petshop Amigo Fiel',
        'Tudo para o seu pet num só lugar: ração, brinquedos, acessórios e banho e tosa com hora marcada.',
        '(37) 3333-1001', '(37) 99999-1001', 'contato@amigofiel.demo',
        'Conselheiro Lafaiete', 'MG', 'Centro', 'Rua Dr. Mário Rodrigues', '210',
        4.7, 32, 1, 1, 'Ativo');
SET @empresa_amigofiel := LAST_INSERT_ID();

-- Clínica VetCare (categoria 2 = Clínica Veterinária)
INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Eduardo', 'Martins', 'contato.vetcare.demo@petfinder.local', @senha_demo, '(31) 3333-1002', 'Masculino', 'ativo', 'empresa');
SET @dono_vetcare := LAST_INSERT_ID();
INSERT INTO empresas (usuario_id, categoria_id, nome_fantasia, descricao, telefone, whatsapp, email, cidade, estado, bairro, endereco, numero, logo, avaliacao, total_avaliacoes, verificada, ativo, status_pagamento)
VALUES (@dono_vetcare, 2, 'Clínica VetCare',
        'Consultas, exames, vacinação e cirurgias. Equipe especializada em pequenos animais.',
        '(31) 3333-1002', '(31) 99999-1002', 'contato@vetcare.demo',
        'Belo Horizonte', 'MG', 'Savassi', 'Rua Pium-í', '450',
        'empresa_vetcare.jpg', 4.9, 58, 1, 1, 'Ativo');
SET @empresa_vetcare := LAST_INSERT_ID();

-- Hotelzinho Cão Feliz (categoria 5 = Hotel para Pets)
INSERT INTO usuarios (nome, sobrenome, email, senha, telefone, genero, status, tipo_usuario)
VALUES ('Fernanda', 'Dias', 'contato.caofeliz.demo@petfinder.local', @senha_demo, '(31) 3333-1003', 'Feminino', 'ativo', 'empresa');
SET @dono_caofeliz := LAST_INSERT_ID();
INSERT INTO empresas (usuario_id, categoria_id, nome_fantasia, descricao, telefone, whatsapp, email, cidade, estado, bairro, endereco, numero, logo, avaliacao, total_avaliacoes, verificada, ativo, status_pagamento)
VALUES (@dono_caofeliz, 5, 'Hotelzinho Cão Feliz',
        'Hospedagem e day care para cães, com área externa, monitoramento e atividades em grupo.',
        '(31) 3333-1003', '(31) 99999-1003', 'contato@caofeliz.demo',
        'Contagem', 'MG', 'Eldorado', 'Rua Santa Rita', '88',
        'empresa_hotelzinho.jpg', 4.6, 21, 0, 1, 'Ativo');
SET @empresa_caofeliz := LAST_INSERT_ID();

-- ==========================================================
-- 3) PRESTADORES DE SERVIÇO (ligados a tutores já criados)
-- ==========================================================

INSERT INTO prestadores_servico
    (usuario_id, tipo, cidade, estado, foto, tempo_experiencia, formacao, apresentacao, valor_hora, status, ativo)
VALUES
    (@tutor_bruno, 'adestrador', 'Ouro Branco', 'MG', 'prestador_thiago.jpg', '6 anos',
     'Formação em adestramento comportamental',
     'Trabalho com adestramento positivo para cães de todas as idades e portes. Atendimento na casa do tutor.',
     90.00, 'aprovado', 1);

INSERT INTO prestadores_servico
    (usuario_id, tipo, cidade, estado, foto, tempo_experiencia, formacao, apresentacao, valor_diaria, status, ativo)
VALUES
    (@tutor_camila, 'pet_sitter', 'Betim', 'MG', 'prestador_fernanda.jpg', '4 anos',
     'Cuidadora de animais certificada',
     'Cuido do seu pet na sua ausência: alimentação, passeios e companhia. Referências disponíveis.',
     80.00, 'aprovado', 1);

INSERT INTO prestadores_servico
    (usuario_id, tipo, cidade, estado, foto, tempo_experiencia, apresentacao, valor_hora, status, ativo)
VALUES
    (@tutor_thiago, 'passeador', 'Conselheiro Lafaiete', 'MG', 'prestador_lucas.jpg', '3 anos',
     'Passeios diários com horário fixo ou avulso, individual ou em grupo pequeno (máx. 4 cães).',
     35.00, 'aprovado', 1);

-- ==========================================================
-- 4) PETS (distribuídos entre os tutores acima)
-- ==========================================================

-- especie_id: 1=Cachorro 2=Gato 3=Ave 5=Coelho | raca_id conforme database/petfinder.sql

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_mariana, 2, 11, 'Para Adoção', 'Mia', 'Fêmea', 'Laranja e branco', 3.80, 1, 'pet_mia.jpg',
        'Gata dócil, gosta de colo e já é castrada. Ótima com crianças.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_rafael, 1, 1, 'Para Adoção', 'Bidu', 'Macho', 'Caramelo', 8.20, 0, 'pet_bidu.jpg',
        'Filhote vira-lata, muito brincalhão, já toma as vacinas em dia.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_juliana, 1, 6, 'Para Adoção', 'Thor', 'Macho', 'Dourado', 12.50, 1, 'pet_thor.jpg',
        'Muito sociável, adora crianças e outros cães. Castrado e vacinado.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_bruno, 1, 5, 'Perdido', 'Rex', 'Macho', 'Preto e marrom', 28.00, 1, 'pet_rex.jpg',
        'Sumiu perto da Rua XV de Novembro, em Ouro Branco. Usa coleira azul e vermelha.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_camila, 1, 7, 'Encontrado', 'Nina', 'Fêmea', 'Branco e cinza', 6.40, 0, 'pet_nina.jpg',
        'Encontrada perto do centro de Betim, bem cuidada, provavelmente tem tutor. Procurando a família dela.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_thiago, 2, 12, 'Para Adoção', 'Luna', 'Fêmea', 'Bege e marrom', 4.10, 1, 'sem-foto.png',
        'Gata siamesa, tranquila, se adapta bem a apartamento.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_mariana, 3, 18, 'Para Adoção', 'Kiwi', 'Macho', 'Cinza e amarelo', 0.09, 0, 'sem-foto.png',
        'Calopsita mansa, já assobia algumas melodias.');

INSERT INTO pets (usuario_id, especie_id, raca_id, status, nome, sexo, cor, peso, castrado, foto, observacoes)
VALUES (@tutor_rafael, 2, 13, 'Com Tutor', 'Simba', 'Macho', 'Cinza', 5.20, 1, 'sem-foto.png',
        'Gato persa da família, aparece aqui só como exemplo de pet "com tutor".');

-- ==========================================================
-- 5) PRODUTOS (do Petshop Amigo Fiel — sem foto própria; o site já
--    mostra um espaço reservado neutro quando não há imagem)
-- ==========================================================
-- categoria_id 9 = Marketplace | subcategoria_id: 1=Ração 2=Brinquedos 3=Higiene 4=Acessórios

INSERT INTO produtos (categoria_id, subcategoria_id, marca_id, empresa_id, nome, descricao, preco_venda, preco_promocional, destaque, ativo)
VALUES (9, 1, 3, @empresa_amigofiel, 'Ração Golden Fórmula Adulto 15kg', 'Ração premium para cães adultos de porte médio.', 189.90, NULL, 1, 1);

INSERT INTO produtos (categoria_id, subcategoria_id, marca_id, empresa_id, nome, descricao, preco_venda, preco_promocional, destaque, ativo)
VALUES (9, 2, NULL, @empresa_amigofiel, 'Brinquedo Mordedor de Borracha', 'Resistente, ideal para cães que mastigam bastante.', 34.90, 24.90, 0, 1);

INSERT INTO produtos (categoria_id, subcategoria_id, marca_id, empresa_id, nome, descricao, preco_venda, preco_promocional, destaque, ativo)
VALUES (9, 3, NULL, @empresa_amigofiel, 'Shampoo Neutro Pet Clean 500ml', 'Shampoo hipoalergênico para todos os tipos de pelagem.', 29.90, NULL, 0, 1);

INSERT INTO produtos (categoria_id, subcategoria_id, marca_id, empresa_id, nome, descricao, preco_venda, preco_promocional, destaque, ativo)
VALUES (9, 4, NULL, @empresa_amigofiel, 'Coleira Ajustável Nylon', 'Coleira resistente com fivela de encaixe rápido, tamanho M.', 39.90, NULL, 0, 1);

INSERT INTO produtos (categoria_id, subcategoria_id, marca_id, empresa_id, nome, descricao, preco_venda, preco_promocional, destaque, ativo)
VALUES (9, 1, 6, @empresa_amigofiel, 'Ração Whiskas Sachê Gatos Adultos', 'Pacote com 12 sachês, sabor carne ao molho.', 42.90, NULL, 0, 1);
