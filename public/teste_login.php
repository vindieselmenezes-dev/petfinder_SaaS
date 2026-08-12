<?php
require_once __DIR__ . '/../config/database.php';

echo "<h3>🔎 Verificando o nome da conexão:</h3>";

if (isset($pdo)) {
    echo "<p style='color:green;'>✅ O nome correto da variável é: <b>\$pdo</b></p>";
} elseif (isset($conn)) {
    echo "<p style='color:green;'>✅ O nome correto da variável é: <b>\$conn</b></p>";
} elseif (isset($db)) {
    echo "<p style='color:green;'>✅ O nome correto da variável é: <b>\$db</b></p>";
} elseif (isset($conexao)) {
    echo "<p style='color:green;'>✅ O nome correto da variável é: <b>\$conexao</b></p>";
} else {
    echo "<p style='color:red;'>❌ Nenhuma variável de conexão padrão foi encontrada. Vamos precisar olhar o arquivo config/database.php.</p>";
}
?>
