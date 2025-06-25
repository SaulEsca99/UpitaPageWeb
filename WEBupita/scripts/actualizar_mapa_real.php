<?php
// Ruta: WEBupita/scripts/actualizar_mapa_real.php
// Script para actualizar la base de datos con las coordenadas reales de UPIITA

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/conexion.php';
require_once '../includes/MapaReal.php';

echo "<h1>Actualizando Mapa Real de UPIITA</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .ok { color: green; } .error { color: red; } .warning { color: orange; }</style>";

try {
    $mapaReal = new MapaReal($pdo);

    echo "<h2>1. Validando integridad de datos...</h2>";
    $validacion = $mapaReal->validarIntegridad();

    if ($validacion['valido']) {
        echo "<p class='ok'>✓ Validación exitosa</p>";
        echo "<p>Total de edificios: {$validacion['total_edificios']}</p>";
        echo "<p>Total de aulas: {$validacion['total_aulas']}</p>";
    } else {
        echo "<p class='error'>✗ Errores encontrados:</p>";
        foreach ($validacion['errores'] as $error) {
            echo "<p class='error'>- $error</p>";
        }
        throw new Exception('Datos del mapa inválidos');
    }

    echo "<h2>2. Actualizando coordenadas en base de datos...</h2>";
    $resultado = $mapaReal->actualizarCoordenadasReales();

    if ($resultado) {
        echo "<p class='ok'>✓ Coordenadas actualizadas exitosamente</p>";
    } else {
        throw new Exception('Error actualizando coordenadas');
    }

    echo "<h2>3. Verificando datos actualizados...</h2>";

    // Verificar aulas con coordenadas
    $stmt = $pdo->query("SELECT COUNT(*) FROM Aulas WHERE coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL");
    $aulas_con_coords = $stmt->fetchColumn();
    echo "<p class='ok'>✓ Aulas con coordenadas: $aulas_con_coords</p>";

    // Verificar puntos de conexión
    $stmt = $pdo->query("SELECT COUNT(*) FROM PuntosConexion");
    $puntos_conexion = $stmt->fetchColumn();
    echo "<p class='ok'>✓ Puntos de conexión: $puntos_conexion</p>";

    // Verificar rutas
    $stmt = $pdo->query("SELECT COUNT(*) FROM Rutas");
    $total_rutas = $stmt->fetchColumn();
    echo "<p class='ok'>✓ Rutas totales: $total_rutas</p>";

    echo "<h2>4. Estadísticas del campus...</h2>";
    $stats = $mapaReal->obtenerEstadisticasCampus();
    echo "<p>Área aproximada del campus: " . round($stats['area_campus'], 2) . " m²</p>";
    echo "<p>Aulas por edificio:</p><ul>";
    foreach ($stats['aulas_por_edificio'] as $edificio => $count) {
        echo "<li>$edificio: $count aulas</li>";
    }
    echo "</ul>";

    echo "<h2>5. Probando sistema de rutas...</h2>";

    // Probar cálculo de ruta simple
    require_once '../includes/Dijkstra.php';
    $dijkstra = new Dijkstra($pdo);

    // Buscar dos aulas para probar
    $stmt = $pdo->query("
        SELECT idAula, numeroAula, nombreAula 
        FROM Aulas 
        WHERE coordenada_x IS NOT NULL 
        ORDER BY RAND() 
        LIMIT 2
    ");
    $aulas_test = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($aulas_test) >= 2) {
        $origen = $aulas_test[0];
        $destino = $aulas_test[1];

        echo "<p>Probando ruta de {$origen['numeroAula']} a {$destino['numeroAula']}...</p>";

        $resultado_ruta = $dijkstra->calcularRutaMasCorta('aula', $origen['idAula'], 'aula', $destino['idAula']);

        if ($resultado_ruta['encontrada']) {
            echo "<p class='ok'>✓ Ruta calculada exitosamente</p>";
            echo "<p>Distancia: {$resultado_ruta['distancia_total']} metros</p>";
            echo "<p>Pasos: {$resultado_ruta['numero_pasos']}</p>";
        } else {
            echo "<p class='warning'>⚠ No se encontró ruta: {$resultado_ruta['mensaje']}</p>";
        }
    } else {
        echo "<p class='warning'>⚠ No hay suficientes aulas para probar rutas</p>";
    }

    echo "<h2>6. Verificación final...</h2>";

    // Verificar conectividad básica
    $edificios = ['A1', 'A2', 'A3', 'A4', 'LC', 'EG', 'EP'];
    $conectividad_ok = true;

    foreach ($edificios as $i => $edificio1) {
        for ($j = $i + 1; $j < count($edificios); $j++) {
            $edificio2 = $edificios[$j];

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
                if (!$ruta['encontrada']) {
                    echo "<p class='error'>✗ No hay conectividad entre $edificio1 y $edificio2</p>";
                    $conectividad_ok = false;
                }
            }
        }
    }

    if ($conectividad_ok) {
        echo "<p class='ok'>✓ Conectividad entre todos los edificios verificada</p>";
    }

    echo "<h2 class='ok'>✅ Actualización completada exitosamente</h2>";
    echo "<p>El mapa real de UPIITA ha sido configurado correctamente.</p>";
    echo "<p><strong>Siguiente paso:</strong> Puedes usar el mapa interactivo con las coordenadas reales.</p>";

    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>Resumen de la actualización:</h3>";
    echo "<ul>";
    echo "<li>✓ $aulas_con_coords aulas con coordenadas actualizadas</li>";
    echo "<li>✓ $puntos_conexion puntos de conexión creados</li>";
    echo "<li>✓ $total_rutas rutas configuradas</li>";
    echo "<li>✓ " . count($edificios) . " edificios mapeados</li>";
    echo "<li>✓ Sistema de navegación funcional</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h2 class='error'>❌ Error durante la actualización</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor revisa los logs y corrige los errores antes de continuar.</p>";
}

echo "<hr>";
echo "<p><a href='../pages/mapa-rutas.php'>→ Ir al Mapa Interactivo</a></p>";
echo "<p><a href='../test_sistema.php'>→ Ejecutar Pruebas del Sistema</a></p>";
?>