-- PetFinder: comissão do marketplace (simulada — não move dinheiro real,
-- só calcula e registra o valor que a plataforma reteria e o valor que
-- a empresa receberia em cada venda de produto).
--
-- Aplicar depois da migration_020_categoria_pet_sitter.sql.

-- Taxa global, em % (ex: '10' = 10%). Fica em `configuracoes` pra poder
-- ser ajustada sem precisar mexer em código.
INSERT INTO configuracoes (chave_config, valor_config, descricao)
VALUES ('comissao_marketplace_percentual', '10', 'Percentual retido pela plataforma em cada venda de produto no marketplace (ex: 10 = 10%)');

-- Guarda o valor calculado em cada item, pra manter o histórico correto
-- mesmo que a taxa mude no futuro (pedidos antigos não devem mudar de valor).
ALTER TABLE pedido_itens
    ADD COLUMN valor_comissao DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal,
    ADD COLUMN valor_repasse DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER valor_comissao;

-- Soma de conferência no pedido inteiro (pode ter itens de empresas diferentes).
ALTER TABLE pedidos
    ADD COLUMN valor_comissao_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER valor_total;
