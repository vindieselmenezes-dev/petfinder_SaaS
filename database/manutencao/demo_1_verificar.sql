-- ==========================================================
-- PetFinder Brasil — CONTAS DE DEMONSTRAÇÃO (Demo@123)
-- Passo 1 de 2: VERIFICAR (só leitura, não altera nada)
-- ==========================================================
-- As contas fictícias criadas por database/seed_demo_apresentacao.sql
-- todas usam a mesma senha publicada no repositório (Demo@123). Em um
-- site público isso é uma porta aberta: qualquer pessoa que leia o
-- repositório consegue entrar nelas.
--
-- Rode este arquivo primeiro. Se a seção 1 vier vazia, não há o que
-- apagar. Se vier com linhas, rode o passo 2 (demo_2_apagar.sql).
-- Funciona no phpMyAdmin (aba SQL) ou com `mysql petfinder < arquivo`.

-- 1) As contas demo que existem hoje neste banco
SELECT id, nome, sobrenome, email, tipo_usuario, status, criado_em
FROM usuarios
WHERE email IN (
    'mariana.souza.demo@petfinder.local',
    'rafael.andrade.demo@petfinder.local',
    'juliana.ferreira.demo@petfinder.local',
    'bruno.costa.demo@petfinder.local',
    'camila.rocha.demo@petfinder.local',
    'thiago.almeida.demo@petfinder.local',
    'contato.amigofiel.demo@petfinder.local',
    'contato.vetcare.demo@petfinder.local',
    'contato.caofeliz.demo@petfinder.local'
)
ORDER BY id;

-- 2) O que o passo 2 vai apagar junto (em cascata com a conta)
SELECT 'pets'                AS tabela, COUNT(*) AS linhas FROM pets
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'empresas', COUNT(*) FROM empresas
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'produtos', COUNT(*) FROM produtos
 WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'))
UNION ALL SELECT 'prestadores_servico', COUNT(*) FROM prestadores_servico
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'enderecos', COUNT(*) FROM enderecos
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local');

-- 2b) Linhas SEM chave estrangeira que o passo 2 também apaga à mão
SELECT 'favoritos (de pets/produtos demo)' AS tabela, COUNT(*) AS linhas FROM favoritos
 WHERE pet_id IN (SELECT id FROM pets WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'))
    OR produto_id IN (SELECT id FROM produtos WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')))
UNION ALL SELECT 'pet_alertas_perdidos', COUNT(*) FROM pet_alertas_perdidos
 WHERE pet_id IN (SELECT id FROM pets WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'))
UNION ALL SELECT 'metricas_empresa_eventos', COUNT(*) FROM metricas_empresa_eventos
 WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'));

-- 2c) PRODUTOS ÓRFÃOS (a empresa dona não existe mais). Se você já apagou
--     contas demo antes, os produtos delas continuam aparecendo na loja,
--     porque produtos.empresa_id não tem chave estrangeira. Esta lista é
--     só para você conferir: o passo 2 não apaga órfãos antigos, porque
--     não há como saber, só pelo banco, de onde eles vieram. Os do seed
--     têm nomes como 'Ração Golden Fórmula Adulto 15kg' e 'Coleira
--     Ajustável Nylon'; se forem eles, apague pelo id.
SELECT p.id, p.empresa_id, p.nome, p.preco_venda, p.criado_em
FROM produtos p LEFT JOIN empresas e ON e.id = p.empresa_id
WHERE e.id IS NULL
ORDER BY p.id;

-- 3) BLOQUEIOS: se alguma destas contagens for maior que 0, alguém de
--    verdade já interagiu com a conta/produto demo (mensagem, pedido,
--    consulta...) e o banco vai RECUSAR a exclusão no passo 2 (nada é
--    apagado pela metade). Nesse caso, em vez de apagar, desative:
--      UPDATE usuarios SET status = 'inativo' WHERE email LIKE '%.demo@petfinder.local';
SELECT 'avaliacoes' AS tabela, COUNT(*) AS linhas FROM avaliacoes
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'consultas', COUNT(*) FROM consultas
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'conversas', COUNT(*) FROM conversas
 WHERE usuario_origem IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
    OR usuario_destino IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'mensagens', COUNT(*) FROM mensagens
 WHERE remetente_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'pedidos', COUNT(*) FROM pedidos
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'cupons_utilizados', COUNT(*) FROM cupons_utilizados
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'suporte', COUNT(*) FROM suporte
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'suporte_respostas', COUNT(*) FROM suporte_respostas
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')
UNION ALL SELECT 'carrinho_itens (produto demo)', COUNT(*) FROM carrinho_itens
 WHERE produto_id IN (SELECT id FROM produtos WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')))
UNION ALL SELECT 'pedido_itens (produto demo)', COUNT(*) FROM pedido_itens
 WHERE produto_id IN (SELECT id FROM produtos WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')));

-- 4) Arquivos de foto no disco que sobram depois de apagar (o banco
--    não apaga arquivos). Remova-os à mão das pastas uploads/pets,
--    uploads/empresas, uploads/prestadores e uploads/produtos.
SELECT 'pets/'         AS pasta, foto    AS arquivo FROM pets
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local') AND foto IS NOT NULL AND foto <> ''
UNION ALL SELECT 'pets/', arquivo FROM pet_imagens
 WHERE pet_id IN (SELECT id FROM pets WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'))
UNION ALL SELECT 'empresas/', logo FROM empresas
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local') AND logo IS NOT NULL AND logo <> ''
UNION ALL SELECT 'empresas/', capa FROM empresas
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local') AND capa IS NOT NULL AND capa <> ''
UNION ALL SELECT 'empresas/', imagem FROM empresa_galeria
 WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local'))
UNION ALL SELECT 'prestadores/', foto FROM prestadores_servico
 WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local') AND foto IS NOT NULL AND foto <> ''
UNION ALL SELECT 'produtos/', imagem FROM produto_imagens
 WHERE produto_id IN (SELECT id FROM produtos WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM usuarios WHERE email LIKE '%.demo@petfinder.local')));
