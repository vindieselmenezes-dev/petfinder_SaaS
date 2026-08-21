<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Suporte
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Abre um novo chamado de suporte
     */
    public function abrirChamado(int $usuarioId, string $assunto, string $descricao, string $prioridade = 'Média'): int|false
    {
        $sql = "
            INSERT INTO suporte (usuario_id, assunto, descricao, prioridade, status)
            VALUES (:usuario_id, :assunto, :descricao, :prioridade, 'Aberto')
        ";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':assunto'    => $assunto,
            ':descricao'  => $descricao,
            ':prioridade' => $prioridade,
        ]);

        return $ok ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Lista os chamados de um usuário específico (visão do cliente/empresa)
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT *
            FROM suporte
            WHERE usuario_id = :usuario_id
            ORDER BY criado_em DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Lista todos os chamados do sistema (visão do administrador)
     */
    public function listarTodos(?string $status = null): array
    {
        $sql = "
            SELECT s.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM suporte s
            INNER JOIN usuarios u ON u.id = s.usuario_id
        ";

        if ($status !== null) {
            $sql .= " WHERE s.status = :status";
        }

        $sql .= " ORDER BY
            FIELD(s.prioridade, 'Urgente', 'Alta', 'Média', 'Baixa'),
            s.criado_em DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        if ($status !== null) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    /**
     * Busca um chamado específico, com checagem opcional de dono
     * (se $usuarioId for informado, só retorna se o chamado for dele)
     */
    public function buscarPorId(int $id, ?int $usuarioId = null): ?array
    {
        $sql = "
            SELECT s.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM suporte s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.id = :id
        ";

        if ($usuarioId !== null) {
            $sql .= " AND s.usuario_id = :usuario_id";
        }

        $stmt = $this->pdo->prepare($sql);

        $params = [':id' => $id];
        if ($usuarioId !== null) {
            $params[':usuario_id'] = $usuarioId;
        }

        $stmt->execute($params);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista as respostas de um chamado, em ordem cronológica
     */
    public function listarRespostas(int $chamadoId): array
    {
        $sql = "
            SELECT r.*, u.nome AS usuario_nome, u.tipo_usuario
            FROM suporte_respostas r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.chamado_id = :chamado_id
            ORDER BY r.criado_em ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':chamado_id' => $chamadoId]);

        return $stmt->fetchAll();
    }

    /**
     * Adiciona uma resposta a um chamado (do cliente ou do administrador)
     */
    public function responder(int $chamadoId, int $usuarioId, string $resposta): bool
    {
        $sql = "
            INSERT INTO suporte_respostas (chamado_id, usuario_id, resposta)
            VALUES (:chamado_id, :usuario_id, :resposta)
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':chamado_id' => $chamadoId,
            ':usuario_id' => $usuarioId,
            ':resposta'   => $resposta,
        ]);
    }

    /**
     * Atualiza o status de um chamado (ação exclusiva do administrador)
     */
    public function atualizarStatus(int $chamadoId, string $status): bool
    {
        $sql = "UPDATE suporte SET status = :status WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':id'     => $chamadoId,
        ]);
    }
}
