<?php
// Ruta: WEBupita/scripts/diagnostico_conectividad.php
// MÓDULO 1: Diagnóstico completo de la conectividad del grafo

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/conexion.php';

echo "<h1>DIAGNÓSTICO DE CONECTIVIDAD - MÓDULO 1</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

function mostrarConsultaDiagnostico($pdo, $query, $titulo) {
    echo "<h3>$titulo</h3>";
    try {
        $stmt = $pdo->query($query);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultados)) {
            echo "<p class='warning'>⚠ No se encontraron resultados</p>";
            return [];
        }

        echo "<table>";
        echo "<tr>";
        foreach (array_keys($resultados[0]) as $columna) {
            echo "<th>" . htmlspecialchars($columna) . "</th>";
        }
        echo "</tr>";

        foreach ($resultados as $fila) {
            echo "<tr>";
            foreach ($fila as $valor) {
                echo "<td>" . htmlspecialchars($valor) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";

        echo "<p class='info'>Total de registros: " . count($resultados) . "</p>";

        return $resultados;
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        return [];
    }
}

try {
    echo "<h2>1. VERIFICACIÓN DE DATOS BÁSICOS</h2>";

    // Verificar edificios
    $edificios = mostrarConsultaDiagnostico($pdo,
        "SELECT idEdificio, nombre, descripcion, pisos FROM Edificios ORDER BY idEdificio",
        "Edificios disponibles"
    );

    // Verificar aulas con coordenadas
    $aulas = mostrarConsultaDiagnostico($pdo,
        "SELECT idAula, numeroAula, nombreAula, piso, idEdificio, 
                coordenada_x, coordenada_y 
         FROM Aulas 
         WHERE coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL 
         ORDER BY idEdificio, numeroAula
         LIMIT 20",
        "Primeras 20 aulas con coordenadas"
    );

    // Verificar puntos de conexión
    $puntos = mostrarConsultaDiagnostico($pdo,
        "SELECT id, nombre, tipo, piso, idEdificio, coordenada_x, coordenada_y 
         FROM PuntosConexion 
         ORDER BY idEdificio, tipo",
        "Puntos de conexión disponibles"
    );

    echo "<h2>2. ANÁLISIS DE RUTAS Y CONECTIVIDAD</h2>";

    // Verificar rutas totales
    $rutas = mostrarConsultaDiagnostico($pdo,
        "SELECT origen_tipo, origen_id, destino_tipo, destino_id, 
                distancia, es_bidireccional, tipo_conexion
         FROM Rutas 
         ORDER BY origen_tipo, origen_id, destino_tipo, destino_id",
        "Todas las rutas configuradas"
    );

    // Analizar conectividad por tipo
    echo "<h3>Análisis de conectividad por tipo de conexión</h3>";
    $analisis_conectividad = mostrarConsultaDiagnostico($pdo,
        "SELECT 
            CONCAT(origen_tipo, ' → ', destino_tipo) as tipo_conexion,
            COUNT(*) as total_rutas,
            AVG(distancia) as distancia_promedio,
            MIN(distancia) as distancia_minima,
            MAX(distancia) as distancia_maxima
         FROM Rutas 
         GROUP BY origen_tipo, destino_tipo
         ORDER BY total_rutas DESC",
        "Estadísticas de conectividad"
    );

    echo "<h2>3. DETECCIÓN DE PROBLEMAS DE CONECTIVIDAD</h2>";

    // Buscar nodos aislados (sin conexiones)
    echo "<h3>Nodos potencialmente aislados</h3>";

    // Aulas sin conexiones de salida
    $aulas_sin_salida = mostrarConsultaDiagnostico($pdo,
        "SELECT a.idAula, a.numeroAula, a.nombreAula, a.idEdificio
         FROM Aulas a
         WHERE a.coordenada_x IS NOT NULL 
         AND a.coordenada_y IS NOT NULL
         AND NOT EXISTS (
             SELECT 1 FROM Rutas r 
             WHERE r.origen_tipo = 'aula' AND r.origen_id = a.idAula
         )",
        "Aulas sin conexiones de salida"
    );

    // Aulas sin conexiones de entrada
    $aulas_sin_entrada = mostrarConsultaDiagnostico($pdo,
        "SELECT a.idAula, a.numeroAula, a.nombreAula, a.idEdificio
         FROM Aulas a
         WHERE a.coordenada_x IS NOT NULL 
         AND a.coordenada_y IS NOT NULL
         AND NOT EXISTS (
             SELECT 1 FROM Rutas r 
             WHERE r.destino_tipo = 'aula' AND r.destino_id = a.idAula
         )",
        "Aulas sin conexiones de entrada"
    );

    // Puntos de conexión sin rutas
    $puntos_sin_rutas = mostrarConsultaDiagnostico($pdo,
        "SELECT p.id, p.nombre, p.tipo, p.idEdificio
         FROM PuntosConexion p
         WHERE NOT EXISTS (
             SELECT 1 FROM Rutas r 
             WHERE (r.origen_tipo = 'punto' AND r.origen_id = p.id)
                OR (r.destino_tipo = 'punto' AND r.destino_id = p.id)
         )",
        "Puntos de conexión sin rutas"
    );

    echo "<h2>4. VERIFICACIÓN DE CASOS ESPECÍFICOS</h2>";

    // Verificar conectividad entre edificios específicos
    echo "<h3>Verificando rutas entre edificios A y EP</h3>";

    $ruta_a_ep = mostrarConsultaDiagnostico($pdo,
        "SELECT 
            r.origen_tipo, r.origen_id, r.destino_tipo, r.destino_id, r.distancia,
            CASE 
                WHEN r.origen_tipo = 'aula' THEN 
                    (SELECT CONCAT(a.numeroAula, ' (', e.nombre, ')') 
                     FROM Aulas a JOIN Edificios e ON a.idEdificio = e.idEdificio 
                     WHERE a.idAula = r.origen_id)
                ELSE 
                    (SELECT CONCAT(p.nombre, ' (', e.nombre, ')') 
                     FROM PuntosConexion p JOIN Edificios e ON p.idEdificio = e.idEdificio 
                     WHERE p.id = r.origen_id)
            END as origen_nombre,
            CASE 
                WHEN r.destino_tipo = 'aula' THEN 
                    (SELECT CONCAT(a.numeroAula, ' (', e.nombre, ')') 
                     FROM Aulas a JOIN Edificios e ON a.idEdificio = e.idEdificio 
                     WHERE a.idAula = r.destino_id)
                ELSE 
                    (SELECT CONCAT(p.nombre, ' (', e.nombre, ')') 
                     FROM PuntosConexion p JOIN Edificios e ON p.idEdificio = e.idEdificio 
                     WHERE p.id = r.destino_id)
            END as destino_nombre
         FROM Rutas r
         WHERE (
             (r.origen_tipo = 'aula' AND r.origen_id IN (SELECT idAula FROM Aulas WHERE idEdificio = 1))
             OR 
             (r.destino_tipo = 'aula' AND r.destino_id IN (SELECT idAula FROM Aulas WHERE idEdificio = 7))
         )
         OR (
             (r.origen_tipo = 'punto' AND r.origen_id IN (SELECT id FROM PuntosConexion WHERE idEdificio = 1))
             OR 
             (r.destino_tipo = 'punto' AND r.destino_id IN (SELECT id FROM PuntosConexion WHERE idEdificio = 7))
         )",
        "Rutas que conectan edificios A y EP"
    );

    echo "<h2>5. RESUMEN DE DIAGNÓSTICO</h2>";
    echo "<div style='background-color: #f0f0f0; padding: 15px; border-left: 4px solid #2196F3;'>";
    echo "<h3>Estadísticas del Sistema:</h3>";
    echo "<ul>";
    echo "<li><strong>Edificios:</strong> " . count($edificios) . "</li>";
    echo "<li><strong>Aulas con coordenadas:</strong> " . count($aulas) . "</li>";
    echo "<li><strong>Puntos de conexión:</strong> " . count($puntos) . "</li>";
    echo "<li><strong>Rutas totales:</strong> " . count($rutas) . "</li>";
    echo "</ul>";

    echo "<h3>Problemas detectados:</h3>";
    echo "<ul>";
    if (count($aulas_sin_salida) > 0) {
        echo "<li class='error'>⚠ " . count($aulas_sin_salida) . " aulas sin conexiones de salida</li>";
    }
    if (count($aulas_sin_entrada) > 0) {
        echo "<li class='error'>⚠ " . count($aulas_sin_entrada) . " aulas sin conexiones de entrada</li>";
    }
    if (count($puntos_sin_rutas) > 0) {
        echo "<li class='error'>⚠ " . count($puntos_sin_rutas) . " puntos de conexión sin rutas</li>";
    }
    if (count($aulas_sin_salida) == 0 && count($aulas_sin_entrada) == 0 && count($puntos_sin_rutas) == 0) {
        echo "<li class='ok'>✓ No se detectaron nodos completamente aislados</li>";
    }
    echo "</ul>";
    echo "</div>";

    echo "<h2>6. PRÓXIMOS PASOS</h2>";
    echo "<p><strong>Para continuar con el diagnóstico:</strong></p>";
    echo "<ol>";
    echo "<li>Ejecutar este script para identificar problemas de conectividad</li>";
    echo "<li>Revisar el algoritmo Dijkstra (Módulo 2)</li>";
    echo "<li>Verificar rutas específicas problemáticas (Módulo 3)</li>";
    echo "<li>Implementar correcciones (Módulo 4)</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error general: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Diagnóstico completado: " . date('Y-m-d H:i:s') . "</em></p>";
?>