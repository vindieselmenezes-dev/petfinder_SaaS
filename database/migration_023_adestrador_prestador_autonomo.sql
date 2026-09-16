-- PetFinder: Adestrador passa a ser um tipo de prestador autônomo
--
-- Contexto: Passeador, Pet Sitter e Táxi Pet já eram tipos de
-- profissional autônomo (tabela prestadores_servico), cadastráveis sem
-- CNPJ ou vínculo com uma empresa. O Adestrador, por outro lado, só
-- existia como categoria de EMPRESA (categoria_id = 7 em `categorias`),
-- o que não fazia sentido pra quem é autônomo.
--
-- Verificado antes desta migration: nenhuma empresa real usa a
-- categoria "Adestramento" hoje, então é seguro desativá-la sem
-- quebrar nenhum cadastro existente.
--
-- Aplicar depois da migration_022_indices_pets.sql.

-- 1) Adiciona 'adestrador' como um tipo válido em prestadores_servico
ALTER TABLE prestadores_servico
    MODIFY tipo ENUM('passeador','pet_sitter','taxista_pet','adestrador') NOT NULL;

-- 2) Desativa a categoria de empresa "Adestramento" (id 7). Não apagamos
-- a linha (mantém o histórico/integridade referencial caso algo antigo
-- ainda aponte pra ela) — só some do formulário de cadastro de empresa,
-- que já filtra por `ativo = 1`.
UPDATE categorias SET ativo = 0 WHERE id = 7 AND nome = 'Adestramento';
