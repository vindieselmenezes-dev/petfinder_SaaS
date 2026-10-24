<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Endereco
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Endereco
{
    /**
     * Conexão com o banco
     */
    private PDO $pdo;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Busca o endereço principal do usuário
     */
    public function buscarPorUsuario(int $usuarioId): ?array
    {
        $sql = "
            SELECT
                id, usuario_id, cep, logradouro, numero, complemento, bairro,
                cidade, estado, referencia, latitude, longitude, principal
            FROM enderecos
            WHERE usuario_id = :usuario
              AND principal = 1
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Siglas dos estados (usadas em formulários e validações).
     */
    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
        'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public static function ufValida(string $uf): bool
    {
        return in_array($uf, self::UFS, true);
    }

    /**
     * Nome de cidade plausível: começa com letra e só tem letras, espaço,
     * apóstrofo, ponto ou hífen (ex.: "Santa Bárbara d'Oeste", "Belo Horizonte").
     */
    public static function cidadeValida(string $cidade): bool
    {
        return preg_match('/^\p{L}[\p{L}\s\'.\-]{1,99}$/u', $cidade) === 1;
    }

    /**
     * Endereço serve para ENTREGA? Cidade e UF sozinhas (o mínimo que o perfil e o
     * cadastro de pet pedem) não bastam: falta rua e número para o pedido chegar.
     */
    public static function estaCompleto(?array $endereco): bool
    {
        if ($endereco === null) {
            return false;
        }

        foreach (['logradouro', 'numero', 'cidade', 'estado'] as $campo) {
            if (trim((string) ($endereco[$campo] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Garante cidade/UF no endereço principal SEM apagar o que já existe:
     *  - não existe endereço principal  -> cria um só com cidade e UF;
     *  - existe, mas sem cidade         -> preenche só cidade e UF;
     *  - existe e já tem cidade         -> não mexe em nada.
     */
    public function salvarCidadeSeAusente(int $usuarioId, string $cidade, string $uf): bool
    {
        $existente = $this->buscarPorUsuario($usuarioId);

        if ($existente === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO enderecos (usuario_id, cidade, estado, principal) VALUES (:usuario, :cidade, :estado, 1)'
            );

            return $stmt->execute([':usuario' => $usuarioId, ':cidade' => $cidade, ':estado' => $uf]);
        }

        if (trim((string) ($existente['cidade'] ?? '')) !== '') {
            return true;
        }

        $stmt = $this->pdo->prepare('UPDATE enderecos SET cidade = :cidade, estado = :estado WHERE id = :id');

        return $stmt->execute([':cidade' => $cidade, ':estado' => $uf, ':id' => (int) $existente['id']]);
    }

    /**
     * Salva o endereço principal do usuário.
     * Se já existir um endereço principal, atualiza.
     * Se não existir, cria um novo.
     */
    public function salvar(array $dados): bool
    {
        $existente = $this->buscarPorUsuario((int) $dados['usuario_id']);

        if ($existente !== null) {
            return $this->atualizar($existente['id'], $dados);
        }

        return $this->inserir($dados);
    }

    /**
     * Insere um novo endereço
     */
    private function inserir(array $dados): bool
    {
        $sql = "
            INSERT INTO enderecos
            (
                usuario_id,
                cep,
                logradouro,
                numero,
                complemento,
                bairro,
                cidade,
                estado,
                referencia,
                principal
            )
            VALUES
            (
                :usuario_id,
                :cep,
                :logradouro,
                :numero,
                :complemento,
                :bairro,
                :cidade,
                :estado,
                :referencia,
                1
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id'   => $dados['usuario_id'],
            ':cep'          => $dados['cep'],
            ':logradouro'   => $dados['logradouro'],
            ':numero'       => $dados['numero'],
            ':complemento'  => $dados['complemento'],
            ':bairro'       => $dados['bairro'],
            ':cidade'       => $dados['cidade'],
            ':estado'       => $dados['estado'],
            ':referencia'   => $dados['referencia'] ?? null,
        ]);
    }

    /**
     * Atualiza um endereço existente
     */
    private function atualizar(int $enderecoId, array $dados): bool
    {
        $sql = "
            UPDATE enderecos
            SET
                cep = :cep,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                referencia = :referencia
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':cep'          => $dados['cep'],
            ':logradouro'   => $dados['logradouro'],
            ':numero'       => $dados['numero'],
            ':complemento'  => $dados['complemento'],
            ':bairro'       => $dados['bairro'],
            ':cidade'       => $dados['cidade'],
            ':estado'       => $dados['estado'],
            ':referencia'   => $dados['referencia'] ?? null,
            ':id'           => $enderecoId
        ]);
    }
}
