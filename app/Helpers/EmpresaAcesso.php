<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: EmpresaAcesso
 *
 * Centraliza a checagem de "este usuário pode mexer nesta
 * empresa?" — verdadeiro se ele for o dono (empresas.usuario_id)
 * OU se estiver ativo na equipe (empresa_equipe), com um papel
 * mínimo opcional. Isso é o que permite um usuário administrar
 * várias empresas com papéis diferentes em cada uma, sem
 * precisar reescrever essa lógica em cada arquivo.
 * ==========================================================
 */
class EmpresaAcesso
{
    /**
     * @param array<int, string> $papeisPermitidos Vazio = qualquer papel ativo serve.
     */
    public static function temAcesso(
        PDO $pdo,
        int $empresaId,
        int $usuarioId,
        array $papeisPermitidos = []
    ): bool {
        if ($empresaId <= 0 || $usuarioId <= 0) {
            return false;
        }

        $sql = "
            SELECT ee.papel
            FROM empresa_equipe ee
            WHERE ee.empresa_id = :empresa_id
              AND ee.usuario_id = :usuario_id
              AND ee.status = 'ativo'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':usuario_id' => $usuarioId,
        ]);
        $linha = $stmt->fetch();

        if (!$linha) {
            return false;
        }

        if (empty($papeisPermitidos)) {
            return true;
        }

        return in_array($linha['papel'], $papeisPermitidos, true);
    }

    /**
     * Retorna o papel do usuário na empresa, ou null se ele não tiver acesso.
     */
    public static function meuPapel(PDO $pdo, int $empresaId, int $usuarioId): ?string
    {
        $stmt = $pdo->prepare("
            SELECT papel FROM empresa_equipe
            WHERE empresa_id = ? AND usuario_id = ? AND status = 'ativo'
        ");
        $stmt->execute([$empresaId, $usuarioId]);
        $linha = $stmt->fetch();

        return $linha['papel'] ?? null;
    }
}
