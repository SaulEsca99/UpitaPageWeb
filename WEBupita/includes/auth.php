<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al login si no está autenticado
    header("Location: /WEBupita/Public/login.php");
    exit;
}
?>
