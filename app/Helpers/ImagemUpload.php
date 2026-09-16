<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: ImagemUpload
 * ==========================================================
 * Salva o arquivo enviado (igual sempre foi feito, via
 * move_uploaded_file) e, se a imagem for maior do que precisa
 * pra ser exibida no site, redimensiona e comprime ela no
 * próprio lugar — mesmo nome, mesma extensão, mesma URL.
 *
 * Se por qualquer motivo o redimensionamento não for possível
 * (GD ausente, formato não suportado, arquivo corrompido), o
 * upload original é mantido intacto — nunca falha o cadastro
 * por causa disso.
 */
final class ImagemUpload
{
    /**
     * Lado máximo (largura ou altura, o que for maior) que uma imagem
     * de exibição no site realmente precisa. 1600px cobre até telas
     * grandes com folga; a maioria das fotos de celular vem muito
     * maior que isso.
     */
    private const LADO_MAXIMO_PADRAO = 1600;

    /**
     * Qualidade usada ao regravar JPEG/WEBP (0-100). 82 é o ponto onde
     * a perda de qualidade já não é perceptível a olho nu, mas o
     * arquivo fica bem mais leve.
     */
    private const QUALIDADE_PADRAO = 82;

    private function __construct()
    {
        // Classe estática — não deve ser instanciada
    }

    /**
     * Move o arquivo enviado pra pasta de destino (exatamente como
     * move_uploaded_file() sempre fez) e, em seguida, redimensiona
     * se necessário.
     *
     * @param string $tmpNameUpload  $_FILES[...]['tmp_name']
     * @param string $destinoAbsoluto Caminho final completo (com nome e extensão)
     */
    public static function salvar(
        string $tmpNameUpload,
        string $destinoAbsoluto,
        int $ladoMaximo = self::LADO_MAXIMO_PADRAO,
        int $qualidade = self::QUALIDADE_PADRAO
    ): bool {
        if (!move_uploaded_file($tmpNameUpload, $destinoAbsoluto)) {
            return false;
        }

        self::redimensionarSeNecessario($destinoAbsoluto, $ladoMaximo, $qualidade);

        return true;
    }

    /**
     * Redimensiona a imagem já salva em $caminho, só se ela for maior
     * do que $ladoMaximo. Não faz nada (silenciosamente) se: GD não
     * estiver disponível, o formato não for suportado, ou o arquivo
     * já for pequeno o bastante — em todos esses casos o arquivo
     * original enviado continua exatamente como estava.
     */
    private static function redimensionarSeNecessario(string $caminho, int $ladoMaximo, int $qualidade): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $info = @getimagesize($caminho);
        if ($info === false) {
            return;
        }

        [$largura, $altura, $tipo] = $info;

        if ($largura <= 0 || $altura <= 0) {
            return;
        }

        // Já é pequena o bastante — não mexe (evita reprocessar/perder
        // qualidade à toa em imagens que já vieram otimizadas).
        if ($largura <= $ladoMaximo && $altura <= $ladoMaximo) {
            return;
        }

        $origem = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($caminho),
            IMAGETYPE_PNG => @imagecreatefrompng($caminho),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($caminho) : false,
            default => false,
        };

        if (!$origem) {
            return;
        }

        $escala = min($ladoMaximo / $largura, $ladoMaximo / $altura);
        $novaLargura = max(1, (int) round($largura * $escala));
        $novaAltura = max(1, (int) round($altura * $escala));

        $redimensionada = imagecreatetruecolor($novaLargura, $novaAltura);

        // PNG pode ter transparência — preserva o canal alpha ao invés
        // de preencher com fundo preto/branco por engano.
        if ($tipo === IMAGETYPE_PNG) {
            imagealphablending($redimensionada, false);
            imagesavealpha($redimensionada, true);
            $transparente = imagecolorallocatealpha($redimensionada, 0, 0, 0, 127);
            imagefilledrectangle($redimensionada, 0, 0, $novaLargura, $novaAltura, $transparente);
        }

        imagecopyresampled(
            $redimensionada,
            $origem,
            0,
            0,
            0,
            0,
            $novaLargura,
            $novaAltura,
            $largura,
            $altura
        );

        switch ($tipo) {
            case IMAGETYPE_JPEG:
                imagejpeg($redimensionada, $caminho, $qualidade);
                break;
            case IMAGETYPE_PNG:
                // Escala de compressão do PNG é 0 (sem compressão) a 9
                // (máxima) — não é "qualidade" como no JPEG, PNG é sem
                // perdas. 6 é o equilíbrio padrão usado pela própria libpng.
                imagepng($redimensionada, $caminho, 6);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($redimensionada, $caminho, $qualidade);
                }
                break;
        }

        imagedestroy($origem);
        imagedestroy($redimensionada);
    }
}
