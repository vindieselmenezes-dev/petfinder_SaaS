-- PetFinder: índices de performance na tabela `pets`
--
-- Contexto: as telas públicas (pets perdidos, encontrados, para adoção,
-- com tutor, adotados e a busca com filtros) fazem, em praticamente
-- toda consulta, o mesmo padrão:
--
--     WHERE p.status = ?  ORDER BY p.criado_em DESC
--
-- Hoje a tabela `pets` não tem nenhum índice em `status` nem em
-- `criado_em` (só em especie_id, raca_id, usuario_id e nome), então
-- esse WHERE + ORDER BY força uma varredura completa da tabela a cada
-- carregamento dessas páginas. Com poucos registros isso não se nota,
-- mas cresce proporcionalmente ao número de pets cadastrados.
--
-- Esta migration não muda nenhum comportamento da aplicação — só cria
-- índices, então nenhuma query precisa ser reescrita.
--
-- Aplicar depois da migration_021_comissao_marketplace.sql.

-- Índice composto: cobre o padrão mais comum (filtra por status e já
-- entrega os resultados na ordem certa, sem precisar de um passo extra
-- de ordenação em memória).
ALTER TABLE pets
    ADD INDEX idx_pet_status_criado_em (status, criado_em);

-- Índice isolado em criado_em: cobre as consultas que ordenam por data
-- sem filtrar por status (ex: Pet::listarTodos(), ou a busca geral com
-- status = "Todos" em Pet::buscarAdocaoPublico()).
ALTER TABLE pets
    ADD INDEX idx_pet_criado_em (criado_em);
