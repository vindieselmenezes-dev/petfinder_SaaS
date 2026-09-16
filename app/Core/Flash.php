<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: Flash
 * ==========================================================
 * Sistema único de mensagens de uma exibição (flash messages).
 *
 * O código antigo criava uma chave de sessão diferente para cada
 * funcionalidade ("sucesso_pet", "erro_produto", "sucesso_chamado"...),
 * e cada página precisava saber ler a sua própria chave — muita
 * duplicidade e, em vários casos, mensagens que eram gravadas mas
 * nunca chegavam a ser exibidas.
 *
 * A partir de agora, use:
 *   Flash::sucesso('Pet cadastrado com sucesso!');
 *   Flash::erro('Não foi possível salvar.');
 *
 * E, em qualquer layout, chame Flash::render() uma vez para exibir
 * (e limpar) a mensagem pendente — inclusive as chaves antigas
 * "sucesso_*" / "erro_*", para não quebrar nada que já existia.
 */
final class Flash
{
    private const CHAVE = '_flash';

    private function __construct()
    {
    }

    public static function sucesso(string $mensagem): void
    {
        self::definir('success', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::definir('danger', $mensagem);
    }

    public static function aviso(string $mensagem): void
    {
        self::definir('warning', $mensagem);
    }

    private static function definir(string $tipo, string $mensagem): void
    {
        $_SESSION[self::CHAVE] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /**
     * Devolve a mensagem pendente (nova ou de uma chave legada) e a remove
     * da sessão. Retorna null se não houver nenhuma.
     */
    public static function consumir(): ?array
    {
        if (!empty($_SESSION[self::CHAVE])) {
            $flash = $_SESSION[self::CHAVE];
            unset($_SESSION[self::CHAVE]);
            return $flash;
        }

        return self::consumirChaveLegada();
    }

    /**
     * Compatibilidade com o padrão antigo: qualquer $_SESSION['sucesso_*']
     * ou $_SESSION['erro_*'] ainda gravado por páginas não migradas.
     */
    private static function consumirChaveLegada(): ?array
    {
        foreach ($_SESSION as $chave => $valor) {
            if (str_starts_with($chave, 'sucesso_') && is_string($valor)) {
                unset($_SESSION[$chave]);
                return ['tipo' => 'success', 'mensagem' => $valor];
            }

            if (str_starts_with($chave, 'erro_') && is_string($valor)) {
                unset($_SESSION[$chave]);
                return ['tipo' => 'danger', 'mensagem' => $valor];
            }
        }

        return null;
    }

    /**
     * Imprime o HTML pronto da mensagem pendente (se houver).
     * Chame uma única vez no layout (ex: logo após abrir o <body>).
     */
    public static function render(): void
    {
        $flash = self::consumir();

        if (!$flash) {
            return;
        }

        printf(
            '<div class="alert alert-%s alert-dismissible fade show m-3" role="alert">%s'
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>',
            htmlspecialchars($flash['tipo']),
            htmlspecialchars($flash['mensagem'])
        );
    }
}
