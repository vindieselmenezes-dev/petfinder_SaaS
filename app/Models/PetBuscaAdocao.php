<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: PetBuscaAdocao
 * Busca pública de pets para adoção.
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PetBuscaAdocao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Busca pública de pets para adoção (sem exigir login)
     * Filtra por texto, cidade, espécie, raça, sexo, cor, castração, idade, peso, altura e status.
     *
     * Chaves aceitas em $criterios (todas opcionais):
     *   busca, cidade, especieId, racaId, sexo, cor, castrado (0, 1 ou -1 =
     *   qualquer), idadeMin, idadeMax, pesoMin, pesoMax, alturaMin, alturaMax
     *   e status.
     *   Além delas: ordem, pagina e porPagina (0 = sem paginação).

     */
    public function buscarAdocaoPublico(array $criterios = []): array
    {
        $ordem = (string) ($criterios['ordem'] ?? 'criado_em');
        $pagina = (int) ($criterios['pagina'] ?? 1);
        $porPagina = (int) ($criterios['porPagina'] ?? 0);

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

        [$sqlFiltros, $params] = $this->construirFiltrosAdocao($criterios);
        $sql .= $sqlFiltros;

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

        if ($porPagina > 0) {
            $pagina = max(1, $pagina);
            $offset = ($pagina - 1) * $porPagina;
            // Assim como em listarPorStatus(), $porPagina/$offset já são
            // inteiros controlados pelo código, então concatenar direto
            // no SQL é seguro (evita o problema conhecido de bind de
            // LIMIT/OFFSET com PDO em modo de prepare nativo).
            $sql .= " LIMIT {$porPagina} OFFSET {$offset} ";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Conta quantos pets batem com os mesmos filtros de buscarAdocaoPublico().
     * Usado para montar a paginação (total de páginas) sem precisar
     * trazer todos os registros pra memória.
     *
     * Chaves aceitas em $criterios (todas opcionais):
     *   busca, cidade, especieId, racaId, sexo, cor, castrado (0, 1 ou -1 =
     *   qualquer), idadeMin, idadeMax, pesoMin, pesoMax, alturaMin, alturaMax
     *   e status.
     */
    public function contarAdocaoPublico(array $criterios = []): int
    {
        $sql = "
            SELECT COUNT(*)
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

        [$sqlFiltros, $params] = $this->construirFiltrosAdocao($criterios);
        $sql .= $sqlFiltros;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Monta a parte "AND ..." dos filtros usados tanto por
     * buscarAdocaoPublico() quanto por contarAdocaoPublico(), pra não
     * duplicar a mesma lógica em dois lugares (e correr o risco dos
     * dois filtros ficarem diferentes com o tempo).
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function construirFiltrosAdocao(array $criterios): array
    {
        $busca = (string) ($criterios['busca'] ?? '');
        $cidade = (string) ($criterios['cidade'] ?? '');
        $especieId = (int) ($criterios['especieId'] ?? 0);
        $racaId = (int) ($criterios['racaId'] ?? 0);
        $sexo = (string) ($criterios['sexo'] ?? '');
        $cor = (string) ($criterios['cor'] ?? '');
        $castrado = (int) ($criterios['castrado'] ?? -1);
        $idadeMin = (int) ($criterios['idadeMin'] ?? 0);
        $idadeMax = (int) ($criterios['idadeMax'] ?? 0);
        $pesoMin = (float) ($criterios['pesoMin'] ?? 0.0);
        $pesoMax = (float) ($criterios['pesoMax'] ?? 0.0);
        $alturaMin = (float) ($criterios['alturaMin'] ?? 0.0);
        $alturaMax = (float) ($criterios['alturaMax'] ?? 0.0);
        $status = (string) ($criterios['status'] ?? 'Para Adoção');

        $termoBusca = "%{$busca}%";

        // Cada filtro: [está ativo?, trecho do SQL, parâmetros]
        $filtros = [
            [$busca !== '', " AND (
                LOWER(p.nome) LIKE LOWER(:busca1)
                OR LOWER(e.nome) LIKE LOWER(:busca2)
                OR LOWER(r.nome) LIKE LOWER(:busca3)
                OR LOWER(p.cor) LIKE LOWER(:busca4)
                OR LOWER(p.microchip) LIKE LOWER(:busca5)
                OR LOWER(p.observacoes) LIKE LOWER(:busca6)
            ) ", [
                ':busca1' => $termoBusca,
                ':busca2' => $termoBusca,
                ':busca3' => $termoBusca,
                ':busca4' => $termoBusca,
                ':busca5' => $termoBusca,
                ':busca6' => $termoBusca,
            ]],
            [$cidade !== '', " AND LOWER(end.cidade) = LOWER(:cidade) ", [':cidade' => $cidade]],
            [$status !== '' && $status !== 'Todos', " AND p.status = :status ", [':status' => $status]],
            [$especieId > 0, " AND p.especie_id = :especie_id ", [':especie_id' => $especieId]],
            [$racaId > 0, " AND p.raca_id = :raca_id ", [':raca_id' => $racaId]],
            [$sexo !== '', " AND p.sexo = :sexo ", [':sexo' => $sexo]],
            [$cor !== '', " AND LOWER(p.cor) LIKE LOWER(:cor) ", [':cor' => "%{$cor}%"]],
            [$castrado === 0 || $castrado === 1, " AND p.castrado = :castrado ", [':castrado' => $castrado]],
            [$idadeMin > 0, " AND COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0) >= :idade_min ", [':idade_min' => $idadeMin]],
            [$idadeMax > 0, " AND COALESCE(TIMESTAMPDIFF(YEAR, p.data_nascimento, CURDATE()), 0) <= :idade_max ", [':idade_max' => $idadeMax]],
            [$pesoMin > 0, " AND p.peso >= :peso_min ", [':peso_min' => $pesoMin]],
            [$pesoMax > 0, " AND p.peso <= :peso_max ", [':peso_max' => $pesoMax]],
            [$alturaMin > 0, " AND p.altura >= :altura_min ", [':altura_min' => $alturaMin]],
            [$alturaMax > 0, " AND p.altura <= :altura_max ", [':altura_max' => $alturaMax]],
        ];

        $sql = '';
        $params = [];

        foreach ($filtros as [$ativo, $trechoSql, $parametros]) {
            if ($ativo) {
                $sql .= $trechoSql;
                $params += $parametros;
            }
        }

        return [$sql, $params];
    }
}
