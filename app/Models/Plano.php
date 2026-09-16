<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Plano
 * ==========================================================
 * Planos de assinatura das empresas (Grátis / Profissional / Destaque).
 * Por enquanto a troca de plano é simulada (sem gateway de pagamento
 * real ligado ainda) -- ver public/simular_assinatura.php, que segue o
 * mesmo espírito do simular_faturamento.php que já existia.
 */

require_once __DIR__ . '/../../config/database.php';

class Plano
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function listarAtivos(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, nome, slug, descricao, preco_mensal, limite_produtos, destaque, prioridade, dias_trial, ativo, criado_em
            FROM planos WHERE ativo = 1 ORDER BY preco_mensal ASC, id ASC
        ");

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, slug, descricao, preco_mensal, limite_produtos, destaque, prioridade, dias_trial, ativo, criado_em
            FROM planos WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function buscarPorSlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, slug, descricao, preco_mensal, limite_produtos, destaque, prioridade, dias_trial, ativo, criado_em
            FROM planos WHERE slug = :slug LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Plano padrão pra empresas novas que não escolheram nenhum
     */
    public function buscarPlanoGratis(): ?array
    {
        return $this->buscarPorSlug('gratis');
    }
}
