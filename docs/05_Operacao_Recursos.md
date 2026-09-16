# Operação dos recursos

## Migration

Aplique `database/migration_013_produto_operacional.sql` depois das migrations anteriores. Ela cria o histórico de rastreio, eventos de métricas, assinaturas push e campos de onboarding/2FA.

## E-mail

Por padrão o sistema registra os e-mails em `logs/emails.log`, comportamento adequado para desenvolvimento local. Em um servidor com `mail()` configurado, defina:

```text
EMAIL_NOTIFICACOES=1
EMAIL_REMETENTE=no-reply@seudominio.com.br
SMTP_HOST=smtp.seudominio.com.br
SMTP_PORT=587
SMTP_AUTH=1
SMTP_USERNAME=usuario-smtp
SMTP_PASSWORD=segredo-fora-do-repositorio
SMTP_ENCRYPTION=tls
```

Com essas variáveis e `composer install`, `app/Helpers/Mailer.php` usa PHPMailer com SMTP transacional. A senha deve vir do ambiente/secret manager, nunca do Git.

## Web Push

O navegador só solicita a inscrição quando `PUSH_VAPID_PUBLIC_KEY` está definida e o usuário está autenticado. A assinatura fica em `push_subscriptions` e o service worker já trata `push` e `notificationclick`.

O envio precisa de um worker ou serviço Web Push com a chave privada VAPID. A chave privada nunca deve ser enviada ao navegador nem versionada no repositório.

```text
PUSH_VAPID_PUBLIC_KEY=chave-publica-base64url
PUSH_VAPID_PRIVATE_KEY=chave-privada-base64url
PUSH_VAPID_SUBJECT=mailto:admin@seudominio.com.br
```

Instale as dependências com `composer install` para habilitar o envio Web Push real.

## 2FA

A tela `public/seguranca.php` ativa a verificação em duas etapas por código temporário enviado ao e-mail. O código expira em 10 minutos e a sessão é regenerada após a validação.

## Onboarding

`public/onboarding.php` apresenta os primeiros passos para tutor ou empresa e registra a conclusão no perfil correspondente.

## Métricas

Visualizações de páginas públicas são registradas em `metricas_empresa_eventos` e resumidas no `painel_b2b.php`. Os CTAs de produto/empresa registram cliques e pedidos confirmados registram conversões por empresa.

## Rastreamento

Pedidos guardam transportadora, código de rastreio e histórico em `pedido_status_historico`. A atualização de status deve ser feita pelo fluxo administrativo/integração da transportadora; o cliente consulta o histórico em `pedido_confirmado.php`.

## Testes E2E

Instale as dependências JavaScript e o Chromium:

```powershell
npm install
npx playwright install chromium
```

Configure `E2E_BASE_URL`, `E2E_EMAIL`, `E2E_PASSWORD`, `E2E_PET_ID` e `E2E_PRODUTO_ID` com registros reais de teste. Execute `npm run test:e2e`. O fluxo usa login, solicitação de adoção e checkout, sem credenciais versionadas.
