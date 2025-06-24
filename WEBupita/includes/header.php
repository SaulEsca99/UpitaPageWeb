<?php
// Ruta: WEBupita/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir constante para rutas
define('BASE_URL', '/TecnologiasParaElDesarrolloDeAplicacionesWeb/SchoolPathFinder/WEBupita');

// Definir constante para evitar check automático de auth
define('SKIP_AUTH_CHECK', true);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPIITA - Instituto Politécnico Nacional</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <a href="<?= BASE_URL ?>/Public/index.php" class="nav-button">Inicio</a>
        <a href="<?= BASE_URL ?>/pages/conocenos.php" class="nav-button">Conócenos</a>
        <a href="<?= BASE_URL ?>/pages/oferta-educativa.php" class="nav-button">Oferta Educativa</a>
        <a href="<?= BASE_URL ?>/pages/comunidad.php" class="nav-button">Comunidad</a>
        <a href="<?= BASE_URL ?>/pages/red-genero.php" class="nav-button">Red de Género</a>
        <a href="<?= BASE_URL ?>/pages/redes-sociales.php" class="nav-button">Redes Sociales</a>

        <!-- Dropdown para mapas -->
        <div class="nav-dropdown" style="position: relative; display: inline-block;">
            <a href="#" class="nav-button nav-dropdown-toggle" onclick="toggleDropdown(event)">
                Mapas <i class="fas fa-chevron-down" style="margin-left: 5px; font-size: 0.8rem;"></i>
            </a>
            <div class="nav-dropdown-content" style="display: none; position: absolute; background: white; min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; overflow: hidden; top: 100%; left: 0;">
                <a href="<?= BASE_URL ?>/pages/mapa-interactivo.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">
                    <i class="fas fa-map" style="margin-right: 8px; color: #007bff;"></i>
                    Mapa Básico
                </a>
                <a href="<?= BASE_URL ?>/pages/mapa-rutas.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block;">
                    <i class="fas fa-route" style="margin-right: 8px; color: #28a745;"></i>
                    Mapa con Rutas
                </a>
            </div>
        </div>

        <!-- Opciones de usuario -->
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="nav-dropdown" style="position: relative; display: inline-block;">
                <a href="#" class="nav-button nav-dropdown-toggle" onclick="toggleDropdown(event)">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> <i class="fas fa-chevron-down" style="margin-left: 5px; font-size: 0.8rem;"></i>
                </a>
                <div class="nav-dropdown-content" style="display: none; position: absolute; background: white; min-width: 180px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; overflow: hidden; top: 100%; right: 0;">
                    <a href="<?= BASE_URL ?>/Public/perfil.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">
                        <i class="fas fa-user-circle" style="margin-right: 8px; color: #007bff;"></i>
                        Mi Perfil
                    </a>
                    <a href="<?= BASE_URL ?>/Public/favoritos.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block; border-bottom: 1px solid #eee;">
                        <i class="fas fa-star" style="margin-right: 8px; color: #ffc107;"></i>
                        Mis Favoritos
                    </a>
                    <a href="<?= BASE_URL ?>/Public/logout.php" style="color: #333; padding: 12px 16px; text-decoration: none; display: block;">
                        <i class="fas fa-sign-out-alt" style="margin-right: 8px; color: #dc3545;"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/Public/login.php" class="nav-button">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
            <a href="<?= BASE_URL ?>/Public/registro.php" class="nav-button">
                <i class="fas fa-user-plus"></i> Registrarse
            </a>
        <?php endif; ?>
    </nav>

    <script>
        // Script para el dropdown de navegación
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdown = event.target.closest('.nav-dropdown');
            const content = dropdown.querySelector('.nav-dropdown-content');
            const icon = dropdown.querySelector('.fa-chevron-down');

            // Cerrar otros dropdowns
            document.querySelectorAll('.nav-dropdown-content').forEach(other => {
                if (other !== content) {
                    other.style.display = 'none';
                    const otherIcon = other.closest('.nav-dropdown').querySelector('.fa-chevron-down');
                    if (otherIcon) {
                        otherIcon.style.transform = 'rotate(0deg)';
                    }
                }
            });

            // Toggle current dropdown
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown-content').forEach(content => {
                    content.style.display = 'none';
                    const icon = content.closest('.nav-dropdown').querySelector('.fa-chevron-down');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });

        // Marcar enlace activo
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navButtons = document.querySelectorAll('.nav-button:not(.nav-dropdown-toggle)');

            navButtons.forEach(button => {
                if (button.getAttribute('href') === currentPath) {
                    button.classList.add('active');
                }
            });
        });

        // Actualizar rutas de APIs para JavaScript
        window.API_BASE_URL = '<?= BASE_URL ?>';
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

        .nav-button.active {
            background-color: #002244;
        }

        .nav-button:hover {
            background-color: #0055a5;
        }

        @media (max-width: 768px) {
            .nav-dropdown-content {
                position: static;
                box-shadow: none;
                background: #f8f9fa;
                margin-top: 5px;
            }

            .primary-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-dropdown {
                width: 100%;
            }

            .nav-button {
                width: 100%;
                text-align: center;
                margin-bottom: 5px;
            }
        }
    </style>