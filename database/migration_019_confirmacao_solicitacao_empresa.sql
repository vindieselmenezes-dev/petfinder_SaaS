-- PetFinder: melhora o pedido de serviço a empresas (Banho e Tosa, Hotel,
-- Creche, Adestramento) para permitir:
--   1) o tutor pedir busca e entrega em casa, informando o endereço;
--   2) a empresa, ao aceitar o pedido, confirmar QUEM vai atender e
--      QUANDO (data/hora exata), além de deixar um recado pro tutor.
--
-- Aplicar depois da migration_018_solicitacoes_empresa.sql.

ALTER TABLE empresa_solicitacoes
    ADD COLUMN busca_em_casa TINYINT(1) NOT NULL DEFAULT 0 AFTER mensagem,
    ADD COLUMN endereco_busca VARCHAR(255) DEFAULT NULL AFTER busca_em_casa,
    ADD COLUMN profissional_responsavel VARCHAR(150) DEFAULT NULL AFTER status,
    ADD COLUMN data_hora_confirmada DATETIME DEFAULT NULL AFTER profissional_responsavel,
    ADD COLUMN observacoes_empresa TEXT DEFAULT NULL AFTER data_hora_confirmada;
