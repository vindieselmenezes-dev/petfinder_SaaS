<?php
session_start();
unset($_SESSION['is_impersonating']);
unset($_SESSION['impersonated_by']);
unset($_SESSION['impersonated_empresa_id']);
unset($_SESSION['impersonated_empresa_nome']);

echo "<script>alert('Sessão de suporte finalizada. Retornando à central.'); window.location.href='suporte_admin.php';</script>";
exit();
