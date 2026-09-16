<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Suporte.php';

class SuporteController
{
    private const PRIORIDADES_VALIDAS = ['Baixa', 'Média', 'Alta', 'Urgente'];
    private const STATUS_VALIDOS = ['Aberto', 'Em Atendimento', 'Resolvido', 'Fechado'];

    private Suporte $suporte;

    public function __construct()
    {
        $this->suporte = new Suporte();
    }

    public function abrirChamado(int $usuarioId, string $assunto, string $descricao, string $prioridade): int|false
    {
        $assunto = trim($assunto);
        $descricao = trim($descricao);

        if ($assunto === '' || $descricao === '') {
            return false;
        }

        if (!in_array($prioridade, self::PRIORIDADES_VALIDAS, true)) {
            $prioridade = 'Média';
        }

        return $this->suporte->abrirChamado($usuarioId, $assunto, $descricao, $prioridade);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->suporte->listarPorUsuario($usuarioId);
    }

    public function listarTodos(?string $status = null): array
    {
        return $this->suporte->listarTodos($status);
    }

    public function buscarPorId(int $id, ?int $usuarioId = null): ?array
    {
        return $this->suporte->buscarPorId($id, $usuarioId);
    }

    public function listarRespostas(int $chamadoId): array
    {
        return $this->suporte->listarRespostas($chamadoId);
    }

    public function responder(int $chamadoId, int $usuarioId, string $resposta): bool
    {
        $resposta = trim($resposta);

        if ($resposta === '') {
            return false;
        }

        return $this->suporte->responder($chamadoId, $usuarioId, $resposta);
    }

    public function atualizarStatus(int $chamadoId, string $status): bool
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return false;
        }

        return $this->suporte->atualizarStatus($chamadoId, $status);
    }

    public function statusValidos(): array
    {
        return self::STATUS_VALIDOS;
    }

    public function prioridadesValidas(): array
    {
        return self::PRIORIDADES_VALIDAS;
    }
}
