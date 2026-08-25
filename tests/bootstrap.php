<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/*
|--------------------------------------------------------------------------
| Marcador usado em todos os dados criados pelos testes, pra deixar claro
| (caso algo não seja limpo corretamente) que aquele registro é de teste
|--------------------------------------------------------------------------
*/

define('TESTE_MARCADOR', 'TESTE_AUTOMATIZADO_PETFINDER');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Pet.php';
require_once __DIR__ . '/../app/Models/Favorito.php';
require_once __DIR__ . '/../app/Models/Endereco.php';
require_once __DIR__ . '/../app/Models/SolicitacaoAdocao.php';
require_once __DIR__ . '/../app/Models/Conversa.php';
require_once __DIR__ . '/../app/Models/Mensagem.php';
require_once __DIR__ . '/../app/Models/Suporte.php';
require_once __DIR__ . '/../app/Models/ResetSenha.php';
require_once __DIR__ . '/../app/Controllers/PetController.php';
require_once __DIR__ . '/../app/Controllers/SolicitacaoAdocaoController.php';
require_once __DIR__ . '/../app/Controllers/ConversaController.php';
require_once __DIR__ . '/../app/Controllers/SuporteController.php';
require_once __DIR__ . '/TestKit.php';
