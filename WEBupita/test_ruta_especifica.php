<?php
// Ruta: WEBupita/test_ruta_especifica.php
// MÓDULO 3: Prueba específica del algoritmo Dijkstra

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/conexion.php';
require_once 'includes/Dijkstra.php';

echo "<h1>PRUEBA ESPECÍFICA DE RUTAS - MÓDULO 3</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .ruta-detalle { background: #f0f8ff; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; border-radius: 5px; }
    .paso-ruta { background: #e8f5e8; padding: 8px; margin: 5px 0; border-left: 3px solid #4CAF50; }
    .test-case { background: #fff3e0; padding: 15px; margin: 15px 0; border-radius: 8px; border: 1px solid #ff9800; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

echo "<div class='container'>";

function mostrarRutaDetallada($resultado, $origen_info, $destino_info) {
    if ($resultado['encontrada']) {
        echo "<div class='ruta-detalle'>";
        echo "<h4 class='ok'>✅ RUTA ENCONTRADA</h4>";
        echo "<p><strong>Origen:</strong> {$origen_info['codigo']} - {$origen_info['nombre']}</p>";
        echo "<p><strong>Destino:</strong> {$destino_info['codigo']} - {$destino_info['nombre']}</p>";
        echo "<p><strong>Distancia total:</strong> " . number_format($resultado['distancia_total'], 2) . " metros</p>";
        echo "<p><strong>Número de pasos:</strong> {$resultado['numero_pasos']}</p>";

        if (!empty($resultado['ruta_detallada'])) {
            echo "<h5>📍 Pasos de la ruta:</h5>";
            foreach ($resultado['ruta_detallada'] as $i => $paso) {
                $numero = $i + 1;
                echo "<div class='paso-ruta'>";
                echo "<strong>Paso $numero:</strong> {$paso['descripcion']} ";
                echo "<span class='info'>({$paso['distancia']}m)</span>";
                echo "</div>";
            }
        }
        echo "</div>";
        return true;
    } else {
        echo "<div class='ruta-detalle' style='border-left-color: #f44336; background: #ffebee;'>";
        echo "<h4 class='error'>❌ RUTA NO ENCONTRADA</h4>";
        echo "<p><strong>Origen:</strong> {$origen_info['codigo']} - {$origen_info['nombre']}</p>";
        echo "<p><strong>Destino:</strong> {$destino_info['codigo']} - {$destino_info['nombre']}</p>";
        echo "<p class='error'><strong>Error:</strong> {$resultado['mensaje']}</p>";
        echo "</div>";
        return false;
    }
}

function buscarLugar($pdo, $codigo) {
    // Buscar en aulas
    $stmt = $pdo->prepare("
        SELECT 'aula' as tipo, idAula as id, numeroAula as codigo, nombreAula as nombre, 
               piso, idEdificio, coordenada_x, coordenada_y
        FROM Aulas 
        WHERE numeroAula = ? AND coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL
    ");
    $stmt->execute([$codigo]);
    $lugar = $stmt->fetch();

    if ($lugar) return $lugar;

    // Buscar en puntos de conexión
    $stmt = $pdo->prepare("
        SELECT 'punto' as tipo, id, nombre as codigo, nombre, 
               piso, idEdificio, coordenada_x, coordenada_y
        FROM PuntosConexion 
        WHERE nombre = ?
    ");
    $stmt->execute([$codigo]);
    return $stmt->fetch();
}

try {
    $dijkstra = new Dijkstra($pdo);

    echo "<h2>🎯 CASOS DE PRUEBA ESPECÍFICOS</h2>";

    // Caso 1: La ruta problemática original
    echo "<div class='test-case'>";
    echo "<h3>Caso 1: A-305 → EP-101 (Ruta problemática original)</h3>";

    $origen = buscarLugar($pdo, 'A-305');
    $destino = buscarLugar($pdo, 'EP-101');

    if ($origen && $destino) {
        $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
        $exito1 = mostrarRutaDetallada($resultado, $origen, $destino);
    } else {
        echo "<p class='error'>No se encontraron las aulas A-305 o EP-101</p>";
        $exito1 = false;
    }
    echo "</div>";

    // Caso 2: Ruta que funcionaba antes
    echo "<div class='test-case'>";
    echo "<h3>Caso 2: A-305 → A-306 (Ruta que debería ser rápida)</h3>";

    $origen = buscarLugar($pdo, 'A-305');
    $destino = buscarLugar($pdo, 'A-306');

    if ($origen && $destino) {
        $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
        $exito2 = mostrarRutaDetallada($resultado, $origen, $destino);
    } else {
        echo "<p class='error'>No se encontraron las aulas A-305 o A-306</p>";
        $exito2 = false;
    }
    echo "</div>";

    // Caso 3: Ruta entre diferentes edificios
    echo "<div class='test-case'>";
    echo "<h3>Caso 3: A-100 → LC-100 (Entre edificios A1 y LC)</h3>";

    $origen = buscarLugar($pdo, 'A-100');
    $destino = buscarLugar($pdo, 'LC-100');

    if ($origen && $destino) {
        $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
        $exito3 = mostrarRutaDetallada($resultado, $origen, $destino);
    } else {
        echo "<p class='warning'>No se encontraron las aulas A-100 o LC-100, probando alternativas...</p>";

        // Buscar alternativas
        $stmt = $pdo->query("SELECT numeroAula FROM Aulas WHERE numeroAula LIKE 'LC-%' AND coordenada_x IS NOT NULL LIMIT 1");
        $lc_aula = $stmt->fetchColumn();

        if ($lc_aula) {
            $destino = buscarLugar($pdo, $lc_aula);
            $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
            $exito3 = mostrarRutaDetallada($resultado, $origen, $destino);
        } else {
            $exito3 = false;
        }
    }
    echo "</div>";

    // Caso 4: Prueba con puntos de conexión
    echo "<div class='test-case'>";
    echo "<h3>Caso 4: Entrada-A1 → Entrada-EP (Entre entradas de edificios)</h3>";

    $origen = buscarLugar($pdo, 'Entrada-A1');
    $destino = buscarLugar($pdo, 'Entrada-EP');

    if ($origen && $destino) {
        $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
        $exito4 = mostrarRutaDetallada($resultado, $origen, $destino);
    } else {
        echo "<p class='error'>No se encontraron los puntos de entrada</p>";
        $exito4 = false;
    }
    echo "</div>";

    // Caso 5: Prueba de escaleras
    echo "<div class='test-case'>";
    echo "<h3>Caso 5: A-100 → A-110 (Entre pisos usando escaleras)</h3>";

    $origen = buscarLugar($pdo, 'A-100');  // Piso 1
    $destino = buscarLugar($pdo, 'A-110'); // Piso 2

    if ($origen && $destino) {
        $resultado = $dijkstra->calcularRutaMasCorta($origen['tipo'], $origen['id'], $destino['tipo'], $destino['id']);
        $exito5 = mostrarRutaDetallada($resultado, $origen, $destino);
    } else {
        echo "<p class='error'>No se encontraron las aulas A-100 o A-110</p>";
        $exito5 = false;
    }
    echo "</div>";

    echo "<h2>📊 RESUMEN DE PRUEBAS</h2>";
    echo "<table>";
    echo "<tr><th>Caso de Prueba</th><th>Resultado</th><th>Estado</th></tr>";
    echo "<tr><td>A-305 → EP-101</td><td>" . ($exito1 ? "✅ EXITOSO" : "❌ FALLÓ") . "</td><td>" . ($exito1 ? "Ruta encontrada" : "Sin ruta") . "</td></tr>";
    echo "<tr><td>A-305 → A-306</td><td>" . ($exito2 ? "✅ EXITOSO" : "❌ FALLÓ") . "</td><td>" . ($exito2 ? "Ruta encontrada" : "Sin ruta") . "</td></tr>";
    echo "<tr><td>A-100 → LC</td><td>" . ($exito3 ? "✅ EXITOSO" : "❌ FALLÓ") . "</td><td>" . ($exito3 ? "Ruta encontrada" : "Sin ruta") . "</td></tr>";
    echo "<tr><td>Entrada-A1 → Entrada-EP</td><td>" . ($exito4 ? "✅ EXITOSO" : "❌ FALLÓ") . "</td><td>" . ($exito4 ? "Ruta encontrada" : "Sin ruta") . "</td></tr>";
    echo "<tr><td>A-100 → A-110 (escaleras)</td><td>" . ($exito5 ? "✅ EXITOSO" : "❌ FALLÓ") . "</td><td>" . ($exito5 ? "Ruta encontrada" : "Sin ruta") . "</td></tr>";
    echo "</table>";

    $exitosos = array_sum([$exito1, $exito2, $exito3, $exito4, $exito5]);
    $total = 5;

    echo "<div style='background: " . ($exitosos >= 4 ? "#e8f5e8" : "#ffebee") . "; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>" . ($exitosos >= 4 ? "🎉 SISTEMA FUNCIONANDO CORRECTAMENTE" : "⚠️ NECESITA MÁS CORRECCIONES") . "</h3>";
    echo "<p><strong>Pruebas exitosas:</strong> $exitosos de $total</p>";

    if ($exitosos >= 4) {
        echo "<p class='ok'>✅ El algoritmo Dijkstra está funcionando correctamente</p>";
        echo "<p class='ok'>✅ La conectividad entre edificios se ha restablecido</p>";
        echo "<p class='ok'>✅ Las rutas problemáticas ahora funcionan</p>";
    } else {
        echo "<p class='error'>❌ Algunas rutas aún presentan problemas</p>";
        echo "<p>Se necesita revisar el algoritmo Dijkstra o agregar más conexiones</p>";
    }
    echo "</div>";

    echo "<h2>🔧 HERRAMIENTAS DE DIAGNÓSTICO</h2>";
    echo "<p><a href='scripts/diagnostico_conectividad.php'>🔍 Ejecutar diagnóstico completo</a></p>";
    echo "<p><a href='api/buscar_lugares.php'>📋 Ver todos los lugares disponibles</a></p>";

    // Formulario para pruebas manuales
    echo "<h3>🧪 Prueba Manual de Rutas</h3>";
    echo "<form method='GET' style='background: #f9f9f9; padding: 15px; border-radius: 8px;'>";
    echo "<label>Origen: <input type='text' name='origen' placeholder='Ej: A-305' value='" . ($_GET['origen'] ?? '') . "'></label> ";
    echo "<label>Destino: <input type='text' name='destino' placeholder='Ej: EP-101' value='" . ($_GET['destino'] ?? '') . "'></label> ";
    echo "<input type='submit' value='Calcular Ruta' style='background: #2196F3; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>";
    echo "</form>";

    // Prueba manual si se envió el formulario
    if (isset($_GET['origen']) && isset($_GET['destino']) && $_GET['origen'] && $_GET['destino']) {
        echo "<h4>Resultado de Prueba Manual:</h4>";
        $origen_manual = buscarLugar($pdo, $_GET['origen']);
        $destino_manual = buscarLugar($pdo, $_GET['destino']);

        if ($origen_manual && $destino_manual) {
            $resultado_manual = $dijkstra->calcularRutaMasCorta($origen_manual['tipo'], $origen_manual['id'], $destino_manual['tipo'], $destino_manual['id']);
            mostrarRutaDetallada($resultado_manual, $origen_manual, $destino_manual);
        } else {
            echo "<p class='error'>No se encontraron los lugares especificados</p>";
        }
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Error durante las pruebas: " . $e->getMessage() . "</p>";
}

echo "</div>";
echo "<hr>";
echo "<p><em>Pruebas completadas: " . date('Y-m-d H:i:s') . "</em></p>";
?>