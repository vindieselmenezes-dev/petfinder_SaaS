<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Consulta
 * ==========================================================
 * Agendamento de consulta veterinária feito pelo tutor. Ideia trazida
 * das páginas clinica.html / consultaveterinaria.html do
 * projetointegrador, usando a tabela `consultas` que já existia no
 * banco do SaaS (até então só usada pelo lado da clínica, ao fechar um
 * atendimento em processa_prontuario.php).
 */

require_once __DIR__ . '/../../config/database.php';

class Consulta
{
    private PDO $pdo;

    public const STATUS_VALIDOS = ['Agendada', 'Confirmada', 'Em Atendimento', 'Concluída', 'Cancelada'];

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Lista os veterinários (com CRMV já registrado no sistema) que fazem
     * parte da equipe ativa de uma empresa -- só eles podem ser
     * selecionados no agendamento, já que consultas.veterinario_id é
     * obrigatório e aponta pra tabela `veterinarios`.
     */
    public function listarVeterinariosDaEmpresa(int $empresaId): array
    {
        $sql = "
            SELECT
                v.id AS veterinario_id,
                v.crmv,
                v.valor_consulta,
                CONCAT(u.nome, ' ', u.sobrenome) AS nome
            FROM empresa_equipe ee
            INNER JOIN veterinarios v ON v.usuario_id = ee.usuario_id
            INNER JOIN usuarios u ON u.id = ee.usuario_id
            WHERE ee.empresa_id = :empresa_id
              AND ee.papel = 'veterinario'
              AND ee.status = 'ativo'
              AND v.ativo = 1
            ORDER BY u.nome
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    /**
     * Agenda uma nova consulta (sempre entra como 'Agendada'; quem
     * confirma é a clínica)
     */
    public function agendar(array $dados): int|false
    {
        $sql = "
            INSERT INTO consultas
                (usuario_id, veterinario_id, empresa_id, pet_id, data_consulta, hora_consulta, status, motivo)
            VALUES
                (:usuario_id, :veterinario_id, :empresa_id, :pet_id, :data_consulta, :hora_consulta, 'Agendada', :motivo)
        ";

        $stmt = $this->pdo->prepare($sql);

        $sucesso = $stmt->execute([
            ':usuario_id' => $dados['usuario_id'],
            ':veterinario_id' => $dados['veterinario_id'],
            ':empresa_id' => $dados['empresa_id'],
            ':pet_id' => $dados['pet_id'] ?? null,
            ':data_consulta' => $dados['data_consulta'],
            ':hora_consulta' => $dados['hora_consulta'],
            ':motivo' => $dados['motivo'] ?? null,
        ]);

        return $sucesso ? (int) $this->pdo->lastInsertId() : false;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.id, c.veterinario_id, c.empresa_id, c.usuario_id, c.pet_id,
                c.data_consulta, c.hora_consulta, c.status, c.motivo,
                c.observacoes, c.valor, c.criado_em,
                e.nome_fantasia AS empresa_nome,
                p.nome AS pet_nome,
                u.nome AS tutor_nome,
                uv.id AS veterinario_usuario_id,
                uv.nome AS veterinario_nome
            FROM consultas c
            INNER JOIN empresas e ON e.id = c.empresa_id
            INNER JOIN usuarios u ON u.id = c.usuario_id
            LEFT JOIN pets p ON p.id = c.pet_id
            LEFT JOIN veterinarios v ON v.id = c.veterinario_id
            LEFT JOIN usuarios uv ON uv.id = v.usuario_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function listarPorTutor(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.id, c.veterinario_id, c.empresa_id, c.usuario_id, c.pet_id,
                c.data_consulta, c.hora_consulta, c.status, c.motivo,
                c.observacoes, c.valor, c.criado_em,
                e.nome_fantasia AS empresa_nome,
                p.nome AS pet_nome,
                uv.nome AS veterinario_nome
            FROM consultas c
            INNER JOIN empresas e ON e.id = c.empresa_id
            LEFT JOIN pets p ON p.id = c.pet_id
            LEFT JOIN veterinarios v ON v.id = c.veterinario_id
            LEFT JOIN usuarios uv ON uv.id = v.usuario_id
            WHERE c.usuario_id = :usuario_id
            ORDER BY c.data_consulta DESC, c.hora_consulta DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function listarPorEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                c.id, c.veterinario_id, c.empresa_id, c.usuario_id, c.pet_id,
                c.data_consulta, c.hora_consulta, c.status, c.motivo,
                c.observacoes, c.valor, c.criado_em,
                p.nome AS pet_nome,
                u.nome AS tutor_nome,
                u.telefone AS tutor_telefone,
                uv.nome AS veterinario_nome
            FROM consultas c
            INNER JOIN usuarios u ON u.id = c.usuario_id
            LEFT JOIN pets p ON p.id = c.pet_id
            LEFT JOIN veterinarios v ON v.id = c.veterinario_id
            LEFT JOIN usuarios uv ON uv.id = v.usuario_id
            WHERE c.empresa_id = :empresa_id
            ORDER BY c.data_consulta DESC, c.hora_consulta DESC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatus(int $consultaId, int $empresaId, string $status): bool
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE consultas
            SET status = :status
            WHERE id = :id AND empresa_id = :empresa_id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':id' => $consultaId,
            ':empresa_id' => $empresaId,
        ]);
    }

    /**
     * Cancelamento feito pelo próprio tutor (só se ainda não foi atendida)
     */
    public function cancelarPeloTutor(int $consultaId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE consultas
            SET status = 'Cancelada'
            WHERE id = :id
              AND usuario_id = :usuario_id
              AND status IN ('Agendada', 'Confirmada')
        ");

        $stmt->execute([':id' => $consultaId, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }
}
