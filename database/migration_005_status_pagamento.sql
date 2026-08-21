/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 005 — Status de pagamento da empresa

   Traz de volta o conceito de 3 estados de cobrança que existia
   em `organizations.status` (apagada na migração 002), agora em
   `empresas`. Usado pelo simulador de faturamento e pelo painel
   B2B pra bloquear ações quando a empresa está inadimplente.
   ============================================================ */

USE petfinder;

ALTER TABLE empresas
    ADD COLUMN status_pagamento ENUM('Ativo', 'Atrasado', 'Suspenso') NOT NULL DEFAULT 'Ativo' AFTER ativo;
