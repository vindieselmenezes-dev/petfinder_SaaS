/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 002 — Remoção do schema antigo paralelo
   (pet-saas-nativo), já substituído pelas tabelas novas.

   Contexto: as tabelas abaixo continham só dado de teste,
   criado durante a fase de exploração do merge (confirmado
   com o usuário em 14/08). Nenhuma delas é mais referenciada
   pelo sistema depois que os arquivos PHP forem reescritos
   pra usar o schema novo (usuarios, empresas, empresa_equipe,
   pet_cotutores, produtos, notificacoes, auditoria/log_acessos).

   Equivalente novo de cada uma, para referência:
     users                  -> usuarios
     organizations          -> empresas
     roles                  -> (perfis.tipo / empresa_equipe.papel)
     organization_user_role -> empresa_equipe
     pet_tutores            -> pets.usuario_id + pet_cotutores
     catalog_items          -> produtos
     user_notifications     -> notificacoes
     audit_logs             -> auditoria / log_acessos
     pet_alertas_perdidos   -> SEM equivalente novo ainda;
                                mantida de propósito (ver nota
                                no fim do arquivo).
   ============================================================ */

USE petfinder;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS organization_user_role;
DROP TABLE IF EXISTS pet_tutores;
DROP TABLE IF EXISTS catalog_items;
DROP TABLE IF EXISTS user_notifications;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

/* ============================================================
   NOTA: `pet_alertas_perdidos` foi PROPOSITALMENTE deixada de
   fora deste DROP. Ela ainda não tem substituta no schema novo
   (esse é um dos 2 pontos "em aberto" do documento de
   mapeamento) e tem 2 linhas de dado real de teste (pet
   perdido/encontrado), então por enquanto ela continua ativa
   e em uso — só será removida quando (e se) decidirmos o
   destino do módulo de alerta de pet perdido.
   ============================================================ */
