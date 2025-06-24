<?php
// Ruta: WEBupita/includes/header.php
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

        <!-- Dropdown para mapas -->
        <div class="nav-dropdown" style="position: relative; display: inline-block;">
            <a href="#" class="nav-button nav-dropdown-toggle" onclick="toggleDropdown(event)">
                Mapas <i class="fas fa-chevron-down" style="margin-left: 5px; font-size: 0.8rem;"></i>
            </a>
            <div class="nav-dropdown-content" style="display: none; position: absolute; background: white; min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; overflow: hidden; top: 100%; left: 0;">
                <a href="/WEBupita/pages/mapa-interactivo.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">
                    <i class="fas fa-map" style="margin-right: 8px; color: #007bff;"></i>
                    Mapa Básico
                </a>
                <a href="/WEBupita/pages/mapa-rutas.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block;">
                    <i class="fas fa-route" style="margin-right: 8px; color: #28a745;"></i>
                    Mapa con Rutas
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="/WEBupita/Public/perfil.php" class="nav-button">Mi Perfil</a>
            <a href="/WEBupita/Public/favoritos.php" class="nav-button">
                <i class="fas fa-star" style="margin-right: 5px;"></i>Mis Favoritos
            </a>
            <a href="/WEBupita/Public/logout.php" class="nav-button">Cerrar sesión</a>
        <?php else: ?>
            <a href="/WEBupita/Public/login.php" class="nav-button">Iniciar sesión</a>
            <a href="/WEBupita/Public/registro.php" class="nav-button">Registrarse</a>
        <?php endif; ?>
    </nav>

    <script>
        // Script para el dropdown de mapas
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdown = event.target.closest('.nav-dropdown');
            const content = dropdown.querySelector('.nav-dropdown-content');
            const icon = dropdown.querySelector('.fa-chevron-down');

            // Cerrar otros dropdowns
            document.querySelectorAll('.nav-dropdown-content').forEach(other => {
                if (other !== content) {
                    other.style.display = 'none';
                    other.closest('.nav-dropdown').querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
                }
            });

            // Toggle current dropdown
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown-content').forEach(content => {
                    content.style.display = 'none';
                    content.closest('.nav-dropdown').querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
                });
            }
        });

        // Agregar Font Awesome si no está incluido
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
            document.head.appendChild(link);
        }
    </script>

    <style>
        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
        }

        .nav-dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .nav-dropdown .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        @media (max-width: 768px) {
            .nav-dropdown-content {
                position: static;
                box-shadow: none;
                background: #f8f9fa;
                margin-top: 5px;
            }
        }
    </style>