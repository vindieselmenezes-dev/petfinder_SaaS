<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PedidoServico
{
    private const FORMATO_DATA_HORA = 'Y-m-d H:i:s';

    public const CATEGORIAS_SUPORTADAS = [4, 5, 6, 7];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criar(array $dados): array|false
    {
        $categoriaId = (int) ($dados['categoria_id'] ?? 0);
        $usuarioId = (int) ($dados['usuario_id'] ?? 0);
        $servico = trim((string) ($dados['servico'] ?? ''));
        $dataDesejada = trim((string) ($dados['data_desejada'] ?? ''));

        if (
            !in_array($categoriaId, self::CATEGORIAS_SUPORTADAS, true)
            || $usuarioId <= 0
            || $servico === ''
            || $dataDesejada === ''
        ) {
            return false;
        }

        $empresas = $this->buscarEmpresasElegiveis($categoriaId);
        if ($empresas === []) {
            return false;
        }

        $petId = (int) ($dados['pet_id'] ?? 0) ?: null;
        if ($petId !== null && !$this->petPertenceAoUsuario($petId, $usuarioId)) {
            return false;
        }

        [$primeiraRodada, $idsPrimeiraRodada, $statusInicial, $ampliarEm] = $this->planejarRodadas($empresas);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO pedidos_servico
                    (usuario_id, categoria_id, pet_id, servico, data_desejada, periodo, mensagem, status, ampliar_em)
                VALUES
                    (:usuario_id, :categoria_id, :pet_id, :servico, :data_desejada, :periodo, :mensagem, :status, :ampliar_em)
            ");
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':categoria_id' => $categoriaId,
                ':pet_id' => $petId,
                ':servico' => $servico,
                ':data_desejada' => $dataDesejada,
                ':periodo' => ($dados['periodo'] ?? '') ?: null,
                ':mensagem' => trim((string) ($dados['mensagem'] ?? '')) ?: null,
                ':status' => $statusInicial,
                ':ampliar_em' => $ampliarEm,
            ]);
            $pedidoId = (int) $this->pdo->lastInsertId();

            $this->vincularEmpresasAoPedido($pedidoId, $empresas, $idsPrimeiraRodada);

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Erro ao criar pedido compartilhado: ' . $e->getMessage());
            return false;
        }

        return [
            'id' => $pedidoId,
            'empresas_notificadas' => $primeiraRodada,
        ];
    }

    private function petPertenceAoUsuario(int $petId, int $usuarioId): bool
    {
        $stmtPet = $this->pdo->prepare('SELECT id FROM pets WHERE id = :pet_id AND usuario_id = :usuario_id');
        $stmtPet->execute([':pet_id' => $petId, ':usuario_id' => $usuarioId]);

        return (bool) $stmtPet->fetchColumn();
    }

    /**
     * Define quem é notificado primeiro (empresas em destaque, se houver),
     * o status inicial do pedido e quando ampliar para as demais.
     *
     * @return array{0: array, 1: array<int, bool>, 2: string, 3: ?string}
     */
    private function planejarRodadas(array $empresas): array
    {
        $destacadas = array_values(array_filter($empresas, static fn(array $empresa): bool => (bool) $empresa['destaque']));
        $primeiraRodada = $destacadas !== [] ? $destacadas : $empresas;
        $idsPrimeiraRodada = array_fill_keys(array_map(static fn(array $empresa): int => (int) $empresa['id'], $primeiraRodada), true);
        $temSegundaRodada = $destacadas !== [] && count($destacadas) < count($empresas);
        $statusInicial = $destacadas !== [] ? 'aguardando_destaques' : 'aguardando_demais';
        $ampliarEm = $temSegundaRodada ? date(self::FORMATO_DATA_HORA, time() + 1800) : null;

        return [$primeiraRodada, $idsPrimeiraRodada, $statusInicial, $ampliarEm];
    }

    private function vincularEmpresasAoPedido(int $pedidoId, array $empresas, array $idsPrimeiraRodada): void
    {
        $stmtEmpresa = $this->pdo->prepare("
            INSERT INTO pedidos_servico_empresas
                (pedido_id, empresa_id, destaque, status, notificada_em)
            VALUES
                (:pedido_id, :empresa_id, :destaque, :status, :notificada_em)
        ");

        foreach ($empresas as $empresa) {
            $empresaId = (int) $empresa['id'];
            $notificada = isset($idsPrimeiraRodada[$empresaId]);
            $stmtEmpresa->execute([
                ':pedido_id' => $pedidoId,
                ':empresa_id' => $empresaId,
                ':destaque' => (int) $empresa['destaque'],
                ':status' => $notificada ? 'notificada' : 'aguardando',
                ':notificada_em' => $notificada ? date(self::FORMATO_DATA_HORA) : null,
            ]);
        }
    }

    public function listarParaEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pe.id AS destinatario_id, pe.status AS resposta_status, pe.destaque,
                p.id AS pedido_id, p.usuario_id, p.servico, p.data_desejada, p.periodo,
                p.mensagem, p.criado_em, c.nome AS categoria_nome,
                u.nome AS tutor_nome, u.email AS tutor_email, pet.nome AS pet_nome,
                pe.valor_orcado, pe.data_hora_proposta, pe.profissional_responsavel,
                pe.observacoes_empresa
            FROM pedidos_servico_empresas pe
            INNER JOIN pedidos_servico p ON p.id = pe.pedido_id
            INNER JOIN categorias c ON c.id = p.categoria_id
            INNER JOIN usuarios u ON u.id = p.usuario_id
            LEFT JOIN pets pet ON pet.id = p.pet_id
            WHERE pe.empresa_id = :empresa_id
              AND pe.status IN ('notificada', 'orcamento_enviado')
              AND p.status <> 'cancelado'
            ORDER BY (pe.status = 'notificada') DESC, p.criado_em DESC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    public function listarPorTutor(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id AS pedido_id, p.categoria_id, p.servico, p.data_desejada, p.periodo,
                p.mensagem, p.status AS pedido_status, p.ampliar_em, p.criado_em,
                c.nome AS categoria_nome, pet.nome AS pet_nome,
                pe.status AS resposta_status, pe.valor_orcado, pe.data_hora_proposta,
                pe.profissional_responsavel, pe.observacoes_empresa,
                e.id AS empresa_id, e.nome_fantasia AS empresa_nome
            FROM pedidos_servico p
            INNER JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN pets pet ON pet.id = p.pet_id
            LEFT JOIN pedidos_servico_empresas pe
                ON pe.pedido_id = p.id AND pe.status = 'orcamento_enviado'
            LEFT JOIN empresas e ON e.id = pe.empresa_id
            WHERE p.usuario_id = :usuario_id
            ORDER BY p.criado_em DESC, e.nome_fantasia ASC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function responder(int $destinatarioId, int $empresaId, array $dados): int|false
    {
        $valor = (float) ($dados['valor_orcado'] ?? 0);
        if ($destinatarioId <= 0 || $empresaId <= 0 || $valor <= 0 || $valor > 99999999.99) {
            return false;
        }

        $dataHoraProposta = trim((string) ($dados['data_hora_proposta'] ?? ''));
        if ($dataHoraProposta !== '') {
            $dataHora = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dataHoraProposta);
            if (!$dataHora || $dataHora->format(self::FORMATO_DATA_HORA) !== $dataHoraProposta || $dataHoraProposta < date(self::FORMATO_DATA_HORA)) {
                return false;
            }
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE pedidos_servico_empresas
                SET status = 'orcamento_enviado',
                    valor_orcado = :valor_orcado,
                    data_hora_proposta = :data_hora_proposta,
                    profissional_responsavel = :profissional_responsavel,
                    observacoes_empresa = :observacoes_empresa,
                    contato_em = NOW()
                WHERE id = :id AND empresa_id = :empresa_id AND status = 'notificada'
            ");
            $stmt->execute([
                ':valor_orcado' => number_format($valor, 2, '.', ''),
                ':data_hora_proposta' => $dataHoraProposta ?: null,
                ':profissional_responsavel' => trim((string) ($dados['profissional_responsavel'] ?? '')) ?: null,
                ':observacoes_empresa' => trim((string) ($dados['observacoes_empresa'] ?? '')) ?: null,
                ':id' => $destinatarioId,
                ':empresa_id' => $empresaId,
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $stmtPedido = $this->pdo->prepare("
                SELECT pedido_id FROM pedidos_servico_empresas
                WHERE id = :id AND empresa_id = :empresa_id
            ");
            $stmtPedido->execute([':id' => $destinatarioId, ':empresa_id' => $empresaId]);
            $pedidoId = (int) $stmtPedido->fetchColumn();

            $this->pdo->prepare("
                UPDATE pedidos_servico
                SET status = 'com_orcamentos', ampliar_em = NULL
                WHERE id = :pedido_id AND status IN ('aguardando_destaques', 'aguardando_demais')
            ")->execute([':pedido_id' => $pedidoId]);

            $stmtTutor = $this->pdo->prepare('SELECT usuario_id FROM pedidos_servico WHERE id = :id');
            $stmtTutor->execute([':id' => $pedidoId]);
            $usuarioId = (int) $stmtTutor->fetchColumn();

            $this->pdo->commit();
            return $usuarioId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Erro ao salvar orçamento de serviço: ' . $e->getMessage());
            return false;
        }
    }

    public function processarSegundaRodada(): array
    {
        $stmtPendentes = $this->pdo->query("
            SELECT id FROM pedidos_servico
            WHERE status = 'aguardando_destaques' AND ampliar_em <= NOW()
            ORDER BY ampliar_em ASC
            LIMIT 100
        ");
        $ids = $stmtPendentes->fetchAll(PDO::FETCH_COLUMN);
        $notificacoes = [];

        foreach ($ids as $pedidoId) {
            try {
                $this->pdo->beginTransaction();

                $stmtPedido = $this->pdo->prepare("
                    SELECT id, status FROM pedidos_servico WHERE id = :id FOR UPDATE
                ");
                $stmtPedido->execute([':id' => $pedidoId]);
                $pedido = $stmtPedido->fetch();
                if (!$pedido || $pedido['status'] !== 'aguardando_destaques') {
                    $this->pdo->commit();
                    continue;
                }

                $stmtOrcamentos = $this->pdo->prepare("
                    SELECT COUNT(*) FROM pedidos_servico_empresas
                    WHERE pedido_id = :pedido_id AND status = 'orcamento_enviado'
                ");
                $stmtOrcamentos->execute([':pedido_id' => $pedidoId]);
                if ((int) $stmtOrcamentos->fetchColumn() > 0) {
                    $this->pdo->prepare("UPDATE pedidos_servico SET status = 'com_orcamentos', ampliar_em = NULL WHERE id = :id")
                        ->execute([':id' => $pedidoId]);
                    $this->pdo->commit();
                    continue;
                }

                $stmtEmpresas = $this->pdo->prepare("
                    SELECT pe.empresa_id, e.usuario_id, e.nome_fantasia
                    FROM pedidos_servico_empresas pe
                    INNER JOIN empresas e ON e.id = pe.empresa_id
                    WHERE pe.pedido_id = :pedido_id AND pe.status = 'aguardando'
                    ORDER BY e.id
                ");
                $stmtEmpresas->execute([':pedido_id' => $pedidoId]);
                $empresas = $stmtEmpresas->fetchAll();

                if ($empresas === []) {
                    $this->pdo->prepare("UPDATE pedidos_servico SET status = 'sem_empresas', ampliar_em = NULL WHERE id = :id")
                        ->execute([':id' => $pedidoId]);
                    $this->pdo->commit();
                    continue;
                }

                $stmtAtualizar = $this->pdo->prepare("
                    UPDATE pedidos_servico_empresas
                    SET status = 'notificada', notificada_em = NOW()
                    WHERE pedido_id = :pedido_id AND status = 'aguardando'
                ");
                $stmtAtualizar->execute([':pedido_id' => $pedidoId]);
                $this->pdo->prepare("UPDATE pedidos_servico SET status = 'aguardando_demais', ampliar_em = NULL WHERE id = :id")
                    ->execute([':id' => $pedidoId]);

                $this->pdo->commit();

                foreach ($empresas as $empresa) {
                    $notificacoes[] = [
                        'pedido_id' => (int) $pedidoId,
                        'empresa_id' => (int) $empresa['empresa_id'],
                        'usuario_id' => (int) $empresa['usuario_id'],
                        'empresa_nome' => $empresa['nome_fantasia'],
                    ];
                }
            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                error_log('Erro ao expandir pedido de serviço: ' . $e->getMessage());
            }
        }

        return $notificacoes;
    }

    private function buscarEmpresasElegiveis(int $categoriaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT e.id, e.usuario_id, e.nome_fantasia, COALESCE(pl.destaque, 0) AS destaque
            FROM empresas e
            LEFT JOIN planos pl ON pl.id = e.plano_id
            WHERE e.categoria_id = :categoria_id
              AND e.ativo = 1
              AND e.status_pagamento = 'Ativo'
              AND (e.plano_expira_em IS NULL OR e.plano_expira_em >= CURDATE())
            ORDER BY COALESCE(pl.destaque, 0) DESC, e.id ASC
        ");
        $stmt->execute([':categoria_id' => $categoriaId]);

        return $stmt->fetchAll();
    }
}
