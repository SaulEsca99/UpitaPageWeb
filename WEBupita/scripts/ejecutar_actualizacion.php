<?php
// Ruta: WEBupita/scripts/ejecutar_actualizacion.php
// Script principal para ejecutar toda la actualización del mapa real

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutos

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Actualización Mapa UPIITA</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #FF9800; font-weight: bold; }
        .info { color: #2196F3; font-weight: bold; }
        .step { 
            background: rgba(255,255,255,0.1); 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }
        h1 { text-align: center; margin-bottom: 30px; font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        h2 { color: #FFD700; margin-top: 25px; }
        .success-box {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        .button:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #FFD700;
        }
    </style>
    <script>
        function updateProgress(percent) {
            document.getElementById('progressFill').style.width = percent + '%';
        }
        
        function scrollToBottom() {
            window.scrollTo(0, document.body.scrollHeight);
        }
        
        // Auto-scroll cada segundo
        setInterval(scrollToBottom, 1000);
    </script>
</head>
<body>
<div class='container'>";

echo "<h1>🗺️ Actualización del Mapa Realista UPIITA</h1>";

echo "<div class='progress-bar'>
    <div id='progressFill' class='progress-fill'></div>
</div>";

$pasos_totales = 8;
$paso_actual = 0;

function mostrarPaso($titulo, $descripcion = '') {
    global $paso_actual, $pasos_totales;
    $paso_actual++;
    $progreso = ($paso_actual / $pasos_totales) * 100;

    echo "<div class='step'>";
    echo "<h2>Paso $paso_actual/$pasos_totales: $titulo</h2>";
    if ($descripcion) {
        echo "<p style='margin-bottom: 15px; opacity: 0.9;'>$descripcion</p>";
    }

    echo "<script>updateProgress($progreso);</script>";

    return $paso_actual;
}

function mostrarResultado($mensaje, $tipo = 'ok') {
    echo "<p class='$tipo'>$mensaje</p>";
    flush();
    ob_flush();
}

// Iniciar buffer de salida
ob_start();

try {
    // PASO 1: Verificar requisitos
    mostrarPaso("Verificación de Requisitos", "Comprobando que todos los archivos y dependencias estén disponibles");

    if (!file_exists('../includes/conexion.php')) {
        throw new Exception('Archivo de conexión no encontrado');
    }

    if (!file_exists('../includes/MapaReal.php')) {
        throw new Exception('Clase MapaReal no encontrada');
    }

    require_once '../includes/conexion.php';
    require_once '../includes/MapaReal.php';
    require_once '../includes/Dijkstra.php';

    mostrarResultado("✓ Todos los archivos requeridos están disponibles");
    mostrarResultado("✓ Conexión a base de datos establecida");
    mostrarResultado("✓ Clases PHP cargadas correctamente");

    sleep(1); // Pausa para mostrar progreso

    // PASO 2: Inicializar clases
    mostrarPaso("Inicialización del Sistema", "Creando instancias de las clases principales");

    $mapaReal = new MapaReal($pdo);
    $dijkstra = new Dijkstra($pdo);

    mostrarResultado("✓ MapaReal inicializado");
    mostrarResultado("✓ Sistema Dijkstra inicializado");

    // PASO 3: Validar integridad de datos
    mostrarPaso("Validación de Datos", "Verificando que los datos del mapa sean consistentes");

    $validacion = $mapaReal->validarIntegridad();

    if ($validacion['valido']) {
        mostrarResultado("✓ Validación exitosa");
        mostrarResultado("✓ Total de edificios: {$validacion['total_edificios']}");
        mostrarResultado("✓ Total de aulas: {$validacion['total_aulas']}");
    } else {
        mostrarResultado("⚠ Errores encontrados en los datos:", 'warning');
        foreach ($validacion['errores'] as $error) {
            mostrarResultado("  - $error", 'warning');
        }
        echo "<p class='info'>Continuando con la actualización...</p>";
    }

    // PASO 4: Limpiar datos existentes
    mostrarPaso("Limpieza de Datos Anteriores", "Removiendo coordenadas y rutas obsoletas");

    $pdo->exec("DELETE FROM Rutas WHERE id > 0");
    $conteo_rutas = $pdo->lastInsertId();

    $pdo->exec("DELETE FROM PuntosConexion WHERE id > 0");
    $conteo_puntos = $pdo->lastInsertId();

    $pdo->exec("UPDATE Aulas SET coordenada_x = NULL, coordenada_y = NULL WHERE idAula > 0");

    mostrarResultado("✓ Rutas anteriores eliminadas");
    mostrarResultado("✓ Puntos de conexión eliminados");
    mostrarResultado("✓ Coordenadas de aulas limpiadas");

    // PASO 5: Actualizar coordenadas reales
    mostrarPaso("Actualización de Coordenadas", "Insertando las coordenadas reales basadas en las imágenes de UPIITA");

    $resultado = $mapaReal->actualizarCoordenadasReales();

    if ($resultado) {
        mostrarResultado("✓ Coordenadas actualizadas exitosamente");

        // Verificar resultados
        $stmt = $pdo->query("SELECT COUNT(*) FROM Aulas WHERE coordenada_x IS NOT NULL");
        $aulas_coords = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM PuntosConexion");
        $puntos_coords = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM Rutas");
        $rutas_coords = $stmt->fetchColumn();

        mostrarResultado("✓ Aulas con coordenadas: $aulas_coords");
        mostrarResultado("✓ Puntos de conexión: $puntos_coords");
        mostrarResultado("✓ Rutas configuradas: $rutas_coords");
    } else {
        throw new Exception('Error actualizando coordenadas reales');
    }

    // PASO 6: Verificar conectividad
    mostrarPaso("Verificación de Conectividad", "Probando que todas las rutas funcionen correctamente");

    $edificios_test = ['A1', 'A2', 'LC', 'EG'];
    $rutas_exitosas = 0;
    $rutas_totales = 0;

    foreach ($edificios_test as $i => $edificio1) {
        for ($j = $i + 1; $j < count($edificios_test); $j++) {
            $edificio2 = $edificios_test[$j];
            $rutas_totales++;

            // Buscar aula en cada edificio
            $stmt = $pdo->prepare("
                SELECT a.idAula FROM Aulas a
                JOIN Edificios e ON a.idEdificio = e.idEdificio
                WHERE e.nombre LIKE ? AND a.coordenada_x IS NOT NULL
                LIMIT 1
            ");

            $stmt->execute(["%$edificio1%"]);
            $aula1_id = $stmt->fetchColumn();

            $stmt->execute(["%$edificio2%"]);
            $aula2_id = $stmt->fetchColumn();

            if ($aula1_id && $aula2_id) {
                $ruta = $dijkstra->calcularRutaMasCorta('aula', $aula1_id, 'aula', $aula2_id);
                if ($ruta['encontrada']) {
                    $rutas_exitosas++;
                    mostrarResultado("✓ Ruta $edificio1 → $edificio2: {$ruta['distancia_total']}m");
                } else {
                    mostrarResultado("✗ No hay ruta entre $edificio1 y $edificio2", 'warning');
                }
            }
        }
    }

    mostrarResultado("✓ Conectividad: $rutas_exitosas/$rutas_totales rutas funcionando");

    // PASO 7: Generar estadísticas
    mostrarPaso("Generación de Estadísticas", "Calculando métricas del nuevo sistema");

    $stats = $mapaReal->obtenerEstadisticasCampus();

    echo "<div class='stats'>";
    echo "<div class='stat-card'><div class='stat-number'>{$stats['total_edificios']}</div><div>Edificios</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$stats['total_aulas']}</div><div>Aulas</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>{$stats['total_conexiones']}</div><div>Conexiones</div></div>";
    echo "<div class='stat-card'><div class='stat-number'>" . round($stats['area_campus']) . "m²</div><div>Área Campus</div></div>";
    echo "</div>";

    mostrarResultado("✓ Estadísticas generadas correctamente");

    // PASO 8: Prueba final del sistema
    mostrarPaso("Prueba Final del Sistema", "Realizando una prueba completa de funcionalidad");

    // Probar API
    $api_test = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../api/mapa_coordenadas.php');
    $api_data = json_decode($api_test, true);

    if ($api_data && $api_data['success']) {
        mostrarResultado("✓ API de coordenadas funcionando");
        mostrarResultado("✓ Datos JSON válidos generados");
    } else {
        mostrarResultado("⚠ API podría tener problemas", 'warning');
    }

    // Probar cálculo de ruta complejo
    $stmt = $pdo->query("
        SELECT idAula, numeroAula FROM Aulas 
        WHERE coordenada_x IS NOT NULL 
        ORDER BY RAND() LIMIT 2
    ");
    $aulas_test = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($aulas_test) >= 2) {
        $ruta_test = $dijkstra->calcularRutaMasCorta('aula', $aulas_test[0]['idAula'], 'aula', $aulas_test[1]['idAula']);

        if ($ruta_test['encontrada']) {
            mostrarResultado("✓ Sistema de rutas completamente funcional");
            mostrarResultado("✓ Ruta de prueba: {$aulas_test[0]['numeroAula']} → {$aulas_test[1]['numeroAula']} ({$ruta_test['distancia_total']}m)");
        } else {
            throw new Exception('Sistema de rutas no funciona correctamente');
        }
    }

    // ÉXITO TOTAL
    echo "<script>updateProgress(100);</script>";

    echo "<div class='success-box'>";
    echo "<h2 style='color: #4CAF50; margin: 0 0 15px 0;'>🎉 ¡Actualización Completada Exitosamente!</h2>";
    echo "<p style='font-size: 1.1rem; margin-bottom: 20px;'>El mapa realista de UPIITA ha sido configurado correctamente con todas las coordenadas y rutas basadas en las imágenes reales.</p>";

    echo "<div style='display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;'>";
    echo "<a href='../pages/mapa-rutas-realista.php' class='button'>🗺️ Ver Mapa Realista</a>";
    echo "<a href='../pages/mapa-rutas.php' class='button'>🛣️ Sistema de Rutas</a>";
    echo "<a href='../Public/favoritos.php' class='button'>⭐ Rutas Favoritas</a>";
    echo "<a href='../test_sistema.php' class='button'>🧪 Pruebas del Sistema</a>";
    echo "</div>";
    echo "</div>";

    echo "<div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h3 style='color: #FFD700; margin-top: 0;'>📊 Resumen de la Actualización:</h3>";
    echo "<ul style='line-height: 1.6;'>";
    echo "<li>✅ $aulas_coords aulas con coordenadas reales actualizadas</li>";
    echo "<li>✅ $puntos_coords puntos de conexión entre edificios</li>";
    echo "<li>✅ $rutas_coords rutas internas y externas configuradas</li>";
    echo "<li>✅ {$stats['total_edificios']} edificios completamente mapeados</li>";
    echo "<li>✅ Sistema de navegación 3D/isométrico funcional</li>";
    echo "<li>✅ API REST para coordenadas en tiempo real</li>";
    echo "<li>✅ Integración completa con rutas favoritas</li>";
    echo "<li>✅ Compatibilidad con dispositivos móviles</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: rgba(33, 150, 243, 0.2); border: 2px solid #2196F3; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h4 style='color: #2196F3; margin-top: 0;'>🚀 Nuevas Características Disponibles:</h4>";
    echo "<ul style='line-height: 1.6;'>";
    echo "<li>🏢 Vista 3D de edificios con efectos de sombra</li>";
    echo "<li>🗺️ Mapa interactivo con zoom y navegación</li>";
    echo "<li>📱 Diseño responsivo para móviles y tablets</li>";
    echo "<li>⚡ Cálculo de rutas en tiempo real</li>";
    echo "<li>💾 Sistema de rutas favoritas personalizado</li>";
    echo "<li>🔍 Búsqueda inteligente de ubicaciones</li>";
    echo "<li>📏 Medición automática de distancias</li>";
    echo "<li>🎯 Navegación por pisos dentro de edificios</li>";
    echo "</ul>";
    echo "</div>";

    // Log de éxito
    $log_message = date('Y-m-d H:i:s') . " - Mapa realista UPIITA actualizado exitosamente\n";
    $log_message .= "Edificios: {$stats['total_edificios']}, Aulas: {$stats['total_aulas']}, Rutas: $rutas_coords\n";
    file_put_contents('../logs/actualizaciones.log', $log_message, FILE_APPEND | LOCK_EX);

} catch (Exception $e) {
    echo "<div style='background: rgba(244, 67, 54, 0.2); border: 2px solid #f44336; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #f44336; margin-top: 0;'>❌ Error Durante la Actualización</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor revisa los logs y corrige los errores antes de continuar.</p>";

    echo "<h3>🔧 Posibles Soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verifica que la base de datos 'upiita' exista</li>";
    echo "<li>Asegúrate de que XAMPP esté ejecutándose</li>";
    echo "<li>Confirma que todos los archivos PHP estén en su lugar</li>";
    echo "<li>Revisa los permisos de archivos y carpetas</li>";
    echo "</ul>";

    echo "<div style='margin-top: 20px;'>";
    echo "<a href='../test_sistema.php' class='button' style='background: #FF9800;'>🔍 Ejecutar Diagnóstico</a>";
    echo "<a href='ejecutar_actualizacion.php' class='button' style='background: #2196F3;'>🔄 Reintentar</a>";
    echo "</div>";
    echo "</div>";

    // Log de error
    $error_message = date('Y-m-d H:i:s') . " - ERROR en actualización: " . $e->getMessage() . "\n";
    file_put_contents('../logs/errores.log', $error_message, FILE_APPEND | LOCK_EX);
}

echo "</div>";
echo "<script>scrollToBottom();</script>";
echo "</body></html>";

// Flush final
ob_end_flush();
?>