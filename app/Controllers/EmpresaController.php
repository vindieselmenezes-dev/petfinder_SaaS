<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: EmpresaController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Empresa.php';

class EmpresaController
{
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private Empresa $empresa;

    public function __construct()
    {
        $this->empresa = new Empresa();
    }

    /**
     * Verifica se um CNPJ já está cadastrado (usado antes de criar a conta,
     * no cadastro combinado usuário+empresa)
     */
    public function cnpjExiste(string $cnpj): bool
    {
        $cnpjLimpo = $this->limparCnpj($cnpj);

        if ($cnpjLimpo === '') {
            return false;
        }

        return $this->empresa->cnpjExiste($cnpjLimpo);
    }

    public function listarCategorias(): array
    {
        return $this->empresa->listarCategorias();
    }

    public function listarDestaques(int $limite = 6): array
    {
        return $this->empresa->listarDestaques($limite);
    }

    public function avaliar(int $empresaId, int $usuarioId, int $nota): bool
    {
        return $this->empresa->avaliar($empresaId, $usuarioId, $nota);
    }

    public function listarAvaliacoes(int $empresaId): array
    {
        return $this->empresa->listarAvaliacoes($empresaId);
    }

    /**
     * Verifica se um arquivo enviado é realmente uma imagem válida
     */
    private function arquivoEhImagemValida(string $caminhoTemporario, int $tamanho): bool
    {
        if ($tamanho <= 0 || $tamanho > self::TAMANHO_MAX_IMAGEM) {
            return false;
        }

        $infoImagem = @getimagesize($caminhoTemporario);

        if ($infoImagem === false) {
            return false;
        }

        return in_array($infoImagem['mime'] ?? '', self::MIME_PERMITIDOS, true);
    }

    /**
     * Processa o upload de uma única imagem (logo ou capa).
     * Retorna o nome do novo arquivo, ou null se não houver upload válido.
     */
    public function processarImagemUnica(array $arquivo): ?string
    {
        if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $permitidas, true)) {
            return null;
        }

        if (!$this->arquivoEhImagemValida($arquivo['tmp_name'], (int) ($arquivo['size'] ?? 0))) {
            return null;
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/empresas";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        $novoNome = uniqid("empresa_", true) . "." . $extensao;
        $destino = $diretorio . "/" . $novoNome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return null;
        }

        return $novoNome;
    }

    /**
     * Processa upload de múltiplas imagens para a galeria
     */
    public function processarGaleria(array $arquivos): array
    {
        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $imagensSalvas = [];

        if (empty($arquivos['name']) || !is_array($arquivos['name'])) {
            return [];
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/empresas";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        foreach ($arquivos['name'] as $index => $nomeArquivo) {

            if (($arquivos['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

            if (!in_array($extensao, $permitidas, true)) {
                continue;
            }

            $tmpName = $arquivos['tmp_name'][$index] ?? '';
            $tamanho = (int) ($arquivos['size'][$index] ?? 0);

            if (!$this->arquivoEhImagemValida($tmpName, $tamanho)) {
                continue;
            }

            $novoNome = uniqid("empresa_galeria_", true) . "." . $extensao;
            $destino = $diretorio . "/" . $novoNome;

            if (move_uploaded_file($tmpName, $destino)) {
                $imagensSalvas[] = $novoNome;
            }

        }

        return $imagensSalvas;
    }

    /**
     * Valida e formata um CNPJ (só números, 14 dígitos)
     */
    private function limparCnpj(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }

    /**
     * Cadastra uma nova empresa
     */
    public function cadastrar(array $dados): int|false
    {
        if (empty($dados['nome_fantasia'])) {
            return false;
        }

        if (empty($dados['categoria_id'])) {
            return false;
        }

        $cnpjLimpo = $this->limparCnpj($dados['cnpj'] ?? '');

        if ($cnpjLimpo !== '' && strlen($cnpjLimpo) !== 14) {
            return false;
        }

        if ($cnpjLimpo !== '' && $this->empresa->cnpjExiste($cnpjLimpo)) {
            return false;
        }

        $dados['cnpj'] = $cnpjLimpo !== '' ? $cnpjLimpo : null;
        $dados['razao_social'] = $dados['razao_social'] ?? '';
        $dados['descricao'] = $dados['descricao'] ?? '';
        $dados['telefone'] = $dados['telefone'] ?? '';
        $dados['whatsapp'] = $dados['whatsapp'] ?? '';
        $dados['email'] = $dados['email'] ?? '';
        $dados['site'] = $dados['site'] ?? '';
        $dados['logo'] = $dados['logo'] ?? null;
        $dados['capa'] = $dados['capa'] ?? null;
        $dados['endereco'] = $dados['endereco'] ?? '';
        $dados['numero'] = $dados['numero'] ?? '';
        $dados['complemento'] = $dados['complemento'] ?? '';
        $dados['bairro'] = $dados['bairro'] ?? '';
        $dados['cidade'] = $dados['cidade'] ?? '';
        $dados['estado'] = $dados['estado'] ?? '';
        $dados['cep'] = $dados['cep'] ?? '';

        $novoId = $this->empresa->cadastrar($dados);

        // Toda empresa nova já entra na equipe como 'proprietario' de quem a cadastrou.
        // Isso é o que permite um usuário administrar várias empresas com papéis
        // diferentes em cada uma (ver empresa_equipe).
        if ($novoId !== false && !empty($dados['usuario_id'])) {
            $pdo = Database::conectar();
            $stmt = $pdo->prepare("
                INSERT INTO empresa_equipe (empresa_id, usuario_id, papel, status)
                VALUES (?, ?, 'proprietario', 'ativo')
            ");
            $stmt->execute([$novoId, (int) $dados['usuario_id']]);
        }

        return $novoId;
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->empresa->buscarPorId($id);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->empresa->listarPorUsuario($usuarioId);
    }

    /**
     * Atualiza uma empresa existente
     */
    public function atualizar(int $id, array $dados): bool
    {
        if (empty($dados['nome_fantasia'])) {
            return false;
        }

        if (empty($dados['categoria_id'])) {
            return false;
        }

        $cnpjLimpo = $this->limparCnpj($dados['cnpj'] ?? '');

        if ($cnpjLimpo !== '' && strlen($cnpjLimpo) !== 14) {
            return false;
        }

        $dados['cnpj'] = $cnpjLimpo !== '' ? $cnpjLimpo : null;
        $dados['razao_social'] = $dados['razao_social'] ?? '';
        $dados['descricao'] = $dados['descricao'] ?? '';
        $dados['telefone'] = $dados['telefone'] ?? '';
        $dados['whatsapp'] = $dados['whatsapp'] ?? '';
        $dados['email'] = $dados['email'] ?? '';
        $dados['site'] = $dados['site'] ?? '';
        $dados['logo'] = $dados['logo'] ?? null;
        $dados['capa'] = $dados['capa'] ?? null;
        $dados['endereco'] = $dados['endereco'] ?? '';
        $dados['numero'] = $dados['numero'] ?? '';
        $dados['complemento'] = $dados['complemento'] ?? '';
        $dados['bairro'] = $dados['bairro'] ?? '';
        $dados['cidade'] = $dados['cidade'] ?? '';
        $dados['estado'] = $dados['estado'] ?? '';
        $dados['cep'] = $dados['cep'] ?? '';

        return $this->empresa->atualizar($id, $dados);
    }

    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->empresa->excluir($id, $usuarioId);
    }

    /**
     * Lista empresas ativas para o diretório público
     */
    public function listarAtivas(int $categoriaId = 0, string $cidade = '', string $busca = ''): array
    {
        return $this->empresa->listarAtivas($categoriaId, trim($cidade), trim($busca));
    }

    public function contarEmpresas(): int
    {
        return $this->empresa->contarEmpresas();
    }

    /**
     * Salva os horários de funcionamento a partir dos dados do formulário
     */
    public function salvarHorarios(int $empresaId, array $horariosPost): bool
    {
        $diasValidos = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        $horarios = [];

        foreach ($diasValidos as $dia) {

            $fechado = isset($horariosPost[$dia]['fechado']);
            $abertura = trim($horariosPost[$dia]['abertura'] ?? '');
            $fechamento = trim($horariosPost[$dia]['fechamento'] ?? '');

            $horarios[] = [
                'dia_semana' => $dia,
                'abertura' => $abertura !== '' ? $abertura : null,
                'fechamento' => $fechamento !== '' ? $fechamento : null,
                'fechado' => $fechado
            ];

        }

        return $this->empresa->salvarHorarios($empresaId, $horarios);
    }

    public function buscarHorarios(int $empresaId): array
    {
        return $this->empresa->buscarHorarios($empresaId);
    }

    public function salvarGaleria(int $empresaId, array $imagens): bool
    {
        return $this->empresa->salvarGaleria($empresaId, $imagens);
    }

    public function buscarGaleria(int $empresaId): array
    {
        return $this->empresa->buscarGaleria($empresaId);
    }

    public function excluirImagemGaleria(int $imagemId, int $empresaId): bool
    {
        return $this->empresa->excluirImagemGaleria($imagemId, $empresaId);
    }
}
