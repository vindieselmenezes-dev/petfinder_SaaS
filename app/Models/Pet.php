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

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->pdo = Database::conectar();
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
                p.*,
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

        $this->registrarHistoricoStatus($novoId, null, $dados['status'], $dados['usuario_id'] ?? null, 'Cadastro do pet');

        return $novoId;
    }

    /**
     * Garante que a tabela de imagens do pet exista.
     */
    private function garantirTabelaImagens(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM pet_imagens LIMIT 1");
            return;
        } catch (PDOException $e) {
            $mensagem = $e->getMessage();

            if (!str_contains($mensagem, '42S02') && !str_contains($mensagem, 'Base table or view not found')) {
                throw $e;
            }

            $this->pdo->exec(
                "
                CREATE TABLE IF NOT EXISTS pet_imagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pet_id INT NOT NULL,
                    arquivo VARCHAR(255) NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_pet_imagem_pet
                        FOREIGN KEY (pet_id)
                        REFERENCES pets(id)
                        ON DELETE CASCADE
                )
                "
            );
        }
    }

    /**
     * Salva as imagens adicionais de um pet
     */
    public function salvarImagens(int $petId, array $imagens): bool
    {
        try {
            $this->garantirTabelaImagens();

            if (count($imagens) === 0) {
                return true;
            }

            $sql = "
                INSERT INTO pet_imagens
                (
                    pet_id,
                    arquivo
                )
                VALUES
                (
                    :pet_id,
                    :arquivo
                )
            ";

            $stmt = $this->pdo->prepare($sql);

            foreach ($imagens as $imagem) {
                if (
                    !$stmt->execute([
                        ':pet_id' => $petId,
                        ':arquivo' => $imagem
                    ])
                ) {
                    return false;
                }
            }

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Busca as imagens adicionais de um pet
     */
    public function buscarImagens(int $petId): array
    {
        try {
            $this->garantirTabelaImagens();

            $sql = "
                SELECT id, arquivo
                FROM pet_imagens
                WHERE pet_id = :pet_id
                ORDER BY id
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pet_id' => $petId]);

            return $stmt->fetchAll();
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * Exclui uma imagem extra específica de um pet (a foto de perfil
     * não é afetada, é só a galeria adicional)
     */
    public function excluirImagem(int $imagemId, int $petId): bool
    {
        $sql = "
            DELETE FROM pet_imagens
            WHERE id = :id
              AND pet_id = :pet_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $imagemId,
            ':pet_id' => $petId,
        ]);
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
            $this->registrarHistoricoStatus($id, (string) $statusAntigo, $dados['status'], $dados['usuario_id'] ?? null);
        }

        return $ok;
    }

    /**
     * Registra uma mudança de status no histórico append-only. Chamado
     * internamente sempre que o status realmente muda, não precisa ser
     * chamado manualmente de fora.
     */
    private function registrarHistoricoStatus(int $petId, ?string $statusAnterior, string $statusNovo, ?int $usuarioId, ?string $motivo = null): void
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

    /**
     * Busca o histórico completo de mudanças de status de um pet,
     * mais recente primeiro
     */
    public function buscarHistoricoStatus(int $petId): array
    {
        $sql = "
            SELECT h.*, u.nome AS alterado_por_nome
            FROM pets_status_historico h
            LEFT JOIN usuarios u ON u.id = h.alterado_por
            WHERE h.pet_id = :pet_id
            ORDER BY h.criado_em DESC, h.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pet_id' => $petId]);

        return $stmt->fetchAll();
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
     * Busca pública de pets para adoção (sem exigir login)
     * Filtra por texto, cidade, espécie, raça, sexo, cor, castração, idade, peso, altura e status.
     */
    public function buscarAdocaoPublico(
        string $busca = '',
        string $cidade = '',
        int $especieId = 0,
        int $racaId = 0,
        string $sexo = '',
        string $cor = '',
        int $castrado = -1,
        int $idadeMin = 0,
        int $idadeMax = 0,
        float $pesoMin = 0.0,
        float $pesoMax = 0.0,
        float $alturaMin = 0.0,
        float $alturaMax = 0.0,
        string $status = 'Para Adoção',
        string $ordem = 'criado_em',
        string $direcao = 'DESC'
    ): array {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.foto,
                p.sexo,
                p.cor,
                p.status,
                p.observacoes,
                p.peso,
                p.altura,
                p.data_nascimento,
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
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (
                LOWER(p.nome) LIKE LOWER(:busca1)
                OR LOWER(e.nome) LIKE LOWER(:busca2)
                OR LOWER(r.nome) LIKE LOWER(:busca3)
                OR LOWER(p.cor) LIKE LOWER(:busca4)
                OR LOWER(p.microchip) LIKE LOWER(:busca5)
                OR LOWER(p.observacoes) LIKE LOWER(:busca6)
            ) ";
            $termo = "%{$busca}%";
            $params[':busca1'] = $termo;
            $params[':busca2'] = $termo;
            $params[':busca3'] = $termo;
            $params[':busca4'] = $termo;
            $params[':busca5'] = $termo;
            $params[':busca6'] = $termo;
        }

        if ($cidade !== '') {
            $sql .= " AND LOWER(end.cidade) = LOWER(:cidade) ";
            $params[':cidade'] = $cidade;
        }

        if ($status !== '' && $status !== 'Todos') {
            $sql .= " AND p.status = :status ";
            $params[':status'] = $status;
        }

        if ($especieId > 0) {
            $sql .= " AND p.especie_id = :especie_id ";
            $params[':especie_id'] = $especieId;
        }

        if ($racaId > 0) {
            $sql .= " AND p.raca_id = :raca_id ";
            $params[':raca_id'] = $racaId;
        }

        if ($sexo !== '') {
            $sql .= " AND p.sexo = :sexo ";
            $params[':sexo'] = $sexo;
        }

        if ($cor !== '') {
            $sql .= " AND LOWER(p.cor) LIKE LOWER(:cor) ";
            $params[':cor'] = "%{$cor}%";
        }

        if ($castrado === 0 || $castrado === 1) {
            $sql .= " AND p.castrado = :castrado ";
            $params[':castrado'] = $castrado;
        }

        if ($idadeMin > 0) {
            $sql .= " AND COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0) >= :idade_min ";
            $params[':idade_min'] = $idadeMin;
        }

        if ($idadeMax > 0) {
            $sql .= " AND COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0) <= :idade_max ";
            $params[':idade_max'] = $idadeMax;
        }

        if ($pesoMin > 0) {
            $sql .= " AND p.peso >= :peso_min ";
            $params[':peso_min'] = $pesoMin;
        }

        if ($pesoMax > 0) {
            $sql .= " AND p.peso <= :peso_max ";
            $params[':peso_max'] = $pesoMax;
        }

        if ($alturaMin > 0) {
            $sql .= " AND p.altura >= :altura_min ";
            $params[':altura_min'] = $alturaMin;
        }

        if ($alturaMax > 0) {
            $sql .= " AND p.altura <= :altura_max ";
            $params[':altura_max'] = $alturaMax;
        }

        $ordemMapeada = 'p.criado_em';
        $direcaoMapeada = strtoupper($direcao) === 'ASC' ? 'ASC' : 'DESC';

        switch ($ordem) {
            case 'nome_asc':
                $ordemMapeada = 'p.nome';
                $direcaoMapeada = 'ASC';
                break;
            case 'nome_desc':
                $ordemMapeada = 'p.nome';
                $direcaoMapeada = 'DESC';
                break;
            case 'idade_asc':
                $ordemMapeada = 'COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0)';
                $direcaoMapeada = 'ASC';
                break;
            case 'idade_desc':
                $ordemMapeada = 'COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0)';
                $direcaoMapeada = 'DESC';
                break;
            case 'antigo':
                $ordemMapeada = 'p.criado_em';
                $direcaoMapeada = 'ASC';
                break;
            case 'recente':
            default:
                $ordemMapeada = 'p.criado_em';
                $direcaoMapeada = 'DESC';
                break;
        }

        $sql .= " ORDER BY {$ordemMapeada} {$direcaoMapeada}, p.id DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Lista todos os pets da plataforma com um determinado status
     * (usado nas telas públicas de Pets Perdidos / Pets Encontrados)
     */
    public function listarPorStatus(string $status): array
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
            $this->registrarHistoricoStatus($petId, (string) $statusAntigo, $status, $usuarioId, $motivo);
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
