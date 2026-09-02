<?php
// Garante que $pdo exista aqui dentro, independente do arquivo que incluiu este menu
// (nem todo arquivo que inclui o menu já tinha conectado ao banco antes, ex: dashboard.php).
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once __DIR__ . '/../Models/Usuario.php';
    $pdo = Database::conectar();
}
?>
<aside class="sidebar" id="sidebarMenu"> 
    <nav> 
        <ul> 
            <!-- Links Comuns para Todos os Usuários --> 
            <li><a href="dashboard.php">🏠 Dashboard</a></li> 
            <li><a href="buscar_pets.php">🔎 Buscar Pets</a></li> 
            <li><a href="carrinho.php">🛒 Carrinho</a></li>
            <li><a href="meus_pedidos.php">📦 Meus Pedidos</a></li>

            <?php 
            // CAPTURA INTELIGENTE E COMPATÍVEL DO PERFIL (Aceita os dois formatos de sessão dos projetos)
            $tipoUsuarioLogado = $_SESSION['perfil_tipo'] ?? $_SESSION['user_role'] ?? 'tutor'; 

            // ============================================================
            // 🏢 1. MENU EXCLUSIVO PARA EMPRESAS (SaaS / B2B)
            // ============================================================
            if ($tipoUsuarioLogado === 'empresa'): 
                // Busca as empresas de verdade em que este usuário está na equipe
                // (dono ou colaborador), pra permitir administrar mais de uma.
                $minhasEmpresasMenu = [];
                if (isset($_SESSION['usuario_id'])) {
                    $stmtMenuEmp = $pdo->prepare("
                        SELECT e.id, e.nome_fantasia
                        FROM empresa_equipe ee
                        JOIN empresas e ON e.id = ee.empresa_id
                        WHERE ee.usuario_id = ? AND ee.status = 'ativo'
                        ORDER BY e.nome_fantasia
                    ");
                    $stmtMenuEmp->execute([(int)$_SESSION['usuario_id']]);
                    $minhasEmpresasMenu = $stmtMenuEmp->fetchAll();
                }
                $primeiraEmpresaId = $minhasEmpresasMenu[0]['id'] ?? 0;
            ?> 
                <?php if ($primeiraEmpresaId > 0): ?>
                    <li style="background: rgba(52, 152, 219, 0.2); border-left: 4px solid #3498db;"> 
                        <a href="painel_b2b.php?empresa_id=<?php echo $primeiraEmpresaId; ?>">📋 Painel Prontuários</a> 
                    </li> 
                    <li><a href="meus_produtos.php?empresa_id=<?php echo $primeiraEmpresaId; ?>">📦 Catálogo / Vitrine</a></li> 
                <?php endif; ?>
                <li><a href="minhas_empresas.php">🏬 Minhas Empresas</a></li>
                <li><a href="cadastrar_empresa.php">➕ Cadastrar Nova Empresa</a></li>
                <li><a href="suporte.php">💬 Fale com o Suporte</a></li>

            <?php 
            // ============================================================
                       // ============================================================
            // 👑 2. MENU EXCLUSIVO PARA ADMINISTRADORES GLOBAIS (Master)
            // ============================================================
            elseif ($tipoUsuarioLogado === 'administrador'): 
            ?> 
                <li style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c;">
                    <a href="admin_usuarios.php">👥 Gestão de Usuários</a>
                </li> 
                <li><a href="suporte_admin.php">🛠️ Painel de Suporte</a></li>


            <?php 
            // ============================================================
            // 🐶 3. MENU EXCLUSIVO PARA TUTORES / CLIENTES COMUNS (B2C)
            // ============================================================
            else: 
            ?> 
                <li><a href="cadastrar_pet.php">🐶 Cadastrar Pet</a></li> 
                <li><a href="meus_pets.php">📋 Meus Pets</a></li> 
                <li><a href="meus_favoritos.php">⭐ Meus Favoritos</a></li> 
                <li><a href="meus_produtos_favoritos.php">🛍️ Produtos Favoritos</a></li> 
                <li><a href="cadastrar_empresa.php">🏢 Cadastrar Empresa</a></li> 
                <li><a href="minhas_empresas.php">🏬 Minhas Empresas</a></li> 
                <li><a href="pets_perdidos.php">🔍 Pets Perdidos</a></li> 
                <li><a href="pets_encontrados.php">❤️ Pets Encontrados</a></li> 
                <li><a href="pets_adocao.php">🏠 Para Adoção</a></li> 
                <li><a href="pets_tutor.php">🏡 Com Tutor</a></li> 
                <li><a href="pets_adotados.php">🎉 Adotados</a></li> 
                <li><a href="minhas_solicitacoes.php">🏠 Minhas Solicitações</a></li>
                <li><a href="solicitacoes_recebidas.php">📥 Solicitações Recebidas</a></li>
                <li><a href="suporte.php">💬 Fale com o Suporte</a></li>
            <?php endif; ?> 

            <!-- Links de Perfil Comuns a Todos --> 
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 0;"> 
            <li><a href="conversas.php">💬 Mensagens</a></li>
            <li><a href="meu_perfil.php">👤 Meu Perfil</a></li> 
            <li><a href="endereco.php">📍 Meu Endereço</a></li> 
            <li><a href="alterar_senha.php">🔒 Alterar Senha</a></li> 
            <li><a href="logout.php">🚪 Sair</a></li> 
        </ul> 
    </nav> 
</aside>
