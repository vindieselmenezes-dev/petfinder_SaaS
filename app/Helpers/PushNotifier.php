<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class PushNotifier
{
    public static function enviar(int $usuarioId, string $titulo, string $mensagem, ?string $link = null): bool
    {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($autoload) || !getenv('PUSH_VAPID_PRIVATE_KEY') || !getenv('PUSH_VAPID_SUBJECT')) {
            return false;
        }
        require_once $autoload;

        if (!class_exists('\Minishlink\WebPush\WebPush')) {
            return false;
        }

        $pdo = Database::conectar();
        $stmt = $pdo->prepare('SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE usuario_id = :usuario_id AND ativo = 1');
        $stmt->execute([':usuario_id' => $usuarioId]);
        $subscriptions = $stmt->fetchAll();
        if (!$subscriptions)
            return false;

        $auth = [
            'VAPID' => [
                'subject' => getenv('PUSH_VAPID_SUBJECT'),
                'publicKey' => getenv('PUSH_VAPID_PUBLIC_KEY'),
                'privateKey' => getenv('PUSH_VAPID_PRIVATE_KEY'),
            ],
        ];
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        $payload = json_encode(['title' => $titulo, 'body' => $mensagem, 'url' => $link ?: '/petfinder-SaaS/public/notificacoes.php'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $subscription['endpoint'],
                    'publicKey' => $subscription['p256dh'],
                    'authToken' => $subscription['auth'],
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $pdo->prepare('UPDATE push_subscriptions SET ativo = 0 WHERE id = :id')->execute([':id' => $subscriptions[array_search($report->getRequest()->getUri()->__toString(), array_column($subscriptions, 'endpoint'))]['id'] ?? 0]);
            }
        }
        return true;
    }
}
