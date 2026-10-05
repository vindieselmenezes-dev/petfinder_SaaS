<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/PedidoServico.php';
require_once __DIR__ . '/NotificacaoController.php';

class PedidoServicoController
{
    private PedidoServico $pedido;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->pedido = new PedidoServico();
        $this->notificacao = new NotificacaoController();
    }

    public function criar(array $dados): int|false
    {
        $categoriaId = (int) ($dados['categoria_id'] ?? 0);
        $servico = trim((string) ($dados['servico'] ?? ''));
        $dataDesejada = trim((string) ($dados['data_desejada'] ?? ''));
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dataDesejada);
        $periodo = (string) ($dados['periodo'] ?? '');

        if (
            !in_array($categoriaId, PedidoServico::CATEGORIAS_SUPORTADAS, true)
            || $servico === ''
            || mb_strlen($servico) > 150
            || !$data
            || $data->format('Y-m-d') !== $dataDesejada
            || $dataDesejada < date('Y-m-d')
            || !in_array($periodo, ['', 'Manhã', 'Tarde', 'Noite'], true)
        ) {
            return false;
        }

        $resultado = $this->pedido->criar($dados);
        if ($resultado === false) {
            return false;
        }

        $categoria = [
            4 => 'Banho e Tosa',
            5 => 'Hotel para Pets',
            6 => 'Creche Pet',
            7 => 'Adestramento',
        ][(int) $dados['categoria_id']] ?? 'serviço';

        foreach ($resultado['empresas_notificadas'] as $empresa) {
            $this->notificacao->criar(
                (int) $empresa['usuario_id'],
                'Novo pedido de ' . $categoria,
                'Um tutor procura ' . $dados['servico'] . ' para ' . $dados['data_desejada'] . '. Envie seu orçamento.',
                'Pedido',
                Url::pagina('solicitacoes_compartilhadas.php') . '?empresa_id=' . (int) $empresa['id']
            );
        }

        return (int) $resultado['id'];
    }

    public function listarParaEmpresa(int $empresaId): array
    {
        return $this->pedido->listarParaEmpresa($empresaId);
    }

    public function listarPorTutor(int $usuarioId): array
    {
        return $this->pedido->listarPorTutor($usuarioId);
    }

    public function responder(int $destinatarioId, int $empresaId, array $dados): bool
    {
        $usuarioId = $this->pedido->responder($destinatarioId, $empresaId, $dados);
        if ($usuarioId === false) {
            return false;
        }

        $this->notificacao->criar(
            $usuarioId,
            'Você recebeu um orçamento',
            'Uma empresa respondeu ao seu pedido de serviço. Consulte o valor e os detalhes na sua área de solicitações.',
            'Pedido',
            Url::pagina('minhas_solicitacoes_empresa.php')
        );

        return true;
    }

    public function processarSegundaRodada(): int
    {
        $notificacoes = $this->pedido->processarSegundaRodada();
        $enviadas = 0;

        foreach ($notificacoes as $notificacao) {
            if (
                $this->notificacao->criar(
                    (int) $notificacao['usuario_id'],
                    'Novo pedido de serviço disponível',
                    'O tutor ainda aguarda orçamento para ' . $notificacao['empresa_nome'] . '. Confira o pedido e envie sua proposta.',
                    'Pedido',
                    Url::pagina('solicitacoes_compartilhadas.php') . '?empresa_id=' . (int) $notificacao['empresa_id']
                )
            ) {
                $enviadas++;
            }
        }

        return $enviadas;
    }
}
