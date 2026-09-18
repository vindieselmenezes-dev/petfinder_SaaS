<?php
// $pdo já está sempre disponível a partir do bootstrap único da aplicação.
$pdo ??= Database::conectar();

// Nome do arquivo da página atual (ex: "meus_pets.php"), usado pra saber
// qual grupo deve começar aberto e qual link deve ficar marcado como ativo.
$paginaAtualMenu = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

/**
 * Retorna 'ativo' se $arquivo for a página atual (usado na classe do <li>).
 */
function menuClasseAtiva(string $arquivo, string $paginaAtual): string
{
    return $arquivo === $paginaAtual ? ' class="ativo"' : '';
}

/**
 * Um grupo de itens que abre/fecha (usa <details>/<summary> nativo do
 * HTML, então funciona mesmo sem JavaScript). Fica aberto por padrão se
 * a página atual estiver dentro dele, pra nunca esconder onde o usuário
 * está.
 *
 * @param array<int, array{0:string,1:string}> $itens Lista de [arquivo, rótulo]
 */
function menuGrupo(string $titulo, string $icone, array $itens, string $paginaAtual): void
{
    $contemAtual = false;
    foreach ($itens as $item) {
        if ($item[0] === $paginaAtual) {
            $contemAtual = true;
            break;
        }
    }
    ?>
    <details class="sidebar-grupo" <?= $contemAtual ? 'open' : '' ?>>
        <summary><span aria-hidden="true"><?= $icone ?></span> <?= htmlspecialchars($titulo) ?><span class="sidebar-grupo-chevron" aria-hidden="true">›</span></summary>
        <ul class="sidebar-subitens">
            <?php foreach ($itens as $item): ?>
                <li<?= menuClasseAtiva($item[0], $paginaAtual) ?>><a href="<?= Url::pagina($item[0]) ?>"><?= $item[1] ?></a></li>
            <?php endforeach; ?>
        </ul>
    </details>
    <?php
}
?>
<aside class="sidebar" id="sidebarMenu">
    <button type="button" class="sidebar-fechar" id="sidebarFechar" aria-label="Fechar menu">✕</button>
    <nav>
        <ul>
            <!-- Links Comuns para Todos os Usuários -->
            <li<?= menuClasseAtiva('dashboard.php', $paginaAtualMenu) ?>><a href="<?= Url::pagina('dashboard.php') ?>">🏠 Dashboard</a></li>
            <li<?= menuClasseAtiva('carrinho.php', $paginaAtualMenu) ?>><a href="<?= Url::pagina('carrinho.php') ?>">🛒 Carrinho</a></li>
            <li<?= menuClasseAtiva('meus_pedidos.php', $paginaAtualMenu) ?>><a href="<?= Url::pagina('meus_pedidos.php') ?>">📦 Meus Pedidos</a></li>
        </ul>

        <?php
        // 🐾 Módulo de Prestadores de Serviço - comum a todos os perfis
        menuGrupo('Serviços', '🐕', [
            ['buscar_pets.php', '🔎 Buscar Pets'],
            ['prestadores.php', 'Passeadores, Pet Sitters, Táxi Pet e Adestradores'],
            ['cadastrar_prestador.php', '➕ Seja Passeador/Pet Sitter/Táxi Pet/Adestrador'],
            ['minhas_solicitacoes_prestador.php', '📋 Minhas Solicitações de Passeio'],
            ['hotelzinho.php', '🏨 Hotelzinho'],
            ['adestramento.php', '🎓 Adestramento'],
            ['banho_e_tosa.php', '✂️ Banho e Tosa'],
            ['clinica_veterinaria.php', '🩺 Clínica Veterinária'],
            ['minhas_consultas.php', '🩺 Minhas Consultas Veterinárias'],
            ['minhas_solicitacoes_empresa.php', '📥 Minhas Solicitações de Serviço'],
        ], $paginaAtualMenu);

        // 🤝 Área de Parceiros (ONGs e empresas apoiadoras) - todos os perfis
        menuGrupo('Parceiros', '🤝', [
            ['parceiros.php', '🌍 ONGs e Empresas Parceiras'],
            ['painel_parceiro.php', '📣 Minhas Campanhas e Eventos'],
            ['cadastrar_parceiro.php', '➕ Quero ser Parceiro'],
        ], $paginaAtualMenu);

        // ============================================================
        // 🏢 1. MENU EXCLUSIVO PARA EMPRESAS (SaaS / B2B)
        // ============================================================
        if (Auth::ehEmpresa()):
            // Busca as empresas de verdade em que este usuário está na equipe
            // (dono ou colaborador), pra permitir administrar mais de uma.
            $minhasEmpresasMenu = [];
            if (Auth::check()) {
                $stmtMenuEmp = $pdo->prepare("
                    SELECT e.id, e.nome_fantasia, e.categoria_id
                    FROM empresa_equipe ee
                    JOIN empresas e ON e.id = ee.empresa_id
                    WHERE ee.usuario_id = ? AND ee.status = 'ativo'
                    ORDER BY e.nome_fantasia
                ");
                $stmtMenuEmp->execute([Auth::id()]);
                $minhasEmpresasMenu = $stmtMenuEmp->fetchAll();
            }
            $primeiraEmpresaId = $minhasEmpresasMenu[0]['id'] ?? 0;
            $primeiraEmpresaCategoria = (int) ($minhasEmpresasMenu[0]['categoria_id'] ?? 0);
            ?>
            <ul>
                <?php if ($primeiraEmpresaId > 0): ?>
                    <li style="background: rgba(52, 152, 219, 0.2); border-left: 4px solid #3498db;">
                        <a href="<?= Url::pagina('painel_b2b.php') ?>?empresa_id=<?php echo $primeiraEmpresaId; ?>">📋 Painel Prontuários</a>
                    </li>
                    <li><a href="<?= Url::pagina('meus_produtos.php') ?>?empresa_id=<?php echo $primeiraEmpresaId; ?>">📦 Catálogo / Vitrine</a></li>
                    <?php if (in_array($primeiraEmpresaCategoria, [2, 3], true)): ?>
                        <li><a href="<?= Url::pagina('consultas_empresa.php') ?>?empresa_id=<?php echo $primeiraEmpresaId; ?>">🩺 Consultas Recebidas</a></li>
                    <?php elseif (in_array($primeiraEmpresaCategoria, [4, 5, 6], true)): ?>
                        <li><a href="<?= Url::pagina('solicitacoes_empresa.php') ?>?empresa_id=<?php echo $primeiraEmpresaId; ?>">📥 Pedidos de Serviço Recebidos</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <?php
            menuGrupo('Empresa', '🏢', [
                ['minhas_empresas.php', '🏬 Minhas Empresas'],
                ['cadastrar_empresa.php', '➕ Cadastrar Nova Empresa'],
                ['planos.php', '📈 Planos para Empresas'],
            ], $paginaAtualMenu);

        // ============================================================
        // 👑 2. MENU EXCLUSIVO PARA ADMINISTRADORES GLOBAIS (Master)
        // ============================================================
        elseif (Auth::ehAdministrador()):
            ?>
            <ul>
                <li style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c;">
                    <a href="<?= Url::pagina('admin_usuarios.php') ?>">👥 Gestão de Usuários</a>
                </li>
                <li><a href="<?= Url::pagina('suporte_admin.php') ?>">🛠️ Painel de Suporte</a></li>
                <li><a href="<?= Url::pagina('admin_parceiros.php') ?>">🤝 Moderação de Parceiros</a></li>
                <li><a href="<?= Url::pagina('admin_campanhas.php') ?>">🛡️ Moderação de Campanhas</a></li>
                <li><a href="<?= Url::pagina('admin_configuracoes.php') ?>">⚙️ Configurações Gerais</a></li>
                <li><a href="<?= Url::pagina('admin_contatos.php') ?>">📬 Mensagens de Contato</a></li>
            </ul>
            <?php
        // ============================================================
        // 🐶 3. MENU EXCLUSIVO PARA TUTORES / CLIENTES COMUNS (B2C)
        // ============================================================
        else:
            menuGrupo('Pets', '🐶', [
                ['cadastrar_pet.php', 'Cadastrar Pet'],
                ['meus_pets.php', '📋 Meus Pets'],
                ['meus_favoritos.php', '⭐ Meus Favoritos'],
                ['meus_produtos_favoritos.php', '🛍️ Produtos Favoritos'],
                ['pets_perdidos.php', '🔍 Pets Perdidos'],
                ['pets_encontrados.php', '❤️ Pets Encontrados'],
                ['pets_adocao.php', '🏠 Para Adoção'],
                ['pets_tutor.php', '🏡 Com Tutor'],
                ['pets_adotados.php', '🎉 Adotados'],
                ['minhas_solicitacoes.php', '🏠 Minhas Solicitações'],
                ['solicitacoes_recebidas.php', '📥 Solicitações Recebidas'],
            ], $paginaAtualMenu);

            menuGrupo('Empresa', '🏢', [
                ['cadastrar_empresa.php', '➕ Cadastrar Empresa'],
                ['minhas_empresas.php', '🏬 Minhas Empresas'],
                ['planos.php', '📈 Planos para Empresas'],
            ], $paginaAtualMenu);
        endif;

        // Links de Perfil Comuns a Todos
        menuGrupo('Conta', '👤', [
            ['conversas.php', '💬 Mensagens'],
            ['meu_perfil.php', 'Meu Perfil'],
            ['endereco.php', '📍 Meu Endereço'],
            ['alterar_senha.php', '🔒 Alterar Senha'],
            ['suporte.php', '💬 Fale com o Suporte'],
            ['logout.php', '🚪 Sair'],
        ], $paginaAtualMenu);
        ?>
    </nav>
</aside>
