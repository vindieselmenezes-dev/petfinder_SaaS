<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: NotificacaoController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Notificacao.php';

class NotificacaoController
{
    private const TIPOS_VALIDOS = ['Sistema', 'Pedido', 'Pagamento', 'Consulta', 'Promoção'];

    private Notificacao $notificacao;

    public function __construct()
    {
        $this->notificacao = new Notificacao();
    }

    public function criar(int $usuarioId, string $titulo, string $mensagem, string $tipo = 'Sistema', ?string $link = null): bool
    {
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $tipo = 'Sistema';
        }

        return $this->notificacao->criar($usuarioId, $titulo, $mensagem, $tipo, $link);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->notificacao->listarPorUsuario($usuarioId);
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        return $this->notificacao->contarNaoLidas($usuarioId);
    }

    public function marcarComoLida(int $id, int $usuarioId): bool
    {
        return $this->notificacao->marcarComoLida($id, $usuarioId);
    }

    public function marcarTodasComoLidas(int $usuarioId): bool
    {
        return $this->notificacao->marcarTodasComoLidas($usuarioId);
    }

    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->notificacao->excluir($id, $usuarioId);
    }
}
