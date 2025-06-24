<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPIITA - Instituto Politécnico Nacional</title>
    <link rel="stylesheet" href="/WEBupita/css/styles.css">
</head>
<body>
    <div class="main-container">
        <!-- Encabezado principal -->
        <header class="main-header">
            <h1 class="main-title">UPIITA</h1>
            <p class="subtitle">"Unidad Profesional Interdisciplinaria en Ingeniería y Tecnologías Avanzadas"</p>
        </header>

        <!-- Barra de navegación principal -->
        <nav class="primary-nav">
            <a href="/WEBupita/Public/index.php" class="nav-button">Inicio</a>
            <a href="/WEBupita/pages/conocenos.php" class="nav-button">Conócenos</a>
            <a href="/WEBupita/pages/oferta-educativa.php" class="nav-button">Oferta Educativa</a>
            <a href="/WEBupita/pages/comunidad.php" class="nav-button">Comunidad</a>
            <a href="/WEBupita/pages/red-genero.php" class="nav-button">Red de Género</a>
            <a href="/WEBupita/pages/redes-sociales.php" class="nav-button">Redes Sociales</a>
            <a href="/WEBupita/pages/mapa-interactivo.php" class="nav-button">Mapa Interactivo</a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="/WEBupita/Public/perfil.php" class="nav-button">Mi Perfil</a>
                <a href="/WEBupita/Public/favoritos.php" class="nav-button">Mis Favoritos</a>
                <a href="/WEBupita/Public/logout.php" class="nav-button">Cerrar sesión</a>
            <?php else: ?>
                <a href="/WEBupita/Public/login.php" class="nav-button">Iniciar sesión</a>
                <a href="/WEBupita/Public/registro.php" class="nav-button">Registrarse</a>
            <?php endif; ?>
        </nav>
