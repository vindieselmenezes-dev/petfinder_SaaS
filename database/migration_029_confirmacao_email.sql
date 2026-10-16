/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 029 — Confirmação de e-mail no cadastro

   - usuarios.email_verificado_em: NULL = e-mail ainda não confirmado.
   - confirmacao_email_tokens: links de confirmação (guarda só o hash).

   IMPORTANTE:
   - Rode UMA vez, com o banco já selecionado no phpMyAdmin (não há
     "USE" aqui de propósito: no InfinityFree o banco tem outro nome).
   - Todas as contas que já existem são marcadas como confirmadas
     (UPDATE abaixo); senão todo mundo seria barrado no próximo login.
   - Se você importar um seed/dump depois desta migração, rode de novo
     apenas o UPDATE para não bloquear as contas de demonstração.
   ============================================================ */

ALTER TABLE usuarios
    ADD COLUMN email_verificado_em DATETIME NULL DEFAULT NULL AFTER email;

UPDATE usuarios
   SET email_verificado_em = NOW()
 WHERE email_verificado_em IS NULL;

CREATE TABLE IF NOT EXISTS confirmacao_email_tokens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    token VARCHAR(64) NOT NULL,

    expira_em DATETIME NOT NULL,

    usado TINYINT(1) NOT NULL DEFAULT 0,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_confirmacao_email_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_confirmacao_email_token UNIQUE (token)

);

CREATE INDEX idx_confirmacao_email_usuario ON confirmacao_email_tokens (usuario_id);
