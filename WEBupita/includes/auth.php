<?php
// Ruta: WEBupita/includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

// Función para requerir autenticación
function requererAutenticacion($redirectUrl = '/WEBupita/Public/login.php') {
    if (!estaAutenticado()) {
        // Guardar la URL actual para redireccionar después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

        // Redireccionar al login
        header("Location: $redirectUrl");
        exit;
    }
}

// Función para obtener datos del usuario actual
function obtenerUsuarioActual() {
    if (!estaAutenticado()) {
        return null;
    }

    try {
        require_once __DIR__ . '/conexion.php';

        $stmt = $pdo->prepare("SELECT id, nombre, email, fecha_registro FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error obteniendo usuario actual: ' . $e->getMessage());
        return null;
    }
}

// Función para cerrar sesión
function cerrarSesion($redirectUrl = '/WEBupita/Public/index.php') {
    // Limpiar todas las variables de sesión
    $_SESSION = array();

    // Eliminar la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destruir la sesión
    session_destroy();

    // Redireccionar
    header("Location: $redirectUrl");
    exit;
}

// Función para verificar permisos (para futuras extensiones)
function tienePermiso($permiso) {
    if (!estaAutenticado()) {
        return false;
    }

    // Por ahora todos los usuarios autenticados tienen los mismos permisos
    // Esto se puede extender en el futuro
    return true;
}

// Función para regenerar ID de sesión (seguridad)
function regenerarSesion() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// Verificar autenticación automáticamente si se llama directamente
if (!defined('SKIP_AUTH_CHECK')) {
    requererAutenticacion();
}
?>