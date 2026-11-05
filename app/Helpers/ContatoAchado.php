<?php

declare(strict_types=1);

/**
 * Opção "mostrar meu telefone neste alerta" dos pets ENCONTRADOS.
 *
 * Por padrão o telefone de quem achou o pet NÃO aparece; o contato é pelo chat.
 * Quem publica pode ligar a opção (e desligar depois). Mesmo ligada, o telefone
 * só é mostrado a usuários logados.
 *
 * Se a coluna pets.mostrar_telefone ainda não existir (migração 031 não rodada),
 * tudo aqui falha de forma segura: o telefone continua oculto.
 */
class ContatoAchado
{
    /**
     * @param  array<int|string> $petIds
     * @return array<int,bool>   ids de pets encontrados com a opção ligada
     */
    public static function ligados(array $petIds): array
    {
        $petIds = array_values(array_unique(array_filter(array_map('intval', $petIds))));
        if ($petIds === []) {
            return [];
        }

        try {
            $marcas = implode(',', array_fill(0, count($petIds), '?'));
            $stmt = Database::conectar()->prepare(
                "SELECT id FROM pets WHERE mostrar_telefone = 1 AND status = 'Encontrado' AND id IN ($marcas)"
            );
            $stmt->execute($petIds);

            return array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
        } catch (Throwable $erro) {
            error_log('[ContatoAchado] ' . $erro->getMessage());
            return [];
        }
    }

    /** Liga/desliga a opção. Só o dono do alerta consegue, e só em pet Encontrado. */
    public static function alternar(int $petId, int $usuarioId, bool $mostrar): bool
    {
        try {
            $pdo = Database::conectar();

            $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND usuario_id = ? AND status = 'Encontrado'");
            $stmt->execute([$petId, $usuarioId]);
            if (!$stmt->fetchColumn()) {
                return false;
            }

            $pdo->prepare('UPDATE pets SET mostrar_telefone = ? WHERE id = ?')->execute([$mostrar ? 1 : 0, $petId]);

            return true;
        } catch (Throwable $erro) {
            error_log('[ContatoAchado] ' . $erro->getMessage());
            return false;
        }
    }

    public static function usuarioTemTelefone(int $usuarioId): bool
    {
        try {
            $stmt = Database::conectar()->prepare('SELECT telefone FROM usuarios WHERE id = ?');
            $stmt->execute([$usuarioId]);

            return trim((string) $stmt->fetchColumn()) !== '';
        } catch (Throwable $erro) {
            error_log('[ContatoAchado] ' . $erro->getMessage());
            return false;
        }
    }
}
