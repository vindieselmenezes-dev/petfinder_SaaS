-- PetFinder: dados de exemplo da área de parceiros.
--
-- OPCIONAL — serve só para ver as telas cheias durante o
-- desenvolvimento/apresentação. Não rode em produção.
--
-- Requisito: a migration_024_parceiros_campanhas.sql já aplicada e pelo
-- menos um usuário na tabela `usuarios` (o primeiro vira o responsável
-- por todos os parceiros de exemplo).

SET @usuario := (SELECT MIN(id) FROM usuarios);

INSERT INTO parceiros
    (usuario_id, tipo, nome, descricao, como_ajuda, cidade, estado,
     instagram, whatsapp, email_contato, chave_pix, aceita_voluntarios,
     destaque, status)
VALUES
    (@usuario, 'ong', 'Patas do Bem',
     'Grupo de protetoras independentes que resgata, trata e encaminha para adoção cães e gatos abandonados. Atuamos desde 2016 e já encontramos lar para mais de 900 animais.',
     'Divulgamos os pets perdidos do PetFinder nas nossas redes e nas feiras de adoção',
     'Belo Horizonte', 'MG', 'patasdobem', '31999990001', 'contato@patasdobem.org.br',
     'patasdobem@pix.com.br', 1, 1, 'aprovado'),

    (@usuario, 'ong', 'Instituto Quatro Patas',
     'ONG focada em castração e controle populacional. Realizamos mutirões mensais em bairros de baixa renda, com equipe veterinária voluntária.',
     'Levamos o app para os tutores atendidos nos mutirões',
     'Contagem', 'MG', 'institutoquatropatas', '31999990002', 'contato@quatropatas.org',
     'quatropatas@pix.com.br', 1, 1, 'aprovado'),

    (@usuario, 'empresa', 'Rações Silvestre',
     'Distribuidora de rações e acessórios que apoia ONGs parceiras com doações mensais de alimento e patrocínio de eventos de adoção.',
     'Patrocina as feiras de adoção e divulga o PetFinder nas lojas físicas',
     'Betim', 'MG', 'racoessilvestre', '31999990003', 'parcerias@racoessilvestre.com.br',
     NULL, 0, 1, 'aprovado'),

    (@usuario, 'apoiador', 'Coletivo Focinho Amigo',
     'Coletivo de voluntários que ajuda no transporte de animais resgatados e na divulgação de pets perdidos nas redes sociais.',
     'Compartilha diariamente os alertas de pets perdidos da plataforma',
     'Ouro Branco', 'MG', 'focinhoamigo', '31999990004', 'focinhoamigo@gmail.com',
     NULL, 1, 0, 'aprovado');

-- ==========================================================
-- Campanhas, eventos e doações de exemplo
-- ==========================================================

SET @patas := (SELECT id FROM parceiros WHERE nome = 'Patas do Bem' LIMIT 1);
SET @quatro := (SELECT id FROM parceiros WHERE nome = 'Instituto Quatro Patas' LIMIT 1);
SET @racoes := (SELECT id FROM parceiros WHERE nome = 'Rações Silvestre' LIMIT 1);

INSERT INTO parceiro_campanhas
    (parceiro_id, tipo, titulo, resumo, descricao, local_evento,
     data_inicio, data_fim, meta_valor, valor_arrecadado, itens_desejados,
     destaque, status)
VALUES
    (@patas, 'campanha',
     'Cirurgia da Mel: atropelamento com fratura',
     'A Mel foi resgatada com a pata traseira fraturada e precisa de cirurgia urgente.',
     'A Mel tem cerca de 3 anos e foi resgatada na BR-040 com fratura exposta. Já passou por exames e está estável, mas precisa de uma cirurgia ortopédica com placa. O valor inclui cirurgia, internação e fisioterapia. Toda doação, por menor que seja, ajuda.',
     NULL, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 4200.00, 1850.00, NULL, 1, 'ativa'),

    (@quatro, 'evento',
     'Mutirão de castração no bairro Jardim Vitória',
     'Castração gratuita para tutores de baixa renda, com agendamento no local.',
     'Serão 120 vagas distribuídas entre cães e gatos, por ordem de chegada. Leve documento com foto e comprovante de residência. O animal deve estar em jejum de 8 horas. Equipe veterinária voluntária e pós-operatório acompanhado pela ONG.',
     'Escola Municipal Jardim Vitória - Belo Horizonte/MG',
     DATE_ADD(NOW(), INTERVAL 12 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY),
     NULL, 0.00, NULL, 1, 'ativa'),

    (@patas, 'doacao',
     'Campanha do agasalho pet: inverno 2026',
     'Estamos recolhendo cobertores, caminhas e ração para os resgatados do abrigo.',
     'Com a chegada do frio, os 60 animais do nosso abrigo precisam de cobertura extra. Aceitamos itens usados em bom estado. Podemos buscar a doação em Belo Horizonte e região.',
     NULL, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), NULL, 0.00,
     'cobertores, caminhas, ração sênior, vermífugo, coleiras', 0, 'ativa'),

    (@racoes, 'evento',
     'Feira de adoção no estacionamento da loja',
     'Sábado de adoção com ONGs parceiras, veterinário no local e brindes.',
     'Traga a família e conheça os animais disponíveis para adoção das ONGs parceiras. Teremos orientação veterinária gratuita, microchipagem a preço de custo e brindes para quem adotar. A adoção é feita mediante entrevista e assinatura de termo de responsabilidade.',
     'Rações Silvestre - Av. Central, 1200, Betim/MG',
     DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY),
     NULL, 0.00, NULL, 0, 'ativa');
