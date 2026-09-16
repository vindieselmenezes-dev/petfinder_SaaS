<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: Compartilhamento
 * ==========================================================
 * Monta os links de "compartilhar" (WhatsApp, Facebook, X/Twitter,
 * Telegram) e o botão de copiar link, usados nos cards e na página
 * de campanha/evento/doação.
 *
 * Usa só os endpoints públicos de intent de cada rede — não precisa
 * de API key nem de app registrado em lugar nenhum.
 */

class Compartilhamento
{
    public static function linkWhatsapp(string $texto, string $url): string
    {
        return 'https://wa.me/?text=' . rawurlencode($texto . ' ' . $url);
    }

    public static function linkFacebook(string $url): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);
    }

    public static function linkTwitter(string $texto, string $url): string
    {
        return 'https://twitter.com/intent/tweet?text=' . rawurlencode($texto) . '&url=' . rawurlencode($url);
    }

    public static function linkTelegram(string $texto, string $url): string
    {
        return 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($texto);
    }

    /**
     * Renderiza o bloco completo de compartilhamento: botões de rede +
     * campo com o link e botão de copiar. Usado na página de detalhe da
     * campanha, onde há espaço de sobra.
     *
     * $idCampo precisa ser único na página quando houver mais de um bloco
     * (não é o caso hoje, mas evita colisão de id no futuro).
     */
    public static function renderizarBloco(string $titulo, string $urlAbsoluta, string $idCampo = 'linkCompartilhar'): string
    {
        $texto = 'Ajude: ' . $titulo;

        $whatsapp = htmlspecialchars(self::linkWhatsapp($texto, $urlAbsoluta));
        $facebook = htmlspecialchars(self::linkFacebook($urlAbsoluta));
        $twitter = htmlspecialchars(self::linkTwitter($texto, $urlAbsoluta));
        $telegram = htmlspecialchars(self::linkTelegram($texto, $urlAbsoluta));
        $urlEscapada = htmlspecialchars($urlAbsoluta);
        $idCampoEscapado = htmlspecialchars($idCampo);

        return <<<HTML
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{$whatsapp}" target="_blank" rel="noopener"
                    class="btn btn-outline-success btn-sm" title="Compartilhar no WhatsApp">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
                <a href="{$facebook}" target="_blank" rel="noopener"
                    class="btn btn-outline-primary btn-sm" title="Compartilhar no Facebook">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
                <a href="{$twitter}" target="_blank" rel="noopener"
                    class="btn btn-outline-dark btn-sm" title="Compartilhar no X">
                    <i class="bi bi-twitter-x"></i> X
                </a>
                <a href="{$telegram}" target="_blank" rel="noopener"
                    class="btn btn-outline-info btn-sm" title="Compartilhar no Telegram">
                    <i class="bi bi-telegram"></i> Telegram
                </a>
            </div>
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control" id="{$idCampoEscapado}" readonly value="{$urlEscapada}">
                <button class="btn btn-outline-secondary" type="button"
                    data-copiar-alvo="{$idCampoEscapado}" title="Copiar link">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            HTML;
    }

    /**
     * Versão compacta: um botão "Compartilhar" que abre um menu dropdown
     * do Bootstrap com as mesmas opções. Usada nos cards das listagens,
     * onde não há espaço para o bloco completo.
     *
     * $idMenu precisa ser único por card (ex: "compartilhar-42").
     */
    public static function renderizarDropdown(string $titulo, string $urlAbsoluta, string $idMenu): string
    {
        $texto = 'Ajude: ' . $titulo;

        $whatsapp = htmlspecialchars(self::linkWhatsapp($texto, $urlAbsoluta));
        $facebook = htmlspecialchars(self::linkFacebook($urlAbsoluta));
        $twitter = htmlspecialchars(self::linkTwitter($texto, $urlAbsoluta));
        $telegram = htmlspecialchars(self::linkTelegram($texto, $urlAbsoluta));
        $urlEscapada = htmlspecialchars($urlAbsoluta);
        $idBotao = htmlspecialchars($idMenu);

        return <<<HTML
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                    id="{$idBotao}" data-bs-toggle="dropdown" aria-expanded="false" title="Compartilhar">
                    <i class="bi bi-share-fill"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{$idBotao}">
                    <li>
                        <a class="dropdown-item" href="{$whatsapp}" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp text-success"></i> WhatsApp
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{$facebook}" target="_blank" rel="noopener">
                            <i class="bi bi-facebook text-primary"></i> Facebook
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{$twitter}" target="_blank" rel="noopener">
                            <i class="bi bi-twitter-x"></i> X
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{$telegram}" target="_blank" rel="noopener">
                            <i class="bi bi-telegram text-info"></i> Telegram
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-copiar-texto="{$urlEscapada}">
                            <i class="bi bi-clipboard"></i> Copiar link
                        </button>
                    </li>
                </ul>
            </div>
            HTML;
    }
}
