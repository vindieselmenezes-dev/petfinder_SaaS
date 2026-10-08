<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PetHistorico
 * Histórico de status e eventos dos pets.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PetHistorico
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Registra uma mudança de status no histórico append-only. Chamado
     * internamente sempre que o status realmente muda, não precisa ser
     * chamado manualmente de fora.
     */
    public function registrarHistoricoStatus(int $petId, ?string $statusAnterior, string $statusNovo, ?int $usuarioId, ?string $motivo = null): void
    {
        $sql = "
            INSERT INTO pets_status_historico (pet_id, status_anterior, status_novo, alterado_por, motivo)
            VALUES (:pet_id, :status_anterior, :status_novo, :usuario_id, :motivo)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pet_id' => $petId,
            ':status_anterior' => $statusAnterior,
            ':status_novo' => $statusNovo,
            ':usuario_id' => $usuarioId,
            ':motivo' => $motivo,
        ]);
    }

    private function garantirTabelaHistoricoEventos(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pets_historico_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pet_id INT NOT NULL,
                tipo VARCHAR(40) NOT NULL,
                descricao VARCHAR(255) NOT NULL,
                detalhes TEXT NULL,
                data_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                registrado_por INT NULL,
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pet_historico_evento (pet_id, data_evento),
                CONSTRAINT fk_pet_historico_evento_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
                CONSTRAINT fk_pet_historico_evento_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Busca o histórico completo de mudanças de status de um pet,
     * mais recente primeiro
     */
    public function buscarHistoricoStatus(int $petId): array
    {
        $sql = "
            SELECT
                h.id, h.pet_id, h.status_anterior, h.status_novo,
                h.alterado_por, h.motivo, h.criado_em,
                u.nome AS alterado_por_nome
            FROM pets_status_historico h
            LEFT JOIN usuarios u ON u.id = h.alterado_por
            WHERE h.pet_id = :pet_id
            ORDER BY h.criado_em DESC, h.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pet_id' => $petId]);

        return $stmt->fetchAll();
    }

    public function registrarEventoHistorico(
        int $petId,
        string $tipo,
        string $descricao,
        ?string $detalhes = null,
        ?string $dataEvento = null,
        ?int $usuarioId = null
    ): bool {
        if ($petId <= 0 || trim($tipo) === '' || trim($descricao) === '') {
            return false;
        }

        $this->garantirTabelaHistoricoEventos();
        $stmt = $this->pdo->prepare(
            'INSERT INTO pets_historico_eventos (pet_id, tipo, descricao, detalhes, data_evento, registrado_por) VALUES (:pet_id, :tipo, :descricao, :detalhes, COALESCE(:data_evento, NOW()), :registrado_por)'
        );

        return $stmt->execute([
            ':pet_id' => $petId,
            ':tipo' => trim($tipo),
            ':descricao' => trim($descricao),
            ':detalhes' => $detalhes,
            ':data_evento' => $dataEvento,
            ':registrado_por' => $usuarioId,
        ]);
    }

    public function buscarHistoricoCompleto(int $petId): array
    {
        $this->garantirTabelaHistoricoEventos();
        $sql = "
            SELECT tipo, descricao, detalhes, data_evento, alterado_por_nome
            FROM (
                SELECT 'Status' AS tipo,
                       CONCAT('Status: ', h.status_novo) AS descricao,
                       h.motivo AS detalhes,
                       h.criado_em AS data_evento,
                       u.nome AS alterado_por_nome
                FROM pets_status_historico h
                LEFT JOIN usuarios u ON u.id = h.alterado_por
                WHERE h.pet_id = :pet_status
                UNION ALL
                SELECT 'Consulta' AS tipo,
                       CONCAT('Consulta ', c.status) AS descricao,
                       COALESCE(NULLIF(c.motivo, ''), c.observacoes) AS detalhes,
                       TIMESTAMP(c.data_consulta, c.hora_consulta) AS data_evento,
                       u.nome AS alterado_por_nome
                FROM consultas c
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.pet_id = :pet_consulta
                UNION ALL
                  SELECT 'Cuidados especiais' AS tipo,
                      'Alergia registrada' AS descricao,
                      CONCAT(a.descricao, ' (Severidade: ', a.severidade, ')') AS detalhes,
                      p.criado_em AS data_evento,
                      NULL AS alterado_por_nome
                  FROM alergias a
                  INNER JOIN pets p ON p.id = a.pet_id
                  WHERE a.pet_id = :pet_alergia
                  UNION ALL
                SELECT tipo, descricao, detalhes, data_evento,
                       u.nome AS alterado_por_nome
                FROM pets_historico_eventos e
                LEFT JOIN usuarios u ON u.id = e.registrado_por
                WHERE e.pet_id = :pet_evento
            ) historico
            ORDER BY data_evento DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pet_status' => $petId,
            ':pet_consulta' => $petId,
            ':pet_alergia' => $petId,
            ':pet_evento' => $petId,
        ]);

        return $stmt->fetchAll();
    }
}
