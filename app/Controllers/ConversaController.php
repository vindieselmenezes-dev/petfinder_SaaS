<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Conversa.php';
require_once __DIR__ . '/../Models/Mensagem.php';
require_once __DIR__ . '/NotificacaoController.php';

class ConversaController
{
    private Conversa $conversa;
    private Mensagem $mensagem;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->conversa    = new Conversa();
        $this->mensagem    = new Mensagem();
        $this->notificacao = new NotificacaoController();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->conversa->listarPorUsuario($usuarioId);
    }

    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        return $this->conversa->buscarPorId($id, $usuarioId);
    }

    public function listarMensagens(int $conversaId): array
    {
        return $this->mensagem->listarPorConversa($conversaId);
    }

    /**
     * Envia uma mensagem numa conversa existente e notifica quem vai receber
     */
    public function enviarMensagem(int $conversaId, int $usuarioId, string $texto): array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return ['sucesso' => false, 'erro' => 'Digite uma mensagem.'];
        }

        $conversaInfo = $this->conversa->buscarPorId($conversaId, $usuarioId);
        if (!$conversaInfo) {
            return ['sucesso' => false, 'erro' => 'Conversa não encontrada.'];
        }

        $this->mensagem->enviar($conversaId, $usuarioId, $texto);

        $destinatarioId = (int) $conversaInfo['usuario_origem'] === $usuarioId
            ? (int) $conversaInfo['usuario_destino']
            : (int) $conversaInfo['usuario_origem'];

        $this->notificacao->criar(
            $destinatarioId,
            "💬 Nova mensagem",
            "Você recebeu uma nova mensagem sobre \"" . $conversaInfo['assunto'] . "\".",
            'Sistema',
            'conversa.php?id=' . $conversaId
        );

        return ['sucesso' => true];
    }

    public function marcarComoLidas(int $conversaId, int $usuarioId): bool
    {
        return $this->mensagem->marcarComoLidas($conversaId, $usuarioId);
    }
}
