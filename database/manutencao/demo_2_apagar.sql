-- ==========================================================
-- PetFinder Brasil — CONTAS DE DEMONSTRAÇÃO (Demo@123)
-- Passo 2 de 2: APAGAR (altera o banco)
-- ==========================================================
-- Só rode depois do passo 1 (demo_1_verificar.sql) e só se você
-- realmente quer remover as contas demo. FAÇA UM BACKUP ANTES.
--
-- Apaga as 9 contas fictícias e tudo que é delas. Pets, empresas,
-- prestadores e endereços somem em cascata com a conta; produtos,
-- favoritos, alertas de pet perdido e métricas NÃO têm chave
-- estrangeira para elas, por isso são apagados explicitamente aqui
-- (antes das contas, enquanto ainda dá para identificá-los).
--
-- Tudo roda numa transação: se o banco recusar algum passo (alguém de
-- verdade interagiu com a conta ou com um produto demo — ver seção 3
-- do passo 1), o script para com erro e NADA fica apagado, porque a
-- transação não chega ao COMMIT. As contas são identificadas pela
-- lista exata de e-mails, nunca por padrão.

DROP TEMPORARY TABLE IF EXISTS tmp_demo_usuarios;
CREATE TEMPORARY TABLE tmp_demo_usuarios AS
SELECT id FROM usuarios
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
);

START TRANSACTION;

DELETE FROM favoritos
 WHERE pet_id IN (SELECT id FROM pets WHERE usuario_id IN (SELECT id FROM tmp_demo_usuarios))
    OR produto_id IN (SELECT id FROM produtos WHERE empresa_id IN
         (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM tmp_demo_usuarios)));

DELETE FROM pet_alertas_perdidos
 WHERE pet_id IN (SELECT id FROM pets WHERE usuario_id IN (SELECT id FROM tmp_demo_usuarios));

DELETE FROM metricas_empresa_eventos
 WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM tmp_demo_usuarios));

DELETE FROM produtos
 WHERE empresa_id IN (SELECT id FROM empresas WHERE usuario_id IN (SELECT id FROM tmp_demo_usuarios));

DELETE FROM usuarios WHERE id IN (SELECT id FROM tmp_demo_usuarios);

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_demo_usuarios;

-- Conferência: as duas colunas devem ser 0
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE email LIKE '%.demo@petfinder.local') AS contas_demo_restantes,
  (SELECT COUNT(*) FROM produtos p LEFT JOIN empresas e ON e.id = p.empresa_id
    WHERE e.id IS NULL)                                                     AS produtos_orfaos;
