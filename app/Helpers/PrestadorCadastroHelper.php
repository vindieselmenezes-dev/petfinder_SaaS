<?php

declare(strict_types=1);

/**
 * Apoio ao cadastro de prestadores: upload da foto e limpeza do CPF.
 * Separado do PrestadorController para manter cada classe enxuta.
 */
final class PrestadorCadastroHelper
{
    private const TAMANHO_MAX_IMAGEM = 5 * 1024 * 1024;

    private const MIME_PERMITIDOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * Verifica se um arquivo enviado é realmente uma imagem válida
     */
    private static function arquivoEhImagemValida(string $caminhoTemporario, int $tamanho): bool
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
     * Processa o upload da foto do prestador.
     * Retorna o nome do novo arquivo, ou null se não houver upload válido.
     */
    public static function processarFoto(array $arquivo): ?string
    {
        if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $permitidas = ["jpg", "jpeg", "png", "webp"];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $permitidas, true)) {
            return null;
        }

        if (!self::arquivoEhImagemValida($arquivo['tmp_name'], (int) ($arquivo['size'] ?? 0))) {
            return null;
        }

        $diretorio = dirname(__DIR__, 2) . "/uploads/prestadores";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        $novoNome = uniqid("prestador_", true) . "." . $extensao;
        $destino = $diretorio . "/" . $novoNome;

        if (!ImagemUpload::salvar($arquivo['tmp_name'], $destino)) {
            return null;
        }

        return $novoNome;
    }

    /**
     * Limpa um CPF, deixando só dígitos
     */
    public static function limparCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }
}
