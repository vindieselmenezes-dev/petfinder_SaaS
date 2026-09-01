<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Conversa
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criar(int $usuarioOrigem, int $usuarioDestino, string $assunto): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO conversas (usuario_origem, usuario_destino, assunto, status)
            VALUES (:origem, :destino, :assunto, 'Aberta')
        ");
        $stmt->execute([
            ':origem'  => $usuarioOrigem,
            ':destino' => $usuarioDestino,
            ':assunto' => $assunto,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   uo.nome AS origem_nome, ud.nome AS destino_nome
            FROM conversas c
            INNER JOIN usuarios uo ON uo.id = c.usuario_origem
            INNER JOIN usuarios ud ON ud.id = c.usuario_destino
            WHERE c.id = :id
              AND (c.usuario_origem = :usuario_id OR c.usuario_destino = :usuario_id2)
        ");
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId, ':usuario_id2' => $usuarioId]);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista as conversas de um usuário (como origem ou destino), com
     * o nome do outro participante e a última mensagem
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                CASE WHEN c.usuario_origem = :usuario_id THEN ud.nome ELSE uo.nome END AS outro_nome,
                CASE WHEN c.usuario_origem = :usuario_id2 THEN c.usuario_destino ELSE c.usuario_origem END AS outro_id,
                (SELECT mensagem FROM mensagens WHERE conversa_id = c.id ORDER BY enviado_em DESC, id DESC LIMIT 1) AS ultima_mensagem,
                (SELECT enviado_em FROM mensagens WHERE conversa_id = c.id ORDER BY enviado_em DESC, id DESC LIMIT 1) AS ultima_mensagem_em,
                (SELECT COUNT(*) FROM mensagens WHERE conversa_id = c.id AND lida = 0 AND remetente_id != :usuario_id3) AS nao_lidas
            FROM conversas c
            INNER JOIN usuarios uo ON uo.id = c.usuario_origem
            INNER JOIN usuarios ud ON ud.id = c.usuario_destino
            WHERE c.usuario_origem = :usuario_id4 OR c.usuario_destino = :usuario_id5
            ORDER BY ultima_mensagem_em DESC, c.criado_em DESC, c.id DESC
        ");
        $stmt->execute([
            ':usuario_id'  => $usuarioId,
            ':usuario_id2' => $usuarioId,
            ':usuario_id3' => $usuarioId,
            ':usuario_id4' => $usuarioId,
            ':usuario_id5' => $usuarioId,
        ]);

        return $stmt->fetchAll();
    }
}
