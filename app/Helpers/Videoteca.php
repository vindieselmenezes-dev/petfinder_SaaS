<?php

declare(strict_types=1);

/**
 * ==========================================================
 * ECOSISTEMPET
 * Helper: Videoteca
 *
 * Lê assets/data/videos.json (a MESMA lista usada pelo baralho da
 * home) e devolve só itens válidos, prontos para a página
 * public/videoteca.php. As categorias (abas) ficam em
 * config/videoteca.php.
 *
 * Formato de cada vídeo (igual ao do baralho da home):
 *   titulo, duracao, tipo ('youtube'|'arquivo'),
 *   youtube (link ou ID de 11 caracteres) ou arquivo (caminho do .mp4),
 *   capa (imagem local, opcional), ativo (false oculta)
 * Campos extras desta página (opcionais): categoria, descricao.
 * ==========================================================
 */

class Videoteca
{
    public const CATEGORIA_PADRAO = 'geral';

    /**
     * @return array{categorias:array<string,string>,videos:array<int,array<string,string>>}
     */
    public static function carregar(?array $config = null, ?array $lista = null): array
    {
        $config ??= require __DIR__ . '/../../config/videoteca.php';
        $lista ??= self::lerJson(__DIR__ . '/../../assets/data/videos.json');

        $categorias = [];
        foreach ((array) ($config['categorias'] ?? []) as $slug => $rotulo) {
            if (is_string($slug) && preg_match('/^[a-z0-9_-]{1,30}$/', $slug) && is_string($rotulo) && trim($rotulo) !== '') {
                $categorias[$slug] = trim($rotulo);
            }
        }

        $videos = [];
        foreach ($lista as $bruto) {
            $video = is_array($bruto) ? self::validarVideo($bruto, $categorias) : null;
            if ($video !== null) {
                $videos[] = $video;
            }
        }

        // "Geral" só existe se algum vídeo cair nela (sem categoria ou categoria desconhecida).
        if (in_array(self::CATEGORIA_PADRAO, array_column($videos, 'categoria'), true)) {
            $categorias[self::CATEGORIA_PADRAO] = $categorias[self::CATEGORIA_PADRAO] ?? 'Geral';
        }

        return ['categorias' => $categorias, 'videos' => $videos];
    }

    /** Lê a lista 'videos' do JSON. Arquivo ausente ou quebrado = lista vazia (a página não cai). */
    public static function lerJson(string $caminho): array
    {
        if (!is_file($caminho)) {
            return [];
        }
        $dados = json_decode((string) file_get_contents($caminho), true);

        return is_array($dados) && is_array($dados['videos'] ?? null) ? $dados['videos'] : [];
    }

    /**
     * @param array<string,string> $categorias
     * @return array<string,string>|null  null = item inválido ou desativado
     */
    public static function validarVideo(array $v, array $categorias): ?array
    {
        if (($v['ativo'] ?? true) === false) {
            return null;
        }

        $titulo = trim((string) ($v['titulo'] ?? ''));
        $tipo = (string) ($v['tipo'] ?? '');
        if ($titulo === '' || mb_strlen($titulo) > 120) {
            return null;
        }

        if ($tipo === 'youtube') {
            $ref = self::idYoutube((string) ($v['youtube'] ?? $v['ref'] ?? ''));
            if ($ref === '') {
                return null;
            }
        } elseif ($tipo === 'arquivo') {
            $ref = trim((string) ($v['arquivo'] ?? $v['ref'] ?? ''));
            if (!preg_match('#^(assets|uploads)/videos/[A-Za-z0-9._/-]+\.(mp4|webm)$#i', $ref) || str_contains($ref, '..')) {
                return null;
            }
        } else {
            return null;
        }

        $categoria = (string) ($v['categoria'] ?? '');
        if (!isset($categorias[$categoria])) {
            $categoria = self::CATEGORIA_PADRAO;
        }

        $capa = trim((string) ($v['capa'] ?? ''));
        if (!preg_match('#^(assets|uploads)/[A-Za-z0-9._/-]+\.(jpe?g|png|webp)$#i', $capa) || str_contains($capa, '..')) {
            $capa = ''; // só imagem local: a política de segurança do site não carrega imagens externas
        }

        $duracao = trim((string) ($v['duracao'] ?? ''));

        return [
            'chave' => substr(md5($tipo . '|' . $ref), 0, 8),
            'titulo' => $titulo,
            'descricao' => mb_substr(trim((string) ($v['descricao'] ?? '')), 0, 220),
            'categoria' => $categoria,
            'tipo' => $tipo,
            'ref' => $ref,
            'capa' => $capa,
            'duracao' => preg_match('/^\d{1,2}(:\d{2}){1,2}$/', $duracao) ? $duracao : '',
        ];
    }

    /** Aceita o link (watch, youtu.be, embed, shorts) ou o ID puro; devolve o ID de 11 caracteres ou ''. */
    public static function idYoutube(string $valor): string
    {
        $valor = trim($valor);
        if (preg_match('/(?:v=|youtu\.be\/|embed\/|shorts\/)([\w-]{11})/', $valor, $m)) {
            return $m[1];
        }

        return preg_match('/^[\w-]{11}$/', $valor) ? $valor : '';
    }
}
