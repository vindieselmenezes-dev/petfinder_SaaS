<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: AcaoPost
 * ==========================================================
 * Gera os atributos de um link <a> que, ao ser clicado, envia
 * a ação por POST com o token CSRF da sessão.
 *
 * Por que existe: ações que alteram dados (excluir, marcar como
 * recuperado...) não podem rodar por um simples link GET, senão
 * qualquer página externa conseguiria disparar a ação com um
 * clique do usuário logado. Aqui o clique monta um <form> POST
 * na hora, então funciona até dentro de outro <form> (HTML não
 * permite formulário dentro de formulário).
 *
 * Uso:
 *   <a <?= AcaoPost::atributos(Url::pagina('excluir_pet.php'), ['id' => $id], 'Excluir este pet?') ?>
 *      class="btn">Excluir</a>
 */
final class AcaoPost
{
    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * @param string               $url         Endereço que vai receber o POST.
     * @param array<string,scalar> $campos      Campos enviados junto (ex.: ['id' => 5]).
     * @param string|null          $confirmacao Texto do "Tem certeza?" (null = sem pergunta).
     *
     * @return string Atributos HTML prontos (href, role e onclick), já escapados.
     */
    public static function atributos(string $url, array $campos = [], ?string $confirmacao = null): string
    {
        $campos['csrf_token'] = Csrf::gerarToken();

        // Os HEX_* evitam que aspas/< > & dentro dos valores quebrem o atributo HTML.
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        $js = 'return (function(u,c,m){'
            . 'if(m&&!window.confirm(m)){return false;}'
            . 'var f=document.createElement("form");f.method="post";f.action=u;'
            . 'for(var k in c){if(Object.prototype.hasOwnProperty.call(c,k)){'
            . 'var i=document.createElement("input");i.type="hidden";i.name=k;i.value=c[k];f.appendChild(i);}}'
            . 'document.body.appendChild(f);f.submit();return false;'
            . '})(' . json_encode($url, $flags)
            . ',' . json_encode($campos, $flags)
            . ',' . json_encode($confirmacao, $flags) . ')';

        return 'href="#" role="button" onclick="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '"';
    }
}
