-- PetFinder: adiciona a categoria de empresa "Pet Sitter".
--
-- Empresas desse ramo (cuidadores de pets que atendem como negócio,
-- não a pessoa física autônoma da tela de Prestadores) passam a poder
-- se cadastrar e aparecer no filtro de categorias de public/empresas/empresas.php.
--
-- Aplicar depois da migration_019_confirmacao_solicitacao_empresa.sql.

INSERT INTO categorias (nome, descricao, icone, ativo)
VALUES ('Pet Sitter', 'Cuidadores de pets na casa do tutor', 'bi-person-heart', 1);
