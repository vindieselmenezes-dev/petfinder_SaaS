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
            SELECT *
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
            ':estado'       => $dados['estado']
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
                estado = :estado
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
            ':id'           => $enderecoId
        ]);
    }
}
