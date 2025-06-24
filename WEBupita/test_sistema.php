<?php
// Ruta: WEBupita/test_sistema.php
// Script para verificar que todo el sistema funciona correctamente

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Prueba del Sistema UPIITA</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .ok { color: green; } .error { color: red; } .warning { color: orange; }</style>";

// 1. Verificar conexión a base de datos
echo "<h2>1. Verificando conexión a base de datos...</h2>";
try {
    require_once 'includes/conexion.php';
    echo "<p class='ok'>✓ Conexión a base de datos exitosa</p>";

    // Verificar tablas
    $tablas = ['Edificios', 'Aulas', 'PuntosConexion', 'Rutas', 'usuarios', 'RutasFavoritas'];
    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
        $count = $stmt->fetchColumn();
        echo "<p class='ok'>✓ Tabla '$tabla': $count registros</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
}

// 2. Verificar clases
echo "<h2>2. Verificando clases...</h2>";
try {
    require_once 'includes/Dijkstra.php';
    $dijkstra = new Dijkstra($pdo);
    echo "<p class='ok'>✓ Clase Dijkstra cargada correctamente</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error cargando Dijkstra: " . $e->getMessage() . "</p>";
}

// 3. Verificar APIs
echo "<h2>3. Verificando APIs...</h2>";
$apis = [
    'api/get_edificios.php',
    'api/get_aulas.php',
    'api/buscar_lugares.php'
];

foreach ($apis as $api) {
    if (file_exists($api)) {
        echo "<p class='ok'>✓ API '$api' existe</p>";
    } else {
        echo "<p class='error'>✗ API '$api' no encontrada</p>";
    }
}

// 4. Verificar lugares disponibles
echo "<h2>4. Verificando lugares disponibles...</h2>";
try {
    $lugares = $dijkstra->obtenerLugaresDisponibles();
    echo "<p class='ok'>✓ Se encontraron " . count($lugares) . " lugares disponibles</p>";

    if (count($lugares) > 0) {
        echo "<p>Primeros 5 lugares:</p><ul>";
        for ($i = 0; $i < min(5, count($lugares)); $i++) {
            $lugar = $lugares[$i];
            echo "<li>{$lugar['codigo']} - {$lugar['nombre']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error obteniendo lugares: " . $e->getMessage() . "</p>";
}

// 5. Verificar cálculo de rutas
echo "<h2>5. Verificando cálculo de rutas...</h2>";
try {
    // Buscar dos aulas para probar
    $stmt = $pdo->query("SELECT idAula FROM Aulas WHERE coordenada_x IS NOT NULL LIMIT 2");
    $aulas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($aulas) >= 2) {
        $resultado = $dijkstra->calcularRutaMasCorta('aula', $aulas[0], 'aula', $aulas[1]);

        if ($resultado['encontrada']) {
            echo "<p class='ok'>✓ Cálculo de ruta exitoso</p>";
            echo "<p>Distancia: {$resultado['distancia_total']} metros</p>";
            echo "<p>Pasos: {$resultado['numero_pasos']}</p>";
        } else {
            echo "<p class='warning'>⚠ No se encontró ruta: {$resultado['mensaje']}</p>";
        }
    } else {
        echo "<p class='warning'>⚠ No hay suficientes aulas con coordenadas para probar</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error calculando ruta: " . $e->getMessage() . "</p>";
}

// 6. Verificar estructura de archivos
echo "<h2>6. Verificando estructura de archivos...</h2>";
$archivos_requeridos = [
    'includes/header.php',
    'includes/footer.php',
    'includes/auth.php',
    'includes/conexion.php',
    'includes/Dijkstra.php',
    'css/styles.css',
    'pages/mapa-interactivo.php',
    'pages/mapa-rutas.php',
    'Public/index.php',
    'Public/login.php',
    'Public/registro.php',
    'Public/favoritos.php'
];

foreach ($archivos_requeridos as $archivo) {
    if (file_exists($archivo)) {
        echo "<p class='ok'>✓ $archivo</p>";
    } else {
        echo "<p class='error'>✗ $archivo (faltante)</p>";
    }
}

// 7. Verificar sesiones
echo "<h2>7. Verificando configuración de sesiones...</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p class='ok'>✓ Sesiones funcionando correctamente</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// 8. Recomendaciones
echo "<h2>8. Recomendaciones de configuración</h2>";
$recomendaciones = [
    'post_max_size' => '16M',
    'upload_max_filesize' => '16M',
    'max_execution_time' => '60',
    'memory_limit' => '256M'
];

foreach ($recomendaciones as $config => $valor_recomendado) {
    $valor_actual = ini_get($config);
    if ($valor_actual == $valor_recomendado ||
        (is_numeric($valor_actual) && is_numeric($valor_recomendado) && $valor_actual >= $valor_recomendado)) {
        echo "<p class='ok'>✓ $config: $valor_actual</p>";
    } else {
        echo "<p class='warning'>⚠ $config: $valor_actual (recomendado: $valor_recomendado)</p>";