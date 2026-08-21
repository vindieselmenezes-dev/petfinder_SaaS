<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"])) { 
    header("Location: login.php"); 
    exit; 
} 

require_once "../app/Controllers/PetController.php"; 
require_once "../app/Models/Favorito.php"; 

$controller = new PetController(); 
$favoritoModel = new Favorito(); 

// Lista apenas os pets do usuário logado
$pets = $controller->listarPorUsuario((int) $_SESSION["usuario_id"]); 

require_once "../app/Includes/header.php"; 
require_once "../app/Includes/menu.php"; 

// --- Configurações visuais de status e espécie ---
function statusBadge(string $status): string {
    $mapa = [
        'Perdido'    => ['bg' => '#fde8e8', 'cor' => '#c0392b', 'icone' => '🔴'],
        'Encontrado' => ['bg' => '#fef5e7', 'cor' => '#b7791f', 'icone' => '🟡'],
        'Com Tutor'  => ['bg' => '#e8f8f0', 'cor' => '#27ae60', 'icone' => '🟢'],
        'Adotado'    => ['bg' => '#e8f0fe', 'cor' => '#2c5cc5', 'icone' => '🔵'],
        'Disponível' => ['bg' => '#f0e8fe', 'cor' => '#8e44ad', 'icone' => '🟣'],
    ];
    $config = $mapa[$status] ?? ['bg' => '#f1f2f6', 'cor' => '#576574', 'icone' => '⚪'];
    return sprintf(
        '<span style="display:inline-flex; align-items:center; gap:5px; background:%s; color:%s; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:bold; font-family:sans-serif; white-space:nowrap;">%s %s</span>',
        $config['bg'], $config['cor'], $config['icone'], htmlspecialchars($status)
    );
}

function especieIcone(?string $especie): string {
    $icones = [
        'Cachorro' => '🐕', 'Gato' => '🐈', 'Ave' => '🐦', 'Peixe' => '🐠',
        'Coelho' => '🐇', 'Hamster' => '🐹', 'Réptil' => '🦎', 'Cavalo' => '🐴',
    ];
    return $icones[$especie ?? ''] ?? '🐾';
}
?> 

<main class="container" style="margin-top: 100px !important; margin-left: 240px !important; padding: 20px !important; display: block !important;"> 
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;"> 
        
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
            <div>
                <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🐶 Meus Pets</h1> 
                <p style="color: #7f8c8d; margin: 0; font-family: sans-serif;">Aqui estão os pets que você cadastrou na plataforma.</p> 
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="background: #eef2f7; color: #34495e; padding: 8px 16px; border-radius: 20px; font-family: sans-serif; font-weight: bold; font-size: 14px;">
                    <?= count($pets); ?> pet<?= count($pets) !== 1 ? 's' : ''; ?>
                </span>
                <a href="cadastrar_pet.php" style="display: inline-flex; align-items: center; gap: 6px; background: #2ecc71; color: white; text-decoration: none; padding: 9px 18px; border-radius: 6px; font-weight: bold; font-size: 14px; font-family: sans-serif;">
                    ➕ Novo Pet
                </a>
            </div>
        </div>

        <style>
            .tabela-pets tbody tr { transition: background-color 0.15s ease; }
            .tabela-pets tbody tr:hover { background-color: #f8fafc !important; }
            .btn-acao { transition: opacity 0.15s ease, transform 0.1s ease; }
            .btn-acao:hover { opacity: 0.85; transform: translateY(-1px); }
        </style>

        <table class="tabela-pets" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-family: sans-serif;"> 
            <thead> 
                <tr style="background: #34495e; color: white; text-align: left;"> 
                    <th style="padding: 15px; text-align: center; width: 100px;">Foto</th> 
                    <th style="padding: 15px;">Nome</th> 
                    <th style="padding: 15px;">Espécie / Raça</th> 
                    <th style="padding: 15px;">Cidade</th> 
                    <th style="padding: 15px;">Status</th> 
                    <th style="padding: 15px;">Cadastro</th> 
                    <th style="padding: 15px; text-align: center;">Ações</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (count($pets) > 0): ?> 
                    <?php foreach ($pets as $pet): ?> 
                        <tr style="border-bottom: 1px solid #eaeaea;"> 
                            <td style="padding: 15px; text-align: center;"> 
                                <?php 
                                // Se a imagem existir, mostra a foto; senão, mostra o ícone da espécie
                                if (!empty($pet['foto']) && file_exists("../uploads/pets/" . $pet['foto'])) {
                                    $caminhoFoto = "../uploads/pets/" . $pet['foto'];
                                    echo '<img src="'.htmlspecialchars($caminhoFoto).'" width="55" height="55" style="object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" alt="Pet">';
                                } else {
                                    echo '<div style="width: 55px; height: 55px; background: #e2e8f0; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; font-size: 24px;">'
                                        . especieIcone($pet['especie_nome'] ?? null)
                                        . '</div>';
                                }
                                ?> 
                            </td> 
                            <td style="padding: 15px; font-weight: bold; color: #333;"> 
                                <?= htmlspecialchars($pet["nome"] ?? 'Sem Nome'); ?> 
                            </td> 
                            <td style="padding: 15px; color: #555;"> 
                                <?= especieIcone($pet['especie_nome'] ?? null); ?> <?= htmlspecialchars($pet["especie_nome"] ?? 'Não informada'); ?> 
                                <br><small style="color: #999;"><?= htmlspecialchars($pet["raca_nome"] ?? 'Mestiço / Vira-lata'); ?></small> 
                            </td> 
                            <td style="padding: 15px; color: #777;"> 
                                📍 <?= htmlspecialchars($pet["cidade"] ?? 'Não informada'); ?> 
                            </td> 
                            <td style="padding: 15px;"> 
                                <?= statusBadge($pet["status"] ?? 'Não informado'); ?> 
                            </td> 
                            <td style="padding: 15px; color: #777;"> 
                                <?= !empty($pet["criado_em"]) ? date("d/m/Y", strtotime($pet["criado_em"])) : 'Não informado'; ?> 
                            </td> 
                            <td style="padding: 15px; text-align: center; white-space: nowrap;"> 
                                <a href="editar_pet.php?id=<?= (int) $pet['id']; ?>" class="btn-acao" style="display: inline-block; background: #3498db; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">✏️ Editar</a> 
                                <?php if (($pet['status'] ?? '') !== 'Perdido'): ?>
                                    <a href="alerta_perdido.php?pet_id=<?= (int) $pet['id']; ?>" class="btn-acao" style="display: inline-block; background: #e67e22; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-left: 5px;">🚨 Perdido</a>
                                <?php else: ?>
                                    <a href="marcar_pet_recuperado.php?id=<?= (int) $pet['id']; ?>" class="btn-acao" onclick="return confirm('Confirma que <?= htmlspecialchars(addslashes($pet['nome'] ?? 'o pet')); ?> foi recuperado?');" style="display: inline-block; background: #27ae60; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-left: 5px;">✅ Recuperado</a>
                                <?php endif; ?>
                                <a href="excluir_pet.php?id=<?= (int) $pet['id']; ?>" class="btn-acao" onclick="return confirm('Tem certeza que deseja excluir este pet?');" style="display: inline-block; background: #e74c3c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif; margin-left: 5px;">❌ Excluir</a> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                <?php else: ?> 
                    <tr> 
                        <td colspan="7" style="padding: 60px 30px; text-align: center;"> 
                            <div style="font-size: 48px; margin-bottom: 10px;">🐾</div>
                            <p style="color: #7f8c8d; font-size: 16px; margin: 0 0 15px 0;">Você ainda não cadastrou nenhum pet.</p>
                            <a href="cadastrar_pet.php" style="display: inline-block; background: #2ecc71; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 14px;">➕ Cadastrar meu primeiro pet</a>
                        </td> 
                    </tr> 
                <?php endif; ?> 
            </tbody> 
        </table> 
    </div> 
</main> 

<?php require_once "../app/Includes/footer.php"; ?>
