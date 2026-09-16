<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: MensagemContato
 * ==========================================================
 * Mensagens enviadas pelo formulário público de contato
 * (public/contato.php). Funciona tanto para visitantes sem conta
 * quanto para usuários logados.
 */

require_once __DIR__ . '/../../config/database.php';

class MensagemContato
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Salva a mensagem. Retorna o ID gerado ou false em caso de erro.
     */
    public function salvar(array $dados): int|false
    {
        $sql = "
            INSERT INTO mensagens_contato (usuario_id, nome, email, assunto, mensagem)
            VALUES (:usuario_id, :nome, :email, :assunto, :mensagem)
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $dados['usuario_id'] ?: null,
                ':nome'       => $dados['nome'],
                ':email'      => $dados['email'],
                ':assunto'    => $dados['assunto'] ?: null,
                ':mensagem'   => $dados['mensagem'],
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erro ao salvar mensagem de contato: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista as mensagens mais recentes primeiro (painel do administrador).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(string $status = ''): array
    {
        $sql = 'SELECT * FROM mensagens_contato';
        $parametros = [];

        if (in_array($status, ['novo', 'em_andamento', 'respondido'], true)) {
            $sql .= ' WHERE status = :status';
            $parametros[':status'] = $status;
        }

        $sql .= ' ORDER BY (status = "novo") DESC, criado_em DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function contarNovas(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM mensagens_contato WHERE status = 'novo'");

        return (int) $stmt->fetchColumn();
    }

    public function alterarStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['novo', 'em_andamento', 'respondido'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE mensagens_contato SET status = :status WHERE id = :id');

        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
