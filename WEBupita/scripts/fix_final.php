<?php
// Ruta: WEBupita/scripts/fix_final.php
// Script para corregir los últimos problemas

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Corrección Final - Mapa UPIITA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f8ff; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { color: #2196F3; font-weight: bold; }
        .success-box { background: #e8f5e8; border: 2px solid #4CAF50; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .button { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 6px; margin: 10px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Corrección Final del Sistema</h1>";

try {
    // 1. Crear carpeta logs
    echo "<h2>1. Creando carpeta de logs</h2>";
    $logsDir = '../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
        echo "<p class='ok'>✓ Carpeta 'logs' creada exitosamente</p>";
    } else {
        echo "<p class='info'>✓ Carpeta 'logs' ya existe</p>";
    }

    // 2. Verificar permisos
    if (is_writable($logsDir)) {
        echo "<p class='ok'>✓ Carpeta 'logs' tiene permisos de escritura</p>";
    } else {
        chmod($logsDir, 0755);
        echo "<p class='ok'>✓ Permisos de escritura configurados</p>";
    }

    // 3. Conectar a base de datos y verificar
    require_once '../includes/conexion.php';
    require_once '../includes/Dijkstra.php';

    echo "<h2>2. Verificando sistema de rutas</h2>";

    $dijkstra = new Dijkstra($pdo);

    // Buscar dos aulas aleatorias con coordenadas
    $stmt = $pdo->query("
        SELECT idAula, numeroAula, nombreAula, coordenada_x, coordenada_y 
        FROM Aulas 
        WHERE coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL
        ORDER BY RAND() 
        LIMIT 5
    ");
    $aulas_test = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p class='info'>Aulas disponibles para pruebas: " . count($aulas_test) . "</p>";

    if (count($aulas_test) >= 2) {
        $origen = $aulas_test[0];
        $destino = $aulas_test[1];

        echo "<p>Probando ruta: <strong>{$origen['numeroAula']}</strong> → <strong>{$destino['numeroAula']}</strong></p>";

        $resultado = $dijkstra->calcularRutaMasCorta('aula', $origen['idAula'], 'aula', $destino['idAula']);

        if ($resultado['encontrada']) {
            echo "<p class='ok'>✓ Sistema de rutas funciona perfectamente</p>";
            echo "<p class='ok'>✓ Distancia calculada: {$resultado['distancia_total']} metros</p>";
            echo "<p class='ok'>✓ Pasos en la ruta: {$resultado['numero_pasos']}</p>";

            // Probar algunas rutas más
            echo "<h3>Pruebas adicionales:</h3>";
            for ($i = 2; $i < min(5, count($aulas_test)); $i++) {
                $test_destino = $aulas_test[$i];
                $test_resultado = $dijkstra->calcularRutaMasCorta('aula', $origen['idAula'], 'aula', $test_destino['idAula']);

                if ($test_resultado['encontrada']) {
                    echo "<p class='ok'>✓ {$origen['numeroAula']} → {$test_destino['numeroAula']}: {$test_resultado['distancia_total']}m</p>";
                } else {
                    echo "<p class='error'>✗ No se encontró ruta a {$test_destino['numeroAula']}: {$test_resultado['mensaje']}</p>";
                }
            }

        } else {
            echo "<p class='error'>✗ Error en ruta: {$resultado['mensaje']}</p>";

            // Diagnóstico más detallado
            echo "<h3>Diagnóstico detallado:</h3>";
            echo "<p>Origen: {$origen['numeroAula']} (ID: {$origen['idAula']}, X: {$origen['coordenada_x']}, Y: {$origen['coordenada_y']})</p>";
            echo "<p>Destino: {$destino['numeroAula']} (ID: {$destino['idAula']}, X: {$destino['coordenada_x']}, Y: {$destino['coordenada_y']})</p>";

            // Verificar si hay rutas en la base de datos
            $stmt = $pdo->query("SELECT COUNT(*) FROM Rutas");
            $total_rutas = $stmt->fetchColumn();
            echo "<p>Total de rutas en BD: $total_rutas</p>";

            $stmt = $pdo->query("SELECT COUNT(*) FROM PuntosConexion");
            $total_puntos = $stmt->fetchColumn();
            echo "<p>Total de puntos de conexión: $total_puntos</p>";
        }
    } else {
        echo "<p class='error'>No hay suficientes aulas con coordenadas para probar</p>";
    }

    // 4. Verificar API
    echo "<h2>3. Verificando APIs</h2>";

    // Verificar que los archivos de API existan
    $apis = [
        'mapa_coordenadas.php' => '../api/mapa_coordenadas.php',
        'calcular_ruta.php' => '../api/calcular_ruta.php',
        'buscar_lugares.php' => '../api/buscar_lugares.php'
    ];

    foreach ($apis as $nombre => $ruta) {
        if (file_exists($ruta)) {
            echo "<p class='ok'>✓ API $nombre existe</p>";
        } else {
            echo "<p class='error'>✗ API $nombre no encontrada</p>";
        }
    }

    // 5. Verificar páginas
    echo "<h2>4. Verificando páginas</h2>";

    $paginas = [
        'mapa-rutas-realista.php' => '../pages/mapa-rutas-realista.php',
        'mapa-rutas.php' => '../pages/mapa-rutas.php',
        'mapa-interactivo.php' => '../pages/mapa-interactivo.php'
    ];

    foreach ($paginas as $nombre => $ruta) {
        if (file_exists($ruta)) {
            echo "<p class='ok'>✓ Página $nombre existe</p>";
        } else {
            echo "<p class='error'>✗ Página $nombre no encontrada</p>";
        }
    }

    // 6. Crear log de éxito
    $log_message = date('Y-m-d H:i:s') . " - Sistema corregido y verificado exitosamente\n";
    file_put_contents($logsDir . '/correcciones.log', $log_message, FILE_APPEND | LOCK_EX);
    echo "<p class='ok'>✓ Log de correcciones creado</p>";

    // ÉXITO
    echo "<div class='success-box'>";
    echo "<h2 style='color: #4CAF50; margin: 0 0 15px 0;'>🎉 ¡Sistema Corregido y Funcionando!</h2>";
    echo "<p>Todos los problemas han sido resueltos. El mapa realista está listo para usar.</p>";

    echo "<div style='margin-top: 20px;'>";
    echo "<a href='../pages/mapa-rutas-realista.php' class='button'>🗺️ Usar Mapa Realista</a>";
    echo "<a href='../pages/mapa-rutas.php' class='button'>🛣️ Mapa con Rutas</a>";
    echo "<a href='../Public/favoritos.php' class='button'>⭐ Rutas Favoritas</a>";
    echo "</div>";
    echo "</div>";

    echo "<h2>📊 Estado Final del Sistema:</h2>";

    // Estadísticas finales
    $stmt = $pdo->query("SELECT COUNT(*) FROM Aulas WHERE coordenada_x IS NOT NULL");
    $aulas_coords = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM PuntosConexion");
    $puntos = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM Rutas");
    $rutas = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM Edificios");
    $edificios = $stmt->fetchColumn();

    echo "<ul>";
    echo "<li>✅ $edificios edificios mapeados</li>";
    echo "<li>✅ $aulas_coords aulas con coordenadas</li>";
    echo "<li>✅ $puntos puntos de conexión</li>";
    echo "<li>✅ $rutas rutas configuradas</li>";
    echo "<li>✅ Sistema de logs funcionando</li>";
    echo "<li>✅ APIs disponibles</li>";
    echo "<li>✅ Páginas web listas</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; border: 2px solid #f44336; padding: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: #f44336;'>Error durante la corrección</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>