<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/SolicitacaoAdocao.php';
require_once __DIR__ . '/../Models/Conversa.php';
require_once __DIR__ . '/../Models/Mensagem.php';
require_once __DIR__ . '/PetController.php';
require_once __DIR__ . '/NotificacaoController.php';

class SolicitacaoAdocaoController
{
    private const STATUS_VALIDOS = ['Pendente', 'Aprovada', 'Rejeitada', 'Cancelada'];

    private SolicitacaoAdocao $solicitacao;
    private Conversa $conversa;
    private Mensagem $mensagem;
    private PetController $pet;
    private NotificacaoController $notificacao;

    public function __construct()
    {
        $this->solicitacao = new SolicitacaoAdocao();
        $this->conversa     = new Conversa();
        $this->mensagem     = new Mensagem();
        $this->pet          = new PetController();
        $this->notificacao  = new NotificacaoController();
    }

    /**
     * Cria uma solicitação de adoção completa: valida o pet, cria a
     * conversa com o dono, manda a primeira mensagem, e notifica o dono.
     */
    public function solicitar(int $petId, int $usuarioSolicitanteId, string $mensagemTexto): array
    {
        $pet = $this->pet->buscarPorId($petId);

        if (!$pet) {
            return ['sucesso' => false, 'erro' => 'Pet não encontrado.'];
        }

        if ($pet['status'] !== 'Para Adoção') {
            return ['sucesso' => false, 'erro' => 'Este pet não está disponível para adoção no momento.'];
        }

        if ((int) $pet['usuario_id'] === $usuarioSolicitanteId) {
            return ['sucesso' => false, 'erro' => 'Você não pode solicitar a adoção do seu próprio pet.'];
        }

        if ($this->solicitacao->existePendente($petId, $usuarioSolicitanteId)) {
            return ['sucesso' => false, 'erro' => 'Você já tem uma solicitação pendente para este pet.'];
        }

        $mensagemTexto = trim($mensagemTexto);
        if ($mensagemTexto === '') {
            return ['sucesso' => false, 'erro' => 'Conte um pouco sobre por que você quer adotar.'];
        }

        $donoId = (int) $pet['usuario_id'];

        // Cria a conversa entre o interessado e o dono, com a mensagem inicial
        $conversaId = $this->conversa->criar($usuarioSolicitanteId, $donoId, "Interesse em adotar " . $pet['nome']);
        $this->mensagem->enviar($conversaId, $usuarioSolicitanteId, $mensagemTexto);

        $novoId = $this->solicitacao->criar($petId, $usuarioSolicitanteId, $conversaId, $mensagemTexto);

        $this->notificacao->criar(
            $donoId,
            "🏠 Novo pedido de adoção!",
            "Alguém quer adotar " . $pet['nome'] . ". Veja os detalhes e responda.",
            'Sistema',
            'solicitacoes_recebidas.php'
        );

        return ['sucesso' => true, 'id' => $novoId, 'conversa_id' => $conversaId];
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->solicitacao->buscarPorId($id);
    }

    public function listarEnviadas(int $usuarioId): array
    {
        return $this->solicitacao->listarEnviadas($usuarioId);
    }

    public function listarRecebidas(int $usuarioId): array
    {
        return $this->solicitacao->listarRecebidas($usuarioId);
    }

    /**
     * Aprova uma solicitação: transfere a posse do pet, marca como
     * Adotado, rejeita as demais solicitações pendentes pro mesmo pet,
     * e notifica todo mundo envolvido.
     */
    public function aprovar(int $solicitacaoId, int $usuarioDonoId): array
    {
        $solicitacao = $this->solicitacao->buscarPorId($solicitacaoId);

        if (!$solicitacao) {
            return ['sucesso' => false, 'erro' => 'Solicitação não encontrada.'];
        }

        if ((int) $solicitacao['pet_dono_id'] !== $usuarioDonoId) {
            return ['sucesso' => false, 'erro' => 'Você não tem permissão sobre esta solicitação.'];
        }

        if ($solicitacao['status'] !== 'Pendente') {
            return ['sucesso' => false, 'erro' => 'Esta solicitação já foi respondida.'];
        }

        $petId = (int) $solicitacao['pet_id'];
        $novoTutorId = (int) $solicitacao['usuario_solicitante_id'];

        // Transfere a posse do pet e marca como adotado
        $this->pet->transferirTutor($petId, $novoTutorId);
        $this->pet->atualizarStatus($petId, 'Adotado');

        $this->solicitacao->atualizarStatus($solicitacaoId, 'Aprovada');
        $this->solicitacao->rejeitarDemaisPendentes($petId, $solicitacaoId);

        $this->notificacao->criar(
            $novoTutorId,
            "🎉 Seu pedido de adoção foi aprovado!",
            "Parabéns! O pedido de adoção de " . $solicitacao['pet_nome'] . " foi aprovado. Bem-vindo à família!",
            'Sistema',
            'meus_pets.php'
        );

        return ['sucesso' => true];
    }

    public function rejeitar(int $solicitacaoId, int $usuarioDonoId): array
    {
        $solicitacao = $this->solicitacao->buscarPorId($solicitacaoId);

        if (!$solicitacao) {
            return ['sucesso' => false, 'erro' => 'Solicitação não encontrada.'];
        }

        if ((int) $solicitacao['pet_dono_id'] !== $usuarioDonoId) {
            return ['sucesso' => false, 'erro' => 'Você não tem permissão sobre esta solicitação.'];
        }

        if ($solicitacao['status'] !== 'Pendente') {
            return ['sucesso' => false, 'erro' => 'Esta solicitação já foi respondida.'];
        }

        $this->solicitacao->atualizarStatus($solicitacaoId, 'Rejeitada');

        $this->notificacao->criar(
            (int) $solicitacao['usuario_solicitante_id'],
            "Sobre seu pedido de adoção",
            "Seu pedido de adoção de " . $solicitacao['pet_nome'] . " não foi aprovado desta vez. Não desanime, outros pets esperam por você!",
            'Sistema',
            'minhas_solicitacoes.php'
        );

        return ['sucesso' => true];
    }

    public function cancelar(int $solicitacaoId, int $usuarioSolicitanteId): array
    {
        $solicitacao = $this->solicitacao->buscarPorId($solicitacaoId);

        if (!$solicitacao || (int) $solicitacao['usuario_solicitante_id'] !== $usuarioSolicitanteId) {
            return ['sucesso' => false, 'erro' => 'Solicitação não encontrada.'];
        }

        if ($solicitacao['status'] !== 'Pendente') {
            return ['sucesso' => false, 'erro' => 'Esta solicitação já foi respondida e não pode mais ser cancelada.'];
        }

        $this->solicitacao->atualizarStatus($solicitacaoId, 'Cancelada');

        return ['sucesso' => true];
    }
}
