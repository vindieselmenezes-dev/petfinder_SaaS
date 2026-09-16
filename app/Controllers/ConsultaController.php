<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: ConsultaController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/NotificacaoController.php';

class ConsultaController
{
    private Consulta $consulta;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->consulta = new Consulta();
        $this->notificacao = new NotificacaoController();
    }

    public function listarVeterinariosDaEmpresa(int $empresaId): array
    {
        return $this->consulta->listarVeterinariosDaEmpresa($empresaId);
    }

    public function agendar(array $dados): int|false
    {
        if (empty($dados['usuario_id']) || empty($dados['veterinario_id']) || empty($dados['empresa_id'])) {
            return false;
        }

        if (empty($dados['data_consulta']) || empty($dados['hora_consulta'])) {
            return false;
        }

        $novoId = $this->consulta->agendar($dados);

        if ($novoId !== false) {
            $consultaCriada = $this->consulta->buscarPorId($novoId);

            if ($consultaCriada && !empty($consultaCriada['veterinario_usuario_id'])) {
                $this->notificacao->criar(
                    (int) $consultaCriada['veterinario_usuario_id'],
                    "🩺 Nova consulta agendada!",
                    "Um tutor solicitou uma consulta em " . ($consultaCriada['data_consulta'] ?? '') . " às " . substr((string) ($consultaCriada['hora_consulta'] ?? ''), 0, 5) . ". Confirme ou reagende.",
                    'Consulta',
                    'consultas_empresa.php?empresa_id=' . (int) $dados['empresa_id']
                );
            }
        }

        return $novoId;
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->consulta->buscarPorId($id);
    }

    public function listarPorTutor(int $usuarioId): array
    {
        return $this->consulta->listarPorTutor($usuarioId);
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        return $this->consulta->listarPorEmpresa($empresaId);
    }

    public function atualizarStatus(int $consultaId, int $empresaId, string $status): bool
    {
        $consultaAntes = $this->consulta->buscarPorId($consultaId);

        $sucesso = $this->consulta->atualizarStatus($consultaId, $empresaId, $status);

        if ($sucesso && $consultaAntes && (int) $consultaAntes['empresa_id'] === $empresaId) {
            $mensagens = [
                'Confirmada' => ['titulo' => '✅ Consulta confirmada!', 'texto' => 'Sua consulta em ' . $consultaAntes['empresa_nome'] . ' foi confirmada.'],
                'Em Atendimento' => ['titulo' => '🩺 Consulta iniciada', 'texto' => 'Sua consulta em ' . $consultaAntes['empresa_nome'] . ' está em atendimento.'],
                'Concluída' => ['titulo' => '🎉 Consulta concluída', 'texto' => 'Sua consulta em ' . $consultaAntes['empresa_nome'] . ' foi concluída. Que tal avaliar o atendimento?'],
                'Cancelada' => ['titulo' => 'Consulta cancelada', 'texto' => 'Sua consulta em ' . $consultaAntes['empresa_nome'] . ' foi cancelada pela clínica.'],
            ];

            if (isset($mensagens[$status])) {
                $this->notificacao->criar(
                    (int) $consultaAntes['usuario_id'],
                    $mensagens[$status]['titulo'],
                    $mensagens[$status]['texto'],
                    'Consulta',
                    'minhas_consultas.php'
                );
            }
        }

        return $sucesso;
    }

    public function cancelarPeloTutor(int $consultaId, int $usuarioId): bool
    {
        $consultaAntes = $this->consulta->buscarPorId($consultaId);

        $sucesso = $this->consulta->cancelarPeloTutor($consultaId, $usuarioId);

        if ($sucesso && $consultaAntes && !empty($consultaAntes['veterinario_usuario_id'])) {
            $this->notificacao->criar(
                (int) $consultaAntes['veterinario_usuario_id'],
                "Consulta cancelada pelo tutor",
                "O tutor " . $consultaAntes['tutor_nome'] . " cancelou a consulta de " . $consultaAntes['data_consulta'] . ".",
                'Consulta',
                'consultas_empresa.php?empresa_id=' . (int) $consultaAntes['empresa_id']
            );
        }

        return $sucesso;
    }
}
