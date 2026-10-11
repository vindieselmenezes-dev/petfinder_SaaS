/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 028 — auditoria.acao aceita as ações do modo suporte

   O modo suporte grava 'IMPERSONATE' (entrada na empresa) e
   'IMPERSONATE_FIM' (saída), mas a coluna só aceitava INSERT,
   UPDATE e DELETE. Em banco com modo SQL estrito a gravação falhava
   ("Data truncated for column 'acao'"); em modo permissivo o valor
   era gravado em branco e a trilha de auditoria perdia a ação.

   Só amplia o ENUM: não altera nem apaga nenhuma linha existente.
   ============================================================ */

USE petfinder;

ALTER TABLE auditoria
    MODIFY acao ENUM('INSERT','UPDATE','DELETE','IMPERSONATE','IMPERSONATE_FIM') DEFAULT NULL;
