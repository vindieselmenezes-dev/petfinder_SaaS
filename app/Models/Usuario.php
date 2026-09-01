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
    public function emailExiste(string $email, ?int $excetoId = null): bool
    {
        $sql = "SELECT id
                FROM usuarios
                WHERE email = :email";

        if ($excetoId !== null) {
            $sql .= " AND id != :exceto_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);

        $params = [':email' => $email];
        if ($excetoId !== null) {
            $params[':exceto_id'] = $excetoId;
        }

        $stmt->execute($params);

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

    /**
     * Salva a última localização (GPS) conhecida do usuário.
     * Usada tanto pro raio de 5km de alertas de pet perdido quanto
     * pra indicar empresas/serviços próximos.
     */
    public function salvarLocalizacao(int $usuarioId, float $latitude, float $longitude): bool
    {
        $sql = "
            UPDATE usuarios
            SET latitude = :latitude, longitude = :longitude
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':latitude'  => $latitude,
            ':longitude' => $longitude,
            ':id'        => $usuarioId,
        ]);
    }

    /**
     * Atualiza os dados básicos de um usuário (uso administrativo)
     */
    public function atualizarDados(int $id, string $nome, string $sobrenome, string $email, ?string $telefone): bool
    {
        $sql = "
            UPDATE usuarios
            SET nome = :nome, sobrenome = :sobrenome, email = :email, telefone = :telefone
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nome'      => $nome,
            ':sobrenome' => $sobrenome,
            ':email'     => $email,
            ':telefone'  => $telefone,
            ':id'        => $id,
        ]);
    }

    /**
     * Lista os IDs de todos os administradores da plataforma (usados,
     * por exemplo, pra notificar todo mundo quando um chamado é aberto).
     * Considera perfis.tipo quando existir, senão cai pra usuarios.tipo_usuario
     * (mesma regra de fallback usada no login).
     */
    public function listarIdsAdministradores(): array
    {
        $sql = "
            SELECT DISTINCT u.id
            FROM usuarios u
            LEFT JOIN perfis p ON p.usuario_id = u.id
            WHERE COALESCE(p.tipo, u.tipo_usuario) = 'administrador'
        ";

        $stmt = $this->pdo->query($sql);

        return array_column($stmt->fetchAll(), 'id');
    }
}
