<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Núcleo: Url
 * ==========================================================
 * Gera os links do sistema a partir de um único mapa de módulos,
 * em vez de cada página/menu ter o caminho "na unha".
 *
 * Isso resolve dois problemas de uma vez:
 *   1) As páginas de "public/" foram agrupadas por módulo
 *      (pets/, empresas/, agendamentos/, loja/, suporte/, admin/,
 *      conta/) — sem isso, todo link quebraria ao mudar de pasta.
 *   2) O projeto pode estar hospedado numa subpasta (ex:
 *      "/petfinder-SaaS"), então os links não podem ser
 *      "chutados" — usam sempre a mesma base configurável.
 *
 * Uso:
 *   Url::pagina('cadastrar_pet.php')       -> /petfinder-SaaS/public/pets/cadastrar_pet.php
 *   Url::asset('css/dashboard.css')        -> /petfinder-SaaS/assets/css/dashboard.css
 *   Url::raiz('index.html')                -> /petfinder-SaaS/index.html
 *   Url::ajax('salvar_localizacao.php')    -> /petfinder-SaaS/app/ajax/salvar_localizacao.php
 *
 * Se um arquivo mudar de módulo no futuro, só precisa atualizar
 * a linha dele aqui embaixo — nenhuma página precisa ser tocada.
 */
final class Url
{
    /**
     * Mapa "arquivo.php" => "módulo" para as páginas que vivem dentro
     * de uma subpasta de public/. Qualquer página que não estiver aqui
     * é tratada como estando direto em public/ (ex: login.php, dashboard.php).
     */
    private const MODULOS = [
        '2fa.php' => 'conta',
        'adestramento.php' => 'agendamentos',
        'adicionar_carrinho.php' => 'loja',
        'admin_usuario_detalhe.php' => 'admin',
        'admin_usuarios.php' => 'admin',
        'admin_configuracoes.php' => 'admin',
        'agendar_consulta.php' => 'agendamentos',
        'alerta_perdido.php' => 'pets',
        'alterar_destaque.php' => 'empresas',
        'alterar_senha.php' => 'conta',
        'atualizar_status_pedido.php' => 'loja',
        'avaliar_empresa.php' => 'empresas',
        'avaliar_prestador.php' => 'agendamentos',
        'banho_e_tosa.php' => 'agendamentos',
        'buscar_pets.php' => 'pets',
        'cadastrar_empresa.php' => 'empresas',
        'cadastrar_pet.php' => 'pets',
        'cadastrar_prestador.php' => 'agendamentos',
        'cadastrar_produto.php' => 'loja',
        'cadastro_empresa.php' => 'empresas',
        'carrinho.php' => 'loja',
        'chamado.php' => 'suporte',
        'checkout.php' => 'loja',
        'clinica_veterinaria.php' => 'agendamentos',
        'consultas_empresa.php' => 'agendamentos',
        'conversa.php' => 'suporte',
        'conversas.php' => 'suporte',
        'editar_empresa.php' => 'empresas',
        'editar_pet.php' => 'pets',
        'editar_produto.php' => 'loja',
        'empresa.php' => 'empresas',
        'empresas.php' => 'empresas',
        'encerrar_suporte.php' => 'suporte',
        'endereco.php' => 'conta',
        'esqueci_senha.php' => 'conta',
        'excluir_empresa.php' => 'empresas',
        'excluir_imagem_empresa.php' => 'empresas',
        'excluir_imagem_pet.php' => 'pets',
        'excluir_imagem_produto.php' => 'loja',
        'excluir_pet.php' => 'pets',
        'excluir_produto.php' => 'loja',
        'favoritar.php' => 'pets',
        'favoritar_produto.php' => 'loja',
        'historico_pet.php' => 'pets',
        'historico_prontuario.php' => 'agendamentos',
        'hotelzinho.php' => 'agendamentos',
        'identidade_pet.php' => 'pets',
        'marcar_pet_recuperado.php' => 'pets',
        'meu_perfil.php' => 'conta',
        'meus_favoritos.php' => 'pets',
        'meus_pedidos.php' => 'loja',
        'meus_pets.php' => 'pets',
        'meus_produtos.php' => 'loja',
        'meus_produtos_favoritos.php' => 'loja',
        'minhas_consultas.php' => 'agendamentos',
        'minhas_empresas.php' => 'empresas',
        'minhas_solicitacoes.php' => 'pets',
        'minhas_solicitacoes_empresa.php' => 'agendamentos',
        'minhas_solicitacoes_prestador.php' => 'agendamentos',
        'notificacoes.php' => 'conta',
        'novo_chamado.php' => 'suporte',
        'novo_item_catalogo.php' => 'loja',
        'novo_prontuario.php' => 'agendamentos',
        'ofertas.php' => 'loja',
        'onboarding.php' => 'conta',
        'painel_b2b.php' => 'empresas',
        'admin_campanhas.php' => 'parceiros',
        'admin_parceiros.php' => 'parceiros',
        'apoios_campanha.php' => 'parceiros',
        'cadastrar_parceiro.php' => 'parceiros',
        'campanha.php' => 'parceiros',
        'campanha_acoes.php' => 'parceiros',
        'campanha_form.php' => 'parceiros',
        'painel_parceiro.php' => 'parceiros',
        'parceiro.php' => 'parceiros',
        'parceiros.php' => 'parceiros',
        'pedido_confirmado.php' => 'loja',
        'pet.php' => 'pets',
        'pets_adocao.php' => 'pets',
        'pets_adotados.php' => 'pets',
        'pets_encontrados.php' => 'pets',
        'pets_perdidos.php' => 'pets',
        'pets_tutor.php' => 'pets',
        'planos.php' => 'empresas',
        'prestador.php' => 'agendamentos',
        'prestadores.php' => 'agendamentos',
        'processa_alerta.php' => 'pets',
        'processa_chamado.php' => 'suporte',
        'processa_checkout.php' => 'loja',
        'processa_impersonate.php' => 'empresas',
        'processa_item_catalogo.php' => 'loja',
        'processa_prontuario.php' => 'agendamentos',
        'processa_retificacao.php' => 'agendamentos',
        'produto.php' => 'loja',
        'produtos.php' => 'loja',
        'redefinir_senha.php' => 'conta',
        'remover_carrinho.php' => 'loja',
        'retificar_prontuario.php' => 'agendamentos',
        'seguranca.php' => 'conta',
        'simular_assinatura.php' => 'empresas',
        'simular_faturamento.php' => 'empresas',
        'solicitacoes_empresa.php' => 'agendamentos',
        'solicitacoes_prestador.php' => 'agendamentos',
        'solicitacoes_recebidas.php' => 'pets',
        'solicitar_adocao.php' => 'pets',
        'solicitar_servico_empresa.php' => 'agendamentos',
        'solicitar_servico_prestador.php' => 'agendamentos',
        'suporte.php' => 'suporte',
        'suporte_admin.php' => 'suporte',
        'vendas_empresa.php' => 'loja',
        'vitrine.php' => 'loja',
    ];

    private function __construct()
    {
    }

    /**
     * Base configurável via APP_BASE_PATH (.env). Deixe em branco se o
     * projeto estiver na raiz do domínio.
     */
    public static function base(): string
    {
        $base = getenv('APP_BASE_PATH');
        return $base !== false ? rtrim($base, '/') : '';
    }

    /**
     * Link para uma página de public/ (já resolve o módulo automaticamente).
     * Ex: Url::pagina('cadastrar_pet.php') ou Url::pagina('painel_b2b.php') . '?empresa_id=5'
     */
    public static function pagina(string $arquivo): string
    {
        $modulo = self::MODULOS[$arquivo] ?? null;
        $prefixo = $modulo ? "{$modulo}/" : '';

        return self::base() . '/public/' . $prefixo . $arquivo;
    }

    /**
     * Link para um arquivo estático em /assets (css, js, img, uploads).
     */
    public static function asset(string $caminho): string
    {
        return self::base() . '/assets/' . ltrim($caminho, '/');
    }

    /**
     * Link para um arquivo endpoint de /app/ajax.
     */
    public static function ajax(string $arquivo): string
    {
        return self::base() . '/app/ajax/' . ltrim($arquivo, '/');
    }

    /**
     * Link para um arquivo dentro de /uploads (fotos enviadas por usuários:
     * pets, empresas, produtos, prestadores...).
     */
    public static function upload(string $caminho): string
    {
        return self::base() . '/uploads/' . ltrim($caminho, '/');
    }

    /**
     * Link para um arquivo na raiz do projeto (index.html, manifest.json, sw.js).
     */
    public static function raiz(string $arquivo): string
    {
        return self::base() . '/' . ltrim($arquivo, '/');
    }

    /**
     * Versão absoluta (com esquema e domínio) de um link já gerado por
     * pagina()/raiz()/asset(). Necessária para compartilhamento em redes
     * sociais: WhatsApp, Facebook e X exigem uma URL completa, não um
     * caminho relativo como "/public/parceiros/campanha.php?id=5".
     *
     * Se HTTP_HOST não estiver disponível (ex: rodando via CLI), devolve
     * o caminho relativo mesmo, sem quebrar.
     */
    public static function absoluta(string $caminhoRelativo): string
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return $caminhoRelativo;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        $esquema = $https ? 'https://' : 'http://';

        return $esquema . $_SERVER['HTTP_HOST'] . $caminhoRelativo;
    }
}
