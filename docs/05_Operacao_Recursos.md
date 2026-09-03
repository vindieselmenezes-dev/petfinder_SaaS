# Operação dos recursos

## Migration

Aplique `database/migration_013_produto_operacional.sql` depois das migrations anteriores. Ela cria o histórico de rastreio, eventos de métricas, assinaturas push e campos de onboarding/2FA.

## E-mail

Por padrão o sistema registra os e-mails em `logs/emails.log`, comportamento adequado para desenvolvimento local. Em um servidor com `mail()` configurado, defina:

```text
EMAIL_NOTIFICACOES=1
EMAIL_REMETENTE=no-reply@seudominio.com.br
```

Para SMTP transacional (recomendado em produção), substitua o transporte de `app/Helpers/Mailer.php` por um adaptador PHPMailer mantendo a assinatura de `Mailer::enviar()`.

## Web Push

O navegador só solicita a inscrição quando `PUSH_VAPID_PUBLIC_KEY` está definida e o usuário está autenticado. A assinatura fica em `push_subscriptions` e o service worker já trata `push` e `notificationclick`.

O envio precisa de um worker ou serviço Web Push com a chave privada VAPID. A chave privada nunca deve ser enviada ao navegador nem versionada no repositório.

## 2FA

A tela `public/seguranca.php` ativa a verificação em duas etapas por código temporário enviado ao e-mail. O código expira em 10 minutos e a sessão é regenerada após a validação.

## Onboarding

`public/onboarding.php` apresenta os primeiros passos para tutor ou empresa e registra a conclusão no perfil correspondente.

## Métricas

Visualizações de páginas públicas são registradas em `metricas_empresa_eventos` e resumidas no `painel_b2b.php`. Cliques e conversões devem ser conectados aos CTAs e ao gateway de pagamento quando esses fluxos estiverem definidos.

## Rastreamento

Pedidos guardam transportadora, código de rastreio e histórico em `pedido_status_historico`. A atualização de status deve ser feita pelo fluxo administrativo/integração da transportadora; o cliente consulta o histórico em `pedido_confirmado.php`.
