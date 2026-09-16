-- PetFinder: moderação de campanhas por parceiro (opcional).
--
-- Contexto: hoje, uma vez que a PARCERIA é aprovada em admin_parceiros.php,
-- o parceiro publica campanhas, eventos e doações sem nenhuma revisão —
-- só o status da parceria (aprovado/recusado/inativo) é olhado. Isso é
-- suficiente pra maioria dos casos, mas quem administra o site pode
-- querer revisar cada publicação individualmente também (evitar textos
-- impróprios, links suspeitos, valores de meta exagerados etc.).
--
-- Esta migration adiciona essa camada extra SEM mudar o comportamento
-- de quem não quiser usá-la:
--
--   - `status_moderacao` nasce 'aprovada' por padrão (linhas existentes
--     e instalações que nunca mexerem na configuração continuam
--     publicando na hora, como sempre foi).
--   - A chave `moderacao_campanhas_ativa` em `configuracoes` liga/desliga
--     a exigência de revisão para publicações NOVAS. Trocar de '0' para
--     '1' é a única ação necessária para exigir aprovação a partir dali
--     (edite direto na tabela `configuracoes`, não existe tela pra isso
--     ainda — mesmo padrão de `comissao_marketplace_percentual`).
--
-- Aplicar depois da migration_025_mensagens_contato.sql.

ALTER TABLE parceiro_campanhas
    ADD COLUMN status_moderacao ENUM('pendente', 'aprovada', 'recusada') NOT NULL DEFAULT 'aprovada' AFTER status,
    ADD COLUMN observacao_moderacao VARCHAR(255) DEFAULT NULL AFTER status_moderacao,
    ADD KEY idx_campanhas_moderacao (status_moderacao);

INSERT INTO configuracoes (chave_config, valor_config, descricao)
VALUES (
    'moderacao_campanhas_ativa',
    '0',
    'Se "1", toda campanha/evento/doação nova de um parceiro entra como pendente e só fica visível no site depois que um administrador aprovar em admin_campanhas.php. Se "0" (padrão), publica na hora, como sempre foi.'
);
