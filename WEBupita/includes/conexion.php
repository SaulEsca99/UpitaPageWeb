<?php
// Ruta: WEBupita/includes/conexion.php

$host = 'localhost';
$db   = 'upiita';
$user = 'root';
$pass = 'tired2019'; // XAMPP por defecto no tiene contraseña para root
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Configurar timezone
    $pdo->exec("SET time_zone = '+00:00'");

} catch (PDOException $e) {
    // Log del error real
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());

    // Mostrar error específico para XAMPP
    if (php_sapi_name() === 'cli') {
        die("Error de conexión a la base de datos: " . $e->getMessage() . "\n");
    } else {
        // Mensaje de error más específico para XAMPP
        $error_msg = "Error de conexión a la base de datos.<br>";
        $error_msg .= "Verifica que:<br>";
        $error_msg .= "1. XAMPP esté ejecutándose (Apache + MySQL)<br>";
        $error_msg .= "2. La base de datos 'upiita' exista<br>";
        $error_msg .= "3. Los datos SQL hayan sido importados<br>";
        $error_msg .= "<br>Error técnico: " . $e->getMessage();
        die($error_msg);
    }
}

// Función auxiliar para ejecutar consultas con manejo de errores
function ejecutarConsulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Error en consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        throw $e;
    }
}

// Función para verificar si la base de datos está configurada correctamente
function verificarBaseDatos($pdo) {
    try {
        // Verificar que existan las tablas principales
        $tablas = ['Edificios', 'Aulas', 'PuntosConexion', 'Rutas', 'usuarios', 'RutasFavoritas'];

        foreach ($tablas as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("La tabla '$tabla' no existe. Por favor ejecuta el script: WEBupita/db/UPIITA_Rutas.sql");
            }
        }

        // Verificar que la vista existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'vista_lugares'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("La vista 'vista_lugares' no existe. Por favor ejecuta el script: WEBupita/db/UPIITA_Rutas.sql");
        }

        return true;

    } catch (Exception $e) {
        error_log('Error verificando base de datos: ' . $e->getMessage());
        return false;
    }
}

// Ejecutar verificación solo si no estamos en una API
if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
    if (!verificarBaseDatos($pdo)) {
        if (php_sapi_name() !== 'cli') {
            $error_msg = "<h3 style='color: red;'>La base de datos no está configurada correctamente.</h3>";
            $error_msg .= "<p><strong>Pasos a seguir:</strong></p>";
            $error_msg .= "<ol>";
            $error_msg .= "<li>Abrir phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
            $error_msg .= "<li>Crear base de datos llamada: <strong>upiita</strong></li>";
            $error_msg .= "<li>Importar el archivo: <strong>WEBupita/db/UPIITA_Rutas.sql</strong></li>";
            $error_msg .= "<li>Recargar esta página</li>";
            $error_msg .= "</ol>";
            die($error_msg);
        }
    }
}
?>