<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Pet
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Pet
{
    /**
     * Conexão com o banco
     */
    private PDO $pdo;

    private PetHistorico $historico;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pdo = Database::conectar();
        $this->historico = new PetHistorico();
    }

    /**
     * Lista todas as espécies ativas
     */
    public function listarEspecies(): array
    {
        $sql = "
            SELECT id, nome
            FROM especies
            WHERE ativo = 1
            ORDER BY nome
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Lista as cidades onde já existe pelo menos um pet cadastrado
     * (via endereço do tutor), pra alimentar sugestões de busca sem
     * travar numa lista fixa — qualquer cidade digitada continua
     * podendo ser buscada, isso aqui é só pra sugerir/autocompletar.
     */
    public function listarCidadesComPets(): array
    {
        $sql = "
            SELECT DISTINCT end.cidade
            FROM pets p
            INNER JOIN enderecos end ON end.usuario_id = p.usuario_id
            WHERE end.cidade IS NOT NULL AND end.cidade != ''
            ORDER BY end.cidade
        ";

        $stmt = $this->pdo->query($sql);

        return array_column($stmt->fetchAll(), 'cidade');
    }

    /**
     * Lista as raças de uma espécie
     */
    public function listarRacas(int $especieId): array
    {
        $sql = "
            SELECT id, nome
            FROM racas
            WHERE especie_id = :especie
              AND ativo = 1
            ORDER BY nome
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':especie' => $especieId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Retorna a quantidade total de pets cadastrados
     */
    public function contarPets(): int
    {
        $sql = "SELECT COUNT(*) FROM pets";

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Lista todos os pets cadastrados na plataforma
     */
    public function listarTodos(): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.status,
                p.sexo,
                p.cor,
                p.foto,
                e.nome AS especie,
                r.nome AS raca,
                u.nome AS tutor_nome,
                u.email AS tutor_email,
                p.criado_em
            FROM pets p
            INNER JOIN especies e ON e.id = p.especie_id
            INNER JOIN racas r ON r.id = p.raca_id
            INNER JOIN usuarios u ON u.id = p.usuario_id
            ORDER BY p.criado_em DESC, p.id DESC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Lista todos os pets do usuário logado
     * A cidade vem do endereço principal do usuário (tabela enderecos)
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.foto,
                p.sexo,
                p.cor,
                p.status,
                p.data_nascimento,
                p.criado_em,
                e.nome AS especie,
                r.nome AS raca,
                end.cidade,
                end.estado
            FROM pets p
            INNER JOIN especies e
                ON e.id = p.especie_id
            INNER JOIN racas r
                ON r.id = p.raca_id
            LEFT JOIN enderecos end
                ON end.usuario_id = p.usuario_id
                AND end.principal = 1
            WHERE p.usuario_id = :usuario
            ORDER BY p.criado_em DESC, p.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Busca um pet pelo ID
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                p.id, p.usuario_id, p.especie_id, p.raca_id, p.status, p.nome,
                p.sexo, p.cor, p.peso, p.altura, p.data_nascimento, p.microchip,
                p.token_identidade, p.castrado, p.foto, p.observacoes,
                p.criado_em, p.atualizado_em,
                e.nome AS especie,
                r.nome AS raca,
                end.cidade,
                end.estado,
                u.nome AS tutor_nome,
                u.telefone AS tutor_telefone
            FROM pets p
            INNER JOIN especies e
                ON e.id = p.especie_id
            INNER JOIN racas r
                ON r.id = p.raca_id
            INNER JOIN usuarios u
                ON u.id = p.usuario_id
            LEFT JOIN enderecos end
                ON end.usuario_id = p.usuario_id
                AND end.principal = 1
            WHERE p.id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Busca um pet pelo token público de identidade (usado na carteirinha
     * digital / QR code -- ideia da página "identidadepet.html" do
     * projetointegrador, aproveitando o que já existe: microchip, espécie,
     * raça e tutor).
     */
    public function buscarPorToken(string $token): ?array
    {
        $sql = "
            SELECT
                p.id, p.usuario_id, p.especie_id, p.raca_id, p.status, p.nome,
                p.sexo, p.cor, p.peso, p.altura, p.data_nascimento, p.microchip,
                p.token_identidade, p.castrado, p.foto, p.observacoes,
                p.criado_em, p.atualizado_em,
                e.nome AS especie,
                r.nome AS raca,
                u.nome AS tutor_nome,
                u.telefone AS tutor_telefone
            FROM pets p
            INNER JOIN especies e ON e.id = p.especie_id
            INNER JOIN racas r ON r.id = p.raca_id
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.token_identidade = :token
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Garante que o pet tenha um token de identidade público, gerando um
     * novo se ainda não existir (pets cadastrados antes da migration 014
     * já recebem o token nela; isso aqui cobre qualquer caso residual).
     */
    public function garantirTokenIdentidade(int $petId): string
    {
        $stmt = $this->pdo->prepare("SELECT token_identidade FROM pets WHERE id = :id");
        $stmt->execute([':id' => $petId]);
        $token = $stmt->fetchColumn();

        if ($token) {
            return $token;
        }

        $novoToken = bin2hex(random_bytes(16));

        $stmt = $this->pdo->prepare("UPDATE pets SET token_identidade = :token WHERE id = :id");
        $stmt->execute([':token' => $novoToken, ':id' => $petId]);

        return $novoToken;
    }

    /**
     * Cadastra um novo pet
     */
    public function cadastrar(array $dados): int|false
    {
        $sql = "
            INSERT INTO pets
            (
                usuario_id,
                nome,
                especie_id,
                raca_id,
                sexo,
                cor,
                status,
                peso,
                altura,
                data_nascimento,
                microchip,
                castrado,
                observacoes,
                foto
            )
            VALUES
            (
                :usuario_id,
                :nome,
                :especie_id,
                :raca_id,
                :sexo,
                :cor,
                :status,
                :peso,
                :altura,
                :data_nascimento,
                :microchip,
                :castrado,
                :observacoes,
                :foto
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        if (
            !$stmt->execute([
                ':usuario_id' => $dados['usuario_id'],
                ':nome' => $dados['nome'],
                ':especie_id' => $dados['especie_id'],
                ':raca_id' => $dados['raca_id'],
                ':sexo' => $dados['sexo'],
                ':cor' => $dados['cor'],
                ':status' => $dados['status'],
                ':peso' => $dados['peso'],
                ':altura' => $dados['altura'],
                ':data_nascimento' => $dados['data_nascimento'],
                ':microchip' => $dados['microchip'],
                ':castrado' => $dados['castrado'],
                ':observacoes' => $dados['observacoes'],
                ':foto' => $dados['foto']
            ])
        ) {
            return false;
        }

        $novoId = (int) $this->pdo->lastInsertId();

        $this->historico->registrarHistoricoStatus($novoId, null, $dados['status'], $dados['usuario_id'] ?? null, 'Cadastro do pet');

        return $novoId;
    }

    /**
     * Atualiza um pet (só se pertencer ao usuário)
     */
    public function atualizar(int $id, array $dados): bool
    {
        // Busca o status atual antes de sobrescrever, pra saber se mudou
        $stmtAtual = $this->pdo->prepare("SELECT status FROM pets WHERE id = :id");
        $stmtAtual->execute([':id' => $id]);
        $statusAntigo = $stmtAtual->fetchColumn();

        $sql = "
            UPDATE pets
            SET
                nome = :nome,
                especie_id = :especie_id,
                raca_id = :raca_id,
                sexo = :sexo,
                cor = :cor,
                status = :status,
                peso = :peso,
                altura = :altura,
                data_nascimento = :data_nascimento,
                microchip = :microchip,
                castrado = :castrado,
                observacoes = :observacoes,
                foto = :foto
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        $ok = $stmt->execute([
            ':nome' => $dados['nome'],
            ':especie_id' => $dados['especie_id'],
            ':raca_id' => $dados['raca_id'],
            ':sexo' => $dados['sexo'],
            ':cor' => $dados['cor'],
            ':status' => $dados['status'],
            ':peso' => $dados['peso'],
            ':altura' => $dados['altura'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':microchip' => $dados['microchip'],
            ':castrado' => $dados['castrado'],
            ':observacoes' => $dados['observacoes'],
            ':foto' => $dados['foto'],
            ':id' => $id,
            ':usuario_id' => $dados['usuario_id']
        ]);

        if ($ok && $statusAntigo !== false && $statusAntigo !== $dados['status']) {
            $this->historico->registrarHistoricoStatus($id, (string) $statusAntigo, $dados['status'], $dados['usuario_id'] ?? null);
        }

        return $ok;
    }

    /**
     * Exclui um pet (só se pertencer ao usuário)
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "
            DELETE FROM pets
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);
    }

    /**
     * Exclui um pet por ID, usado pelo painel administrativo
     */
    public function excluirPorId(int $id): bool
    {
        $sql = "DELETE FROM pets WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Lista todos os pets da plataforma com um determinado status
     * (usado nas telas públicas de Pets Perdidos / Pets Encontrados)
     *
     * $pagina e $porPagina permitem paginar o resultado. Por padrão
     * ($pagina = 1, $porPagina = 0) o comportamento é o mesmo de sempre:
     * traz todos os registros, sem LIMIT.
     */
    public function listarPorStatus(string $status, int $pagina = 1, int $porPagina = 0): array
    {
        $sql = "
            SELECT
                p.id,
                p.usuario_id,
                p.nome,
                p.foto,
                p.sexo,
                p.cor,
                p.status,
                p.observacoes,
                p.criado_em,
                e.nome AS especie,
                r.nome AS raca,
                (
                    SELECT cidade FROM enderecos
                    WHERE usuario_id = p.usuario_id
                    ORDER BY principal DESC, id ASC
                    LIMIT 1
                ) AS cidade,
                (
                    SELECT estado FROM enderecos
                    WHERE usuario_id = p.usuario_id
                    ORDER BY principal DESC, id ASC
                    LIMIT 1
                ) AS estado,
                u.nome AS tutor_nome,
                u.telefone AS tutor_telefone
            FROM pets p
            INNER JOIN especies e
                ON e.id = p.especie_id
            INNER JOIN racas r
                ON r.id = p.raca_id
            INNER JOIN usuarios u
                ON u.id = p.usuario_id
            WHERE p.status = :status
            ORDER BY p.criado_em DESC, p.id DESC
        ";

        if ($porPagina > 0) {
            $pagina = max(1, $pagina);
            $offset = ($pagina - 1) * $porPagina;
            // LIMIT/OFFSET não aceitam bind nativo de forma confiável em
            // todos os drivers, mas aqui os valores já são inteiros
            // controlados pelo próprio código (int), então é seguro
            // concatenar diretamente.
            $sql .= " LIMIT {$porPagina} OFFSET {$offset} ";
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':status' => $status
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Conta pets por status
     */
    public function contarPorStatus(string $status): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM pets
            WHERE status = :status
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':status' => $status
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Atualiza somente o status de um pet
     * (usado para marcar como Perdido, Encontrado, Adotado, etc.)
     */
    public function atualizarStatus(int $petId, string $status, ?int $usuarioId = null, ?string $motivo = null): bool
    {
        $stmtAtual = $this->pdo->prepare("SELECT status FROM pets WHERE id = :id");
        $stmtAtual->execute([':id' => $petId]);
        $statusAntigo = $stmtAtual->fetchColumn();

        $sql = "
            UPDATE pets
            SET status = :status
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $ok = $stmt->execute([
            ':status' => $status,
            ':id' => $petId
        ]);

        if ($ok && $statusAntigo !== false && $statusAntigo !== $status) {
            $this->historico->registrarHistoricoStatus($petId, (string) $statusAntigo, $status, $usuarioId, $motivo);
        }

        return $ok;
    }

    /**
     * Transfere a posse de um pet pra outro usuário (usado quando uma
     * solicitação de adoção é aprovada)
     */
    public function transferirTutor(int $petId, int $novoTutorId): bool
    {
        $sql = "
            UPDATE pets
            SET usuario_id = :novo_tutor_id
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':novo_tutor_id' => $novoTutorId,
            ':id' => $petId
        ]);
    }
}
