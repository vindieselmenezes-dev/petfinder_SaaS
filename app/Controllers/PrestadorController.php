<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: PrestadorController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Prestador.php';
require_once __DIR__ . '/NotificacaoController.php';

class PrestadorController
{

    private Prestador $prestador;
    private PrestadorAvaliacao $avaliacao;
    private PrestadorSolicitacao $solicitacao;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->prestador = new Prestador();
        $this->avaliacao = new PrestadorAvaliacao();
        $this->solicitacao = new PrestadorSolicitacao();
        $this->notificacao = new NotificacaoController();
    }

    public function buscarPorUsuarioETipo(int $usuarioId, string $tipo): ?array
    {
        return $this->prestador->buscarPorUsuarioETipo($usuarioId, $tipo);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->prestador->listarPorUsuario($usuarioId);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->prestador->buscarPorId($id);
    }

    public function listarAtivos(string $tipo = '', string $cidade = '', string $busca = ''): array
    {
        return $this->prestador->listarAtivos($tipo, $cidade, $busca);
    }

    public function buscarServicos(int $prestadorId): array
    {
        return $this->prestador->buscarServicos($prestadorId);
    }

    public function buscarAnimaisAtendidos(int $prestadorId): array
    {
        return $this->prestador->buscarAnimaisAtendidos($prestadorId);
    }

    public function buscarDisponibilidade(int $prestadorId): array
    {
        return $this->prestador->buscarDisponibilidade($prestadorId);
    }

    public function salvarVeiculo(int $prestadorId, array $dados): bool
    {
        return $this->prestador->salvarVeiculo($prestadorId, $dados);
    }

    public function buscarVeiculo(int $prestadorId): ?array
    {
        return $this->prestador->buscarVeiculo($prestadorId);
    }

    public function atualizarPlano(int $prestadorId, int $planoId): bool
    {
        return $this->prestador->atualizarPlano($prestadorId, $planoId);
    }

    public function trialVencido(int $prestadorId): bool
    {
        return $this->prestador->trialVencido($prestadorId);
    }

    public function listarAvaliacoes(int $prestadorId): array
    {
        return $this->avaliacao->listarAvaliacoes($prestadorId);
    }

    public function avaliar(int $prestadorId, int $usuarioId, int $nota, string $comentario = ''): bool
    {
        return $this->avaliacao->avaliar($prestadorId, $usuarioId, $nota, $comentario);
    }

    public function listarSolicitacoesRecebidas(int $prestadorId): array
    {
        return $this->solicitacao->listarSolicitacoesRecebidas($prestadorId);
    }

    public function listarSolicitacoesFeitas(int $usuarioId): array
    {
        return $this->solicitacao->listarSolicitacoesFeitas($usuarioId);
    }

    public function atualizarStatusSolicitacao(int $solicitacaoId, int $prestadorId, string $status): bool
    {
        $solicitacao = $this->solicitacao->buscarSolicitacaoPorId($solicitacaoId);

        $sucesso = $this->solicitacao->atualizarStatusSolicitacao($solicitacaoId, $prestadorId, $status);

        if ($sucesso && $solicitacao) {
            $mensagens = [
                'aceita' => ['title' => '✅ Solicitação aceita!', 'texto' => 'Seu pedido de serviço foi aceito pelo profissional.'],
                'recusada' => ['title' => 'Sobre sua solicitação', 'texto' => 'Seu pedido de serviço não pôde ser aceito desta vez.'],
                'concluida' => ['title' => '🎉 Serviço concluído!', 'texto' => 'Seu serviço foi marcado como concluído. Que tal avaliar o profissional?'],
                'cancelada' => ['title' => 'Solicitação cancelada', 'texto' => 'Seu pedido de serviço foi cancelado.'],
            ];

            if (isset($mensagens[$status])) {
                $this->notificacao->criar(
                    (int) $solicitacao['usuario_id'],
                    $mensagens[$status]['title'],
                    $mensagens[$status]['texto'],
                    'Sistema',
                    'minhas_solicitacoes_prestador.php'
                );
            }
        }

        return $sucesso;
    }

    public function criarSolicitacao(array $dados): int|false
    {
        // Prestador com período de teste vencido não recebe novas
        // solicitações até assinar um plano pago.
        if (!empty($dados['prestador_id']) && $this->trialVencido((int) $dados['prestador_id'])) {
            return false;
        }

        $novoId = $this->solicitacao->criarSolicitacao($dados);

        if ($novoId === false) {
            return $novoId;
        }

        $prestador = $this->prestador->buscarPorId((int) $dados['prestador_id']);

        if ($prestador) {
            $rotulo = match ($prestador['tipo']) {
                'pet_sitter' => 'Pet Sitter',
                'taxista_pet' => 'Táxi Pet',
                'adestrador' => 'Adestramento',
                default => 'Passeador',
            };

            $this->notificacao->criar(
                (int) $prestador['usuario_id'],
                "🐾 Nova solicitação de serviço!",
                "Você recebeu um novo pedido de {$rotulo}. Confira os detalhes e responda.",
                'Sistema',
                'solicitacoes_prestador.php?prestador_id=' . (int) $dados['prestador_id']
            );
        }

        return $novoId;
    }

    /**
     * Cadastra o prestador e já grava serviços, animais atendidos e disponibilidade.
     * Se $veiculo não for vazio, grava também os dados de veículo (taxista_pet).
     */
    public function cadastrarCompleto(array $dados, array $servicos, array $animais, array $dias, array $periodos, array $veiculo = []): int|false
    {
        $novoId = $this->prestador->cadastrar($dados);

        if ($novoId === false) {
            return false;
        }

        $this->prestador->salvarServicos($novoId, $servicos);
        $this->prestador->salvarAnimaisAtendidos($novoId, $animais);
        $this->prestador->salvarDisponibilidade($novoId, $dias, $periodos);

        if (!empty($veiculo)) {
            $this->prestador->salvarVeiculo($novoId, $veiculo);
        }

        return $novoId;
    }
}
