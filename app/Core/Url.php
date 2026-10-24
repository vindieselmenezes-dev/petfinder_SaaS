<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';

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
        'admin_pedidos.php' => 'admin',
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
        'confirmar_email.php' => 'conta',
        'reenviar_confirmacao.php' => 'conta',
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
        'pedir_servico.php' => 'agendamentos',
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
        'solicitacoes_compartilhadas.php' => 'agendamentos',
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

    /**
     * Envia o cabeçalho de redirecionamento (HTTP Location).
     * Quem chama continua responsável por encerrar o script (exit).
     */
    public static function redirecionar(string $destino): void
    {
        header('Location: ' . $destino);
    }

    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * Base configurável via APP_BASE_PATH (.env). Deixe em branco se o
     * projeto estiver na raiz do domínio.
     */
    public static function base(): string
    {
        $base = Env::get('APP_BASE_PATH');
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
        $origem = self::origemConfiavel();

        return $origem === null ? $caminhoRelativo : $origem . $caminhoRelativo;
    }

    /**
     * Esquema + domínio usados em links absolutos (e-mail de redefinição de
     * senha, confirmação de e-mail, notificações...).
     *
     * O cabeçalho "Host" é enviado pelo cliente e pode ser forjado: se o link
     * do e-mail fosse montado só com ele, um atacante poderia pedir a redefinição
     * de senha de uma vítima com um Host falso e fazer o e-mail apontar para o site
     * dele ("envenenamento" do link). Por isso o APP_URL do .env tem prioridade.
     * Só se ele não existir é que cai no Host da requisição, e mesmo assim só se
     * tiver formato de domínio válido.
     */
    private static function origemConfiavel(): ?string
    {
        $appUrl = Env::get('APP_URL');

        if (is_string($appUrl) && trim($appUrl) !== '') {
            $partes = parse_url(trim($appUrl));
            $esquema = strtolower((string) ($partes['scheme'] ?? ''));

            // Usa só esquema + domínio (+ porta): o caminho já vem em $caminhoRelativo
            // (via APP_BASE_PATH), então não pode ser repetido aqui.
            if (is_array($partes) && in_array($esquema, ['http', 'https'], true) && !empty($partes['host'])) {
                return $esquema . '://' . $partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '');
            }
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        $hostValido = preg_match('/^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?(:\d{1,5})?$/', $host) === 1
            || preg_match('/^\[[0-9A-Fa-f:.]+\](:\d{1,5})?$/', $host) === 1;

        if (!$hostValido) {
            return null;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        return ($https ? 'https://' : 'http://') . $host;
    }

    /**
     * Valida um destino de redirecionamento ("voltar para...") recebido do
     * navegador. Só aceita caminhos DENTRO do próprio site; devolve null se o
     * destino puder levar o visitante para outro domínio (open redirect).
     *
     * Barra, além de "http://" e "//dominio":
     *  - barra invertida: navegadores tratam "/\dominio" como "//dominio";
     *  - caracteres de controle (tab, quebra de linha...): navegadores os
     *    removem, então "/<TAB>/dominio" também vira "//dominio";
     *  - qualquer esquema ("javascript:", "data:", "ftp:"...).
     */
    public static function caminhoLocalOuNull(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || strlen($url) > 2048) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $url) === 1) {
            return null;
        }

        if (preg_match('#^[A-Za-z][A-Za-z0-9+.\-]*:#', $url) === 1 || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }
}
