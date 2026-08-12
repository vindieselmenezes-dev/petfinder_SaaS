<?php
/**
 * ==========================================================
 * PETFINDER BRASIL
 * Arquivo: app/Models/Usuario.php
 * ==========================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Usuario
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
     * Verifica se o e-mail já existe
     */
    public function emailExiste(string $email): bool
    {
        $sql = "SELECT id
                FROM usuarios
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch() !== false;
    }

   public function cadastrar(array $dados): bool
{
    try {

        $sql = "INSERT INTO usuarios
        (
            nome,
            sobrenome,
            email,
            senha,
            telefone
        )
        VALUES
        (
            :nome,
            :sobrenome,
            :email,
            :senha,
            :telefone
        )";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([

            ':nome'       => $dados['nome'],
            ':sobrenome'  => $dados['sobrenome'],
            ':email'      => $dados['email'],
            ':senha'      => password_hash($dados['senha'], PASSWORD_DEFAULT),
            ':telefone'   => $dados['telefone']

        ]);

        return true;

    } catch (Throwable $e) {

        die(
            "<h2>ERRO DO BANCO</h2><pre>" .
            $e->getMessage() .
            "</pre>"
        );

    }
}
    /**
     * Busca usuário pelo e-mail, incluindo perfil
     */
    public function buscarPorEmail(string $email): array|false
    {
        $sql = "SELECT u.*, p.tipo AS perfil_tipo
                FROM usuarios u
                LEFT JOIN perfis p
                    ON p.usuario_id = u.id
                WHERE u.email = :email
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch();
    }

    /**
     * Busca usuário pelo ID
     */
    public function buscarPorId(int $id): array|false
    {
        $sql = "SELECT *
                FROM usuarios
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    /**
     * Atualiza a senha de um usuário (já recebe o hash pronto)
     */
    public function atualizarSenha(int $id, string $novoHash): bool
    {
        $sql = "UPDATE usuarios
                SET senha = :senha
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':senha' => $novoHash,
            ':id'    => $id
        ]);
    }

    /**
     * Define o tipo de perfil de um usuário (cliente, empresa, veterinario, administrador)
     * Atualiza se já existir um registro, insere se não existir
     * (não depende de chave única em usuario_id, que pode não existir)
     */
    public function definirPerfil(int $usuarioId, string $tipo): bool
    {
        $sqlVerifica = "SELECT id FROM perfis WHERE usuario_id = :usuario_id LIMIT 1";
        $stmt = $this->pdo->prepare($sqlVerifica);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $existente = $stmt->fetch();

        if ($existente) {
            $sql = "UPDATE perfis SET tipo = :tipo WHERE usuario_id = :usuario_id";
        } else {
            $sql = "INSERT INTO perfis (usuario_id, tipo) VALUES (:usuario_id, :tipo)";
        }

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo'       => $tipo
        ]);
    }

    /**
     * Retorna o total de usuários cadastrados
     */
    public function contarUsuarios(): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Retorna todos os usuários
     */
    public function listarTodos(): array
    {
            $sql = "SELECT u.id, u.nome, u.sobrenome, u.email, u.telefone, COALESCE(p.tipo, 'cliente') AS perfil 
            FROM usuarios u 
            LEFT JOIN perfis p ON p.usuario_id = u.id 
            GROUP BY u.id
            ORDER BY u.nome";


        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Deleta um usuário pelo ID
     */
    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM usuarios WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }
}
