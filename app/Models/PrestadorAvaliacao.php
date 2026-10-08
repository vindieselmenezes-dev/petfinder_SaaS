<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PrestadorAvaliacao
 * Avaliações recebidas pelos prestadores de serviço.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PrestadorAvaliacao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function avaliar(int $prestadorId, int $usuarioId, int $nota, string $comentario = ''): bool
    {
        if ($prestadorId <= 0 || $usuarioId <= 0 || $nota < 1 || $nota > 5) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM prestador_avaliacoes WHERE prestador_id = :prestador_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute([':prestador_id' => $prestadorId, ':usuario_id' => $usuarioId]);

        if ($stmt->fetch()) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO prestador_avaliacoes (prestador_id, usuario_id, nota, comentario)
                 VALUES (:prestador_id, :usuario_id, :nota, :comentario)'
            );
            $stmt->execute([
                ':prestador_id' => $prestadorId,
                ':usuario_id' => $usuarioId,
                ':nota' => $nota,
                ':comentario' => $comentario ?: null
            ]);

            $stmt = $this->pdo->prepare(
                'UPDATE prestadores_servico
                 SET avaliacao = (SELECT ROUND(AVG(nota), 1) FROM prestador_avaliacoes WHERE prestador_id = :p1),
                     total_avaliacoes = (SELECT COUNT(*) FROM prestador_avaliacoes WHERE prestador_id = :p2)
                 WHERE id = :p3'
            );
            $stmt->execute([':p1' => $prestadorId, ':p2' => $prestadorId, ':p3' => $prestadorId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function listarAvaliacoes(int $prestadorId, int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.nota, a.comentario, a.criado_em, u.nome AS usuario_nome
             FROM prestador_avaliacoes a JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.prestador_id = :prestador_id ORDER BY a.criado_em DESC LIMIT :limite'
        );
        $stmt->bindValue(':prestador_id', $prestadorId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', max(1, min($limite, 50)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | SOLICITAÇÕES (pedido de passeio / diária de pet sitter)
    |--------------------------------------------------------------------------
    */
}
