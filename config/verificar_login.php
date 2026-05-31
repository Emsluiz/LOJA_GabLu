<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não existir a credencial de segurança na sessão, barra o acesso
if (!isset($_SESSION["usuario_id"])) {
    // Expulsa o invasor direto para a tela de login na raiz
    header("Location: ../index.php");
    exit;
}
?>
