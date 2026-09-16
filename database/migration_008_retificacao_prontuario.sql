/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 008 — Retificação de prontuário (append-only de verdade)

   O texto de `retificar_prontuario.php` sempre prometeu que "o
   original não é apagado, uma versão corrigida é anexada" — mas
   o schema de `prontuarios` nunca teve coluna nenhuma pra isso.
   Essa migração adiciona o vínculo de verdade entre uma
   retificação e o prontuário original que ela corrige.
   ============================================================ */

USE petfinder;

ALTER TABLE prontuarios
    ADD COLUMN retificacao_de_id INT NULL AFTER consulta_id,
    ADD CONSTRAINT fk_prontuario_retificacao
        FOREIGN KEY (retificacao_de_id)
        REFERENCES prontuarios(id)
        ON DELETE SET NULL;
