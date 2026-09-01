/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 007 — Link de destino nas notificações

   `notificacoes` não tinha como guardar pra onde a notificação
   deveria levar ao ser clicada (ex: o chamado de suporte
   específico, o pet específico etc.). Sem isso, cada notificação
   virava um beco sem saída — só um texto, sem ação nenhuma.
   ============================================================ */

USE petfinder;

ALTER TABLE notificacoes
    ADD COLUMN link VARCHAR(255) NULL AFTER mensagem;
