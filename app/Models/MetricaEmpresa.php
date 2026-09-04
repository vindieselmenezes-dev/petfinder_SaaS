<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class MetricaEmpresa
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function registrar(int $empresaId, string $tipo, string $pagina, ?int $referenciaId = null, ?int $usuarioId = null): bool
    {
        if (!in_array($tipo, ['visualizacao', 'clique', 'conversao'], true)) {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO metricas_empresa_eventos (empresa_id, tipo, pagina, referencia_id, usuario_id)
                 VALUES (:empresa_id, :tipo, :pagina, :referencia_id, :usuario_id)'
            );
            return $stmt->execute([
                ':empresa_id' => $empresaId,
                ':tipo' => $tipo,
                ':pagina' => $pagina,
                ':referencia_id' => $referenciaId,
                ':usuario_id' => $usuarioId,
            ]);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function resumo(int $empresaId, int $dias = 30): array
    {
        $resumo = ['visualizacao' => 0, 'clique' => 0, 'conversao' => 0];
        try {
            $dias = max(1, $dias);
            $stmt = $this->pdo->prepare(
                "SELECT tipo, COUNT(*) AS total
                 FROM metricas_empresa_eventos
                 WHERE empresa_id = :empresa_id AND criado_em >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)
                 GROUP BY tipo"
            );
            $stmt->execute([':empresa_id' => $empresaId]);
            foreach ($stmt->fetchAll() as $linha) {
                $resumo[$linha['tipo']] = (int) $linha['total'];
            }
        } catch (Throwable $exception) {
            // A migration pode estar aguardando aplicação.
        }
        $resumo['taxa_conversao'] = $resumo['clique'] > 0
            ? round(($resumo['conversao'] / $resumo['clique']) * 100, 2)
            : 0.0;
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(DISTINCT usuario_id) FROM metricas_empresa_eventos WHERE empresa_id = :empresa_id AND criado_em >= DATE_SUB(NOW(), INTERVAL ' . max(1, $dias) . ' DAY)');
            $stmt->execute([':empresa_id' => $empresaId]);
            $resumo['usuarios_unicos'] = (int) $stmt->fetchColumn();
            $stmt = $this->pdo->prepare('SELECT COUNT(DISTINCT p.id) AS pedidos, COALESCE(SUM(pi.subtotal), 0) AS receita FROM pedidos p JOIN pedido_itens pi ON pi.pedido_id = p.id JOIN produtos pr ON pr.id = pi.produto_id WHERE pr.empresa_id = :empresa_id AND p.status <> "Cancelado" AND p.criado_em >= DATE_SUB(NOW(), INTERVAL ' . max(1, $dias) . ' DAY)');
            $stmt->execute([':empresa_id' => $empresaId]);
            $vendas = $stmt->fetch();
            $resumo['pedidos'] = (int) ($vendas['pedidos'] ?? 0);
            $resumo['receita'] = (float) ($vendas['receita'] ?? 0);
        } catch (Throwable $exception) {
            $resumo['usuarios_unicos'] = 0;
            $resumo['pedidos'] = 0;
            $resumo['receita'] = 0.0;
        }
        return $resumo;
    }
}
