/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 009 — Tokens de redefinição de senha

   Estrutura pro fluxo de "esqueci minha senha". O envio de
   e-mail em si fica pra depois (ver app/Helpers/Mailer.php) —
   por enquanto o token é só registrado aqui.
   ============================================================ */

USE petfinder;

CREATE TABLE IF NOT EXISTS reset_senha_tokens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    token VARCHAR(64) NOT NULL,

    expira_em DATETIME NOT NULL,

    usado TINYINT(1) NOT NULL DEFAULT 0,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reset_token_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_reset_token UNIQUE (token)

);

CREATE INDEX idx_reset_token_usuario ON reset_senha_tokens (usuario_id);
