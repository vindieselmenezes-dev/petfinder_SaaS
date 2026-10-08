<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: EmpresaSolicitacaoController
 * ==========================================================
 * Pedido de contratação feito por um tutor a uma empresa de Banho e Tosa,
 * Hotel para Pets, Creche Pet ou Adestramento (categorias que não têm
 * agendamento próprio como a Clínica Veterinária tem).
 */

require_once __DIR__ . '/../Models/EmpresaSolicitacao.php';
require_once __DIR__ . '/NotificacaoController.php';

class EmpresaSolicitacaoController
{
    private const PREFIXO_PEDIDO = 'Seu pedido em ';

    private EmpresaSolicitacao $solicitacao;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->solicitacao = new EmpresaSolicitacao();
        $this->notificacao = new NotificacaoController();
    }

    public function categoriaSuportada(int $categoriaId): bool
    {
        return in_array($categoriaId, EmpresaSolicitacao::CATEGORIAS_SUPORTADAS, true);
    }

    public function criar(array $dados): int|false
    {
        if (!$this->dadosValidosParaCriar($dados)) {
            return false;
        }

        $novoId = $this->solicitacao->criar($dados);

        if ($novoId !== false) {
            $this->notificarNovaSolicitacao($novoId, (int) $dados['empresa_id']);
        }

        return $novoId;
    }

    private function dadosValidosParaCriar(array $dados): bool
    {
        if (empty($dados['usuario_id']) || empty($dados['empresa_id']) || empty($dados['data_desejada'])) {
            return false;
        }

        // Pedido de busca em casa exige o endereço de busca preenchido.
        return empty($dados['busca_em_casa']) || !empty(trim((string) ($dados['endereco_busca'] ?? '')));
    }

    private function notificarNovaSolicitacao(int $novoId, int $empresaId): void
    {
        $solicitacaoCriada = $this->solicitacao->buscarPorId($novoId);

        if (!$solicitacaoCriada || empty($solicitacaoCriada['empresa_usuario_id'])) {
            return;
        }

        $quando = $solicitacaoCriada['data_desejada']
            . ($solicitacaoCriada['periodo'] ? ' (' . $solicitacaoCriada['periodo'] . ')' : '');
        $buscaTexto = !empty($solicitacaoCriada['busca_em_casa']) ? ' Pediu busca e entrega em casa.' : '';

        $this->notificacao->criar(
            (int) $solicitacaoCriada['empresa_usuario_id'],
            "📥 Novo pedido de serviço!",
            "O tutor " . $solicitacaoCriada['tutor_nome'] . " solicitou "
                . ($solicitacaoCriada['servico'] ?: 'um serviço') . " para " . $quando . "." . $buscaTexto,
            'Sistema',
            'solicitacoes_empresa.php?empresa_id=' . $empresaId
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->solicitacao->buscarPorId($id);
    }

    public function listarPorTutor(int $usuarioId): array
    {
        return $this->solicitacao->listarPorTutor($usuarioId);
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        return $this->solicitacao->listarPorEmpresa($empresaId);
    }

    public function atualizarStatus(int $solicitacaoId, int $empresaId, string $status, array $confirmacao = []): bool
    {
        $solicitacaoAntes = $this->solicitacao->buscarPorId($solicitacaoId);

        $sucesso = $this->solicitacao->atualizarStatus($solicitacaoId, $empresaId, $status, $confirmacao);

        if ($sucesso && $solicitacaoAntes && (int) $solicitacaoAntes['empresa_id'] === $empresaId) {
            $solicitacaoAtualizada = $this->solicitacao->buscarPorId($solicitacaoId);

            $detalhes = '';
            if (!empty($solicitacaoAtualizada['data_hora_confirmada'])) {
                $detalhes .= ' Confirmado para ' . date('d/m/Y \à\s H:i', strtotime($solicitacaoAtualizada['data_hora_confirmada'])) . '.';
            }
            if (!empty($solicitacaoAtualizada['profissional_responsavel'])) {
                $detalhes .= ' Quem atende: ' . $solicitacaoAtualizada['profissional_responsavel'] . '.';
            }
            if (!empty($solicitacaoAtualizada['observacoes_empresa'])) {
                $detalhes .= ' Recado da empresa: "' . $solicitacaoAtualizada['observacoes_empresa'] . '".';
            }

            $mensagens = [
                'aceita' => ['titulo' => '✅ Pedido aceito!', 'texto' => self::PREFIXO_PEDIDO . $solicitacaoAntes['empresa_nome'] . ' foi aceito.' . $detalhes],
                'recusada' => ['titulo' => 'Pedido recusado', 'texto' => self::PREFIXO_PEDIDO . $solicitacaoAntes['empresa_nome'] . ' não pôde ser atendido dessa vez.'],
                'concluida' => ['titulo' => '🎉 Serviço concluído', 'texto' => self::PREFIXO_PEDIDO . $solicitacaoAntes['empresa_nome'] . ' foi concluído. Que tal avaliar o atendimento?'],
                'cancelada' => ['titulo' => 'Pedido cancelado', 'texto' => self::PREFIXO_PEDIDO . $solicitacaoAntes['empresa_nome'] . ' foi cancelado pela empresa.'],
            ];

            if (isset($mensagens[$status])) {
                $this->notificacao->criar(
                    (int) $solicitacaoAntes['usuario_id'],
                    $mensagens[$status]['titulo'],
                    $mensagens[$status]['texto'],
                    'Sistema',
                    'minhas_solicitacoes_empresa.php'
                );
            }
        }

        return $sucesso;
    }

    public function cancelarPeloTutor(int $solicitacaoId, int $usuarioId): bool
    {
        $solicitacaoAntes = $this->solicitacao->buscarPorId($solicitacaoId);

        $sucesso = $this->solicitacao->cancelarPeloTutor($solicitacaoId, $usuarioId);

        if ($sucesso && $solicitacaoAntes && !empty($solicitacaoAntes['empresa_usuario_id'])) {
            $this->notificacao->criar(
                (int) $solicitacaoAntes['empresa_usuario_id'],
                "Pedido cancelado pelo tutor",
                "O tutor " . $solicitacaoAntes['tutor_nome'] . " cancelou o pedido de " . $solicitacaoAntes['data_desejada'] . ".",
                'Sistema',
                'solicitacoes_empresa.php?empresa_id=' . (int) $solicitacaoAntes['empresa_id']
            );
        }

        return $sucesso;
    }
}
