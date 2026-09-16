<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Prestador
 * ==========================================================
 * Prestadores de servico autonomos (Passeador e Pet Sitter).
 * Ideia trazida do projeto "projetointegrador" e integrada de
 * verdade ao banco: aqui o profissional se cadastra com o
 * proprio CPF, atrelado a conta de usuario (usuarios.id).
 */

require_once __DIR__ . '/../../config/database.php';

class Prestador
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public const TIPOS_VALIDOS = ['passeador', 'pet_sitter', 'taxista_pet', 'adestrador'];

    /**
     * Verifica se o usuário já possui um perfil desse tipo de prestador
     */
    public function buscarPorUsuarioETipo(int $usuarioId, string $tipo): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id, usuario_id, tipo, plano_id, cpf, genero, data_nascimento,
                telefone, whatsapp, email, cep, endereco, numero, complemento,
                bairro, cidade, estado, foto, tempo_experiencia, formacao,
                experiencia, apresentacao, diferencial, valor_hora, valor_diaria,
                forma_pagamento, area_atendimento, instagram, facebook,
                avaliacao, total_avaliacoes, status, ativo, criado_em,
                atualizado_em, plano_iniciado_em, plano_expira_em
            FROM prestadores_servico
            WHERE usuario_id = :usuario_id
              AND tipo = :tipo
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo' => $tipo
        ]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista todos os perfis de prestador de um usuário (pode ter os dois tipos)
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id, usuario_id, tipo, plano_id, cpf, genero, data_nascimento,
                telefone, whatsapp, email, cep, endereco, numero, complemento,
                bairro, cidade, estado, foto, tempo_experiencia, formacao,
                experiencia, apresentacao, diferencial, valor_hora, valor_diaria,
                forma_pagamento, area_atendimento, instagram, facebook,
                avaliacao, total_avaliacoes, status, ativo, criado_em,
                atualizado_em, plano_iniciado_em, plano_expira_em
            FROM prestadores_servico
            WHERE usuario_id = :usuario_id
            ORDER BY criado_em DESC
        ");

        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Cadastra um novo prestador. Retorna o ID gerado, ou false em caso de erro.
     */
    public function cadastrar(array $dados): int|false
    {
        // Todo prestador (Passeador/Pet Sitter/Táxi Pet) começa no plano
        // Grátis com prazo -- mesma regra das empresas, ninguém fica de
        // graça pra sempre.
        $planoId = $dados['plano_id'] ?? null;

        if (empty($planoId)) {
            $stmtPlano = $this->pdo->query("SELECT id FROM planos WHERE slug = 'gratis' LIMIT 1");
            $planoGratis = $stmtPlano->fetch();
            $planoId = $planoGratis ? (int) $planoGratis['id'] : null;
        }

        $diasTrial = 0;
        if ($planoId) {
            $stmtDias = $this->pdo->prepare("SELECT dias_trial FROM planos WHERE id = :id");
            $stmtDias->execute([':id' => $planoId]);
            $diasTrial = (int) $stmtDias->fetchColumn();
        }

        $sql = "
            INSERT INTO prestadores_servico
            (
                usuario_id, tipo, plano_id, plano_iniciado_em, plano_expira_em,
                cpf, genero, data_nascimento,
                telefone, whatsapp, email,
                cep, endereco, numero, complemento, bairro, cidade, estado,
                foto, tempo_experiencia, formacao, experiencia, apresentacao,
                diferencial, valor_hora, valor_diaria, forma_pagamento,
                area_atendimento, instagram, facebook
            )
            VALUES
            (
                :usuario_id, :tipo, :plano_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL :dias_trial DAY),
                :cpf, :genero, :data_nascimento,
                :telefone, :whatsapp, :email,
                :cep, :endereco, :numero, :complemento, :bairro, :cidade, :estado,
                :foto, :tempo_experiencia, :formacao, :experiencia, :apresentacao,
                :diferencial, :valor_hora, :valor_diaria, :forma_pagamento,
                :area_atendimento, :instagram, :facebook
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $sucesso = $stmt->execute([
            ':usuario_id' => $dados['usuario_id'],
            ':tipo' => $dados['tipo'],
            ':plano_id' => $planoId,
            ':dias_trial' => $diasTrial,
            ':cpf' => ($dados['cpf'] ?? '') ?: null,
            ':genero' => ($dados['genero'] ?? '') ?: 'nao-informar',
            ':data_nascimento' => ($dados['data_nascimento'] ?? '') ?: null,
            ':telefone' => ($dados['telefone'] ?? '') ?: null,
            ':whatsapp' => ($dados['whatsapp'] ?? '') ?: null,
            ':email' => ($dados['email'] ?? '') ?: null,
            ':cep' => ($dados['cep'] ?? '') ?: null,
            ':endereco' => ($dados['endereco'] ?? '') ?: null,
            ':numero' => ($dados['numero'] ?? '') ?: null,
            ':complemento' => ($dados['complemento'] ?? '') ?: null,
            ':bairro' => ($dados['bairro'] ?? '') ?: null,
            ':cidade' => ($dados['cidade'] ?? '') ?: null,
            ':estado' => ($dados['estado'] ?? '') ?: null,
            ':foto' => ($dados['foto'] ?? '') ?: null,
            ':tempo_experiencia' => ($dados['tempo_experiencia'] ?? '') ?: null,
            ':formacao' => ($dados['formacao'] ?? '') ?: null,
            ':experiencia' => ($dados['experiencia'] ?? '') ?: null,
            ':apresentacao' => ($dados['apresentacao'] ?? '') ?: null,
            ':diferencial' => ($dados['diferencial'] ?? '') ?: null,
            ':valor_hora' => ($dados['valor_hora'] ?? '') !== '' ? $dados['valor_hora'] : null,
            ':valor_diaria' => ($dados['valor_diaria'] ?? '') !== '' ? $dados['valor_diaria'] : null,
            ':forma_pagamento' => ($dados['forma_pagamento'] ?? '') ?: null,
            ':area_atendimento' => ($dados['area_atendimento'] ?? '') ?: null,
            ':instagram' => ($dados['instagram'] ?? '') ?: null,
            ':facebook' => ($dados['facebook'] ?? '') ?: null,
        ]);

        if (!$sucesso) {
            return false;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Busca um prestador pelo ID, com dados do usuário (nome, foto de perfil)
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id, p.usuario_id, p.tipo, p.plano_id, p.cpf, p.genero,
                p.data_nascimento, p.telefone, p.whatsapp, p.email, p.cep,
                p.endereco, p.numero, p.complemento, p.bairro, p.cidade,
                p.estado, p.foto, p.tempo_experiencia, p.formacao,
                p.experiencia, p.apresentacao, p.diferencial, p.valor_hora,
                p.valor_diaria, p.forma_pagamento, p.area_atendimento,
                p.instagram, p.facebook, p.avaliacao, p.total_avaliacoes,
                p.status, p.ativo, p.criado_em, p.atualizado_em,
                p.plano_iniciado_em, p.plano_expira_em,
                u.nome AS usuario_nome,
                u.sobrenome AS usuario_sobrenome
            FROM prestadores_servico p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /**
     * Lista prestadores ativos para o diretório público, com filtro opcional
     * de tipo e cidade
     */
    public function listarAtivos(string $tipo = '', string $cidade = '', string $busca = ''): array
    {
        $sql = "
            SELECT
                p.id, p.usuario_id, p.tipo, p.plano_id, p.cpf, p.genero,
                p.data_nascimento, p.telefone, p.whatsapp, p.email, p.cep,
                p.endereco, p.numero, p.complemento, p.bairro, p.cidade,
                p.estado, p.foto, p.tempo_experiencia, p.formacao,
                p.experiencia, p.apresentacao, p.diferencial, p.valor_hora,
                p.valor_diaria, p.forma_pagamento, p.area_atendimento,
                p.instagram, p.facebook, p.avaliacao, p.total_avaliacoes,
                p.status, p.ativo, p.criado_em, p.atualizado_em,
                p.plano_iniciado_em, p.plano_expira_em,
                u.nome AS usuario_nome,
                u.sobrenome AS usuario_sobrenome
            FROM prestadores_servico p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.ativo = 1
              AND (p.plano_expira_em IS NULL OR p.plano_expira_em >= CURDATE())
        ";

        $params = [];

        if (in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $sql .= " AND p.tipo = :tipo ";
            $params[':tipo'] = $tipo;
        }

        if ($cidade !== '') {
            $sql .= " AND p.cidade = :cidade ";
            $params[':cidade'] = $cidade;
        }

        if ($busca !== '') {
            $sql .= " AND LOWER(u.nome) LIKE LOWER(:busca) ";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql .= " ORDER BY p.avaliacao DESC, p.criado_em DESC, p.id DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | SERVIÇOS, ANIMAIS ATENDIDOS E DISPONIBILIDADE (multivalorados)
    |--------------------------------------------------------------------------
    */

    public function salvarServicos(int $prestadorId, array $servicos): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM prestador_servicos WHERE prestador_id = :prestador_id");
        $stmt->execute([':prestador_id' => $prestadorId]);

        if (empty($servicos)) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_servicos (prestador_id, servico)
            VALUES (:prestador_id, :servico)
        ");

        foreach ($servicos as $servico) {
            $stmt->execute([':prestador_id' => $prestadorId, ':servico' => $servico]);
        }

        return true;
    }

    public function buscarServicos(int $prestadorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT servico FROM prestador_servicos WHERE prestador_id = :prestador_id ORDER BY id
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        return array_column($stmt->fetchAll(), 'servico');
    }

    public function salvarAnimaisAtendidos(int $prestadorId, array $animais): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM prestador_animais_atendidos WHERE prestador_id = :prestador_id");
        $stmt->execute([':prestador_id' => $prestadorId]);

        if (empty($animais)) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_animais_atendidos (prestador_id, animal)
            VALUES (:prestador_id, :animal)
        ");

        foreach ($animais as $animal) {
            $stmt->execute([':prestador_id' => $prestadorId, ':animal' => $animal]);
        }

        return true;
    }

    public function buscarAnimaisAtendidos(int $prestadorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT animal FROM prestador_animais_atendidos WHERE prestador_id = :prestador_id ORDER BY id
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        return array_column($stmt->fetchAll(), 'animal');
    }

    public function salvarDisponibilidade(int $prestadorId, array $dias, array $periodos): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM prestador_disponibilidade WHERE prestador_id = :prestador_id");
        $stmt->execute([':prestador_id' => $prestadorId]);

        if (empty($dias) || empty($periodos)) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_disponibilidade (prestador_id, dia_semana, periodo)
            VALUES (:prestador_id, :dia_semana, :periodo)
        ");

        foreach ($dias as $dia) {
            foreach ($periodos as $periodo) {
                $stmt->execute([
                    ':prestador_id' => $prestadorId,
                    ':dia_semana' => $dia,
                    ':periodo' => $periodo
                ]);
            }
        }

        return true;
    }

    public function buscarDisponibilidade(int $prestadorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT dia_semana, periodo
            FROM prestador_disponibilidade
            WHERE prestador_id = :prestador_id
            ORDER BY FIELD(dia_semana, 'Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo')
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | VEÍCULO (só para prestadores do tipo taxista_pet)
    |--------------------------------------------------------------------------
    */

    public function salvarVeiculo(int $prestadorId, array $dados): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_veiculo
                (prestador_id, tipo_veiculo, modelo, placa, ano, capacidade_pets,
                 ar_condicionado, caixa_transporte, aceita_animais_grandes,
                 valor_km, valor_corrida_minima)
            VALUES
                (:prestador_id, :tipo_veiculo, :modelo, :placa, :ano, :capacidade_pets,
                 :ar_condicionado, :caixa_transporte, :aceita_animais_grandes,
                 :valor_km, :valor_corrida_minima)
            ON DUPLICATE KEY UPDATE
                tipo_veiculo = VALUES(tipo_veiculo),
                modelo = VALUES(modelo),
                placa = VALUES(placa),
                ano = VALUES(ano),
                capacidade_pets = VALUES(capacidade_pets),
                ar_condicionado = VALUES(ar_condicionado),
                caixa_transporte = VALUES(caixa_transporte),
                aceita_animais_grandes = VALUES(aceita_animais_grandes),
                valor_km = VALUES(valor_km),
                valor_corrida_minima = VALUES(valor_corrida_minima)
        ");

        return $stmt->execute([
            ':prestador_id' => $prestadorId,
            ':tipo_veiculo' => ($dados['tipo_veiculo'] ?? '') ?: 'Carro',
            ':modelo' => ($dados['modelo'] ?? '') ?: null,
            ':placa' => ($dados['placa'] ?? '') ?: null,
            ':ano' => ($dados['ano'] ?? '') ?: null,
            ':capacidade_pets' => (int) (($dados['capacidade_pets'] ?? '') ?: 1),
            ':ar_condicionado' => !empty($dados['ar_condicionado']) ? 1 : 0,
            ':caixa_transporte' => !empty($dados['caixa_transporte']) ? 1 : 0,
            ':aceita_animais_grandes' => !empty($dados['aceita_animais_grandes']) ? 1 : 0,
            ':valor_km' => ($dados['valor_km'] ?? '') !== '' ? $dados['valor_km'] : null,
            ':valor_corrida_minima' => ($dados['valor_corrida_minima'] ?? '') !== '' ? $dados['valor_corrida_minima'] : null,
        ]);
    }

    public function buscarVeiculo(int $prestadorId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                prestador_id, tipo_veiculo, modelo, placa, ano, capacidade_pets,
                ar_condicionado, caixa_transporte, aceita_animais_grandes,
                valor_km, valor_corrida_minima
            FROM prestador_veiculo
            WHERE prestador_id = :prestador_id
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | PLANO / PERÍODO DE TESTE
    |--------------------------------------------------------------------------
    */

    /**
     * Troca o plano do prestador (mesmo esquema simulado das empresas --
     * ver public/simular_assinatura.php). O plano Grátis só vale uma vez,
     * no cadastro.
     */
    public function atualizarPlano(int $prestadorId, int $planoId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE prestadores_servico ps
            INNER JOIN planos p ON p.id = :plano_id
            SET
                ps.plano_id = :plano_id_set,
                ps.plano_iniciado_em = CURDATE(),
                ps.plano_expira_em = DATE_ADD(CURDATE(), INTERVAL p.dias_trial DAY)
            WHERE ps.id = :prestador_id
        ");

        return $stmt->execute([
            ':plano_id' => $planoId,
            ':plano_id_set' => $planoId,
            ':prestador_id' => $prestadorId,
        ]);
    }

    /**
     * Verifica se o período de teste/plano do prestador já venceu sem
     * assinatura de um plano pago.
     */
    public function trialVencido(int $prestadorId): bool
    {
        $stmt = $this->pdo->prepare("SELECT plano_expira_em FROM prestadores_servico WHERE id = :id");
        $stmt->execute([':id' => $prestadorId]);
        $expiraEm = $stmt->fetchColumn();

        if (!$expiraEm) {
            return false;
        }

        return strtotime($expiraEm) < strtotime(date('Y-m-d'));
    }

    /*
    |--------------------------------------------------------------------------
    | AVALIAÇÕES
    |--------------------------------------------------------------------------
    */

    public function avaliar(int $prestadorId, int $usuarioId, int $nota, string $comentario = ''): bool
    {
        if ($prestadorId <= 0 || $usuarioId <= 0 || $nota < 1 || $nota > 5) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM prestador_avaliacoes WHERE prestador_id = :prestador_id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute([':prestador_id' => $prestadorId, ':usuario_id' => $usuarioId]);

        if ($stmt->fetch()) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO prestador_avaliacoes (prestador_id, usuario_id, nota, comentario)
                 VALUES (:prestador_id, :usuario_id, :nota, :comentario)'
            );
            $stmt->execute([
                ':prestador_id' => $prestadorId,
                ':usuario_id' => $usuarioId,
                ':nota' => $nota,
                ':comentario' => $comentario ?: null
            ]);

            $stmt = $this->pdo->prepare(
                'UPDATE prestadores_servico
                 SET avaliacao = (SELECT ROUND(AVG(nota), 1) FROM prestador_avaliacoes WHERE prestador_id = :p1),
                     total_avaliacoes = (SELECT COUNT(*) FROM prestador_avaliacoes WHERE prestador_id = :p2)
                 WHERE id = :p3'
            );
            $stmt->execute([':p1' => $prestadorId, ':p2' => $prestadorId, ':p3' => $prestadorId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function listarAvaliacoes(int $prestadorId, int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.nota, a.comentario, a.criado_em, u.nome AS usuario_nome
             FROM prestador_avaliacoes a JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.prestador_id = :prestador_id ORDER BY a.criado_em DESC LIMIT :limite'
        );
        $stmt->bindValue(':prestador_id', $prestadorId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', max(1, min($limite, 50)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | SOLICITAÇÕES (pedido de passeio / diária de pet sitter)
    |--------------------------------------------------------------------------
    */

    public function criarSolicitacao(array $dados): int|false
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prestador_solicitacoes
            (prestador_id, usuario_id, pet_id, data_desejada, periodo, mensagem)
            VALUES
            (:prestador_id, :usuario_id, :pet_id, :data_desejada, :periodo, :mensagem)
        ");

        $sucesso = $stmt->execute([
            ':prestador_id' => $dados['prestador_id'],
            ':usuario_id' => $dados['usuario_id'],
            ':pet_id' => ($dados['pet_id'] ?? '') ?: null,
            ':data_desejada' => ($dados['data_desejada'] ?? '') ?: null,
            ':periodo' => ($dados['periodo'] ?? '') ?: null,
            ':mensagem' => ($dados['mensagem'] ?? '') ?: null,
        ]);

        return $sucesso ? (int) $this->pdo->lastInsertId() : false;
    }

    public function listarSolicitacoesRecebidas(int $prestadorId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                u.nome AS tutor_nome, u.telefone AS tutor_telefone, p.nome AS pet_nome
            FROM prestador_solicitacoes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.prestador_id = :prestador_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':prestador_id' => $prestadorId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca uma solicitação com dados do tutor, do prestador (usuario_id
     * dono do perfil) e do pet -- usado pra montar as notificações.
     */
    public function buscarSolicitacaoPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                pr.usuario_id AS prestador_usuario_id,
                pr.tipo AS prestador_tipo,
                u.nome AS tutor_nome,
                p.nome AS pet_nome
            FROM prestador_solicitacoes s
            INNER JOIN prestadores_servico pr ON pr.id = s.prestador_id
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN pets p ON p.id = s.pet_id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    public function listarSolicitacoesFeitas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id, s.prestador_id, s.usuario_id, s.pet_id, s.data_desejada,
                s.periodo, s.mensagem, s.status, s.criado_em, s.atualizado_em,
                pr.tipo, u.nome AS prestador_nome
            FROM prestador_solicitacoes s
            INNER JOIN prestadores_servico pr ON pr.id = s.prestador_id
            INNER JOIN usuarios u ON u.id = pr.usuario_id
            WHERE s.usuario_id = :usuario_id
            ORDER BY s.criado_em DESC
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function atualizarStatusSolicitacao(int $solicitacaoId, int $prestadorId, string $status): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE prestador_solicitacoes
            SET status = :status
            WHERE id = :id AND prestador_id = :prestador_id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':id' => $solicitacaoId,
            ':prestador_id' => $prestadorId
        ]);
    }
}
