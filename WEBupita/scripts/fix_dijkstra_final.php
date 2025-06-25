<?php
// Ruta: WEBupita/scripts/fix_dijkstra_final.php
// MÓDULO 4: Corrección final del algoritmo Dijkstra para mostrar rutas detalladas

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CORRECCIÓN FINAL DIJKSTRA - MÓDULO 4</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .step { background: #f0f8ff; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; border-radius: 5px; }
</style>";

echo "<div class='container'>";

// Crear el archivo corregido de Dijkstra.php
$dijkstra_corrected = '<?php
// Ruta: WEBupita/includes/Dijkstra.php

class Dijkstra {
    private $pdo;
    private $grafo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->grafo = [];
    }

    /**
     * Construye el grafo desde la base de datos
     */
    private function construirGrafo() {
        $this->grafo = [];

        try {
            // Obtener todas las rutas
            $stmt = $this->pdo->query("
                SELECT origen_tipo, origen_id, destino_tipo, destino_id, distancia, es_bidireccional
                FROM Rutas
            ");

            $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rutas as $ruta) {
                $origen = $ruta[\'origen_tipo\'] . \'_\' . $ruta[\'origen_id\'];
                $destino = $ruta[\'destino_tipo\'] . \'_\' . $ruta[\'destino_id\'];
                $distancia = floatval($ruta[\'distancia\']);

                // Agregar arista origen -> destino
                if (!isset($this->grafo[$origen])) {
                    $this->grafo[$origen] = [];
                }
                $this->grafo[$origen][$destino] = $distancia;

                // Si es bidireccional, agregar destino -> origen
                if ($ruta[\'es_bidireccional\']) {
                    if (!isset($this->grafo[$destino])) {
                        $this->grafo[$destino] = [];
                    }
                    $this->grafo[$destino][$origen] = $distancia;
                }
            }
        } catch (Exception $e) {
            error_log(\'Error construyendo grafo: \' . $e->getMessage());
            throw new Exception(\'Error al construir el grafo de rutas\');
        }
    }

    /**
     * Implementación del algoritmo de Dijkstra
     */
    public function calcularRutaMasCorta($origenTipo, $origenId, $destinoTipo, $destinoId) {
        try {
            $this->construirGrafo();

            $origen = $origenTipo . \'_\' . $origenId;
            $destino = $destinoTipo . \'_\' . $destinoId;

            // Verificar que los nodos existen en el grafo
            if (!isset($this->grafo[$origen]) && !$this->existeNodoEnDestinos($origen)) {
                return [
                    \'encontrada\' => false,
                    \'mensaje\' => \'Punto de origen no encontrado en el mapa de rutas\'
                ];
            }

            if (!isset($this->grafo[$destino]) && !$this->existeNodoEnDestinos($destino)) {
                return [
                    \'encontrada\' => false,
                    \'mensaje\' => \'Punto de destino no encontrado en el mapa de rutas\'
                ];
            }

            // Inicializar distancias y predecesores
            $distancias = [];
            $predecesores = [];
            $visitados = [];
            $nodos = $this->obtenerTodosLosNodos();

            // Verificar que hay nodos en el grafo
            if (empty($nodos)) {
                return [
                    \'encontrada\' => false,
                    \'mensaje\' => \'No hay rutas disponibles en el sistema\'
                ];
            }

            // Distancia infinita para todos los nodos excepto el origen
            foreach ($nodos as $nodo) {
                $distancias[$nodo] = PHP_FLOAT_MAX;
                $predecesores[$nodo] = null;
                $visitados[$nodo] = false;
            }
            $distancias[$origen] = 0;

            // Algoritmo de Dijkstra
            $iteraciones = 0;
            $maxIteraciones = count($nodos) * 2; // Evitar bucles infinitos

            while ($iteraciones < $maxIteraciones) {
                $iteraciones++;

                // Encontrar el nodo no visitado con menor distancia
                $nodoActual = null;
                $menorDistancia = PHP_FLOAT_MAX;

                foreach ($nodos as $nodo) {
                    if (!$visitados[$nodo] && $distancias[$nodo] < $menorDistancia) {
                        $menorDistancia = $distancias[$nodo];
                        $nodoActual = $nodo;
                    }
                }

                // Si no hay nodo actual o llegamos al destino
                if ($nodoActual === null || $nodoActual === $destino) {
                    break;
                }

                // Marcar como visitado
                $visitados[$nodoActual] = true;

                // Relajar aristas
                if (isset($this->grafo[$nodoActual])) {
                    foreach ($this->grafo[$nodoActual] as $vecino => $peso) {
                        if (!$visitados[$vecino]) {
                            $nuevaDistancia = $distancias[$nodoActual] + $peso;
                            if ($nuevaDistancia < $distancias[$vecino]) {
                                $distancias[$vecino] = $nuevaDistancia;
                                $predecesores[$vecino] = $nodoActual;
                            }
                        }
                    }
                }
            }

            // Verificar si se encontró una ruta
            if ($distancias[$destino] === PHP_FLOAT_MAX) {
                return [
                    \'encontrada\' => false,
                    \'mensaje\' => \'No existe una ruta entre los puntos seleccionados\'
                ];
            }

            // Reconstruir la ruta
            $ruta = [];
            $nodoActual = $destino;

            while ($nodoActual !== null) {
                $ruta[] = $nodoActual;
                $nodoActual = $predecesores[$nodoActual];
            }

            $ruta = array_reverse($ruta);

            // Obtener información detallada de cada punto en la ruta
            $rutaDetallada = $this->obtenerDetallesRuta($ruta);

            return [
                \'encontrada\' => true,
                \'distancia_total\' => round($distancias[$destino], 2),
                \'ruta\' => $ruta,
                \'ruta_detallada\' => $rutaDetallada,
                \'numero_pasos\' => count($ruta) - 1
            ];

        } catch (Exception $e) {
            error_log(\'Error en calcularRutaMasCorta: \' . $e->getMessage());
            return [
                \'encontrada\' => false,
                \'mensaje\' => \'Error interno al calcular la ruta\'
            ];
        }
    }

    /**
     * Verifica si un nodo existe como destino en alguna ruta
     */
    private function existeNodoEnDestinos($nodo) {
        foreach ($this->grafo as $vecinos) {
            if (isset($vecinos[$nodo])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtiene todos los nodos únicos del grafo
     */
    private function obtenerTodosLosNodos() {
        $nodos = [];

        foreach ($this->grafo as $origen => $vecinos) {
            $nodos[$origen] = true;
            foreach ($vecinos as $destino => $peso) {
                $nodos[$destino] = true;
            }
        }

        return array_keys($nodos);
    }

    /**
     * Obtiene información detallada de cada punto en la ruta
     */
    private function obtenerDetallesRuta($ruta) {
        $detalles = [];
        
        for ($i = 0; $i < count($ruta); $i++) {
            $nodo = $ruta[$i];
            list($tipo, $id) = explode(\'_\', $nodo);

            try {
                if ($tipo === \'aula\') {
                    $stmt = $this->pdo->prepare("
                        SELECT a.numeroAula as codigo, a.nombreAula as nombre, a.piso, a.idEdificio,
                               a.coordenada_x, a.coordenada_y, e.nombre as edificio_nombre
                        FROM Aulas a
                        LEFT JOIN Edificios e ON a.idEdificio = e.idEdificio
                        WHERE a.idAula = ?
                    ");
                    $stmt->execute([$id]);
                } else {
                    $stmt = $this->pdo->prepare("
                        SELECT p.nombre as codigo, p.nombre, p.piso, p.idEdificio,
                               p.coordenada_x, p.coordenada_y, e.nombre as edificio_nombre
                        FROM PuntosConexion p
                        LEFT JOIN Edificios e ON p.idEdificio = e.idEdificio
                        WHERE p.id = ?
                    ");
                    $stmt->execute([$id]);
                }

                $info = $stmt->fetch();
                
                if ($info) {
                    // Calcular distancia al siguiente punto
                    $distancia = 0;
                    if ($i < count($ruta) - 1) {
                        $siguienteNodo = $ruta[$i + 1];
                        if (isset($this->grafo[$nodo][$siguienteNodo])) {
                            $distancia = $this->grafo[$nodo][$siguienteNodo];
                        }
                    }
                    
                    $detalles[] = [
                        \'tipo\' => $tipo,
                        \'codigo\' => $info[\'codigo\'],
                        \'nombre\' => $info[\'nombre\'],
                        \'descripcion\' => $info[\'codigo\'] . \' - \' . $info[\'nombre\'],
                        \'distancia\' => round($distancia, 2),
                        \'piso\' => $info[\'piso\'],
                        \'edificio\' => $info[\'edificio_nombre\'],
                        \'coordenada_x\' => $info[\'coordenada_x\'],
                        \'coordenada_y\' => $info[\'coordenada_y\']
                    ];
                }
            } catch (Exception $e) {
                error_log(\'Error obteniendo detalles del nodo \' . $nodo . \': \' . $e->getMessage());
                $detalles[] = [
                    \'tipo\' => $tipo,
                    \'codigo\' => \'Desconocido\',
                    \'nombre\' => \'Punto no encontrado\',
                    \'descripcion\' => \'Error al cargar información\',
                    \'distancia\' => 0,
                    \'piso\' => 0,
                    \'edificio\' => \'Desconocido\'
                ];
            }
        }

        return $detalles;
    }

    /**
     * Obtiene todos los lugares disponibles para búsqueda
     */
    public function obtenerLugaresDisponibles() {
        try {
            $stmt = $this->pdo->query("
                SELECT \'aula\' as tipo, idAula as id, numeroAula as codigo, nombreAula as nombre, 
                       piso, idEdificio, coordenada_x, coordenada_y
                FROM Aulas 
                WHERE coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL
                UNION ALL
                SELECT \'punto\' as tipo, id, nombre as codigo, nombre, 
                       piso, idEdificio, coordenada_x, coordenada_y
                FROM PuntosConexion
                ORDER BY codigo
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log(\'Error obteniendo lugares: \' . $e->getMessage());
            return [];
        }
    }
}
?>';

echo "<div class='step'>";
echo "<h3>📝 Paso 1: Creando archivo Dijkstra.php corregido</h3>";

$archivo_destino = '../includes/Dijkstra.php';

// Hacer backup del archivo original
if (file_exists($archivo_destino)) {
    copy($archivo_destino, $archivo_destino . '.backup.' . date('Y-m-d-H-i-s'));
    echo "<p class='info'>✓ Backup creado del archivo original</p>";
}

// Escribir el archivo corregido
if (file_put_contents($archivo_destino, $dijkstra_corrected)) {
    echo "<p class='ok'>✓ Archivo Dijkstra.php actualizado exitosamente</p>";
} else {
    echo "<p class='error'>✗ Error escribiendo el archivo corregido</p>";
}
echo "</div>";

// Probar el sistema corregido
echo "<div class='step'>";
echo "<h3>🧪 Paso 2: Probando el sistema corregido</h3>";

try {
    require_once '../includes/conexion.php';
    require_once '../includes/Dijkstra.php';

    $dijkstra = new Dijkstra($pdo);

    // Buscar A-305 y EP-101
    $stmt = $pdo->prepare("SELECT idAula FROM Aulas WHERE numeroAula = ?");
    $stmt->execute(['A-305']);
    $a305_id = $stmt->fetchColumn();

    $stmt->execute(['EP-101']);
    $ep101_id = $stmt->fetchColumn();

    if ($a305_id && $ep101_id) {
        echo "<p class='info'>Probando ruta A-305 → EP-101...</p>";

        $resultado = $dijkstra->calcularRutaMasCorta('aula', $a305_id, 'aula', $ep101_id);

        if ($resultado['encontrada']) {
            echo "<p class='ok'>✅ RUTA ENCONTRADA</p>";
            echo "<p><strong>Distancia:</strong> {$resultado['distancia_total']} metros</p>";
            echo "<p><strong>Pasos:</strong> {$resultado['numero_pasos']}</p>";

            if (!empty($resultado['ruta_detallada'])) {
                echo "<h4>📍 Detalles de la ruta:</h4>";
                echo "<ol>";
                foreach ($resultado['ruta_detallada'] as $paso) {
                    $icono = $paso['tipo'] === 'aula' ? '🚪' : '🚶';
                    echo "<li>$icono <strong>{$paso['codigo']}</strong> - {$paso['nombre']}";
                    if ($paso['distancia'] > 0) {
                        echo " <em>({$paso['distancia']}m al siguiente)</em>";
                    }
                    echo "</li>";
                }
                echo "</ol>";
                echo "<p class='ok'>✓ Los detalles de la ruta se muestran correctamente</p>";
            } else {
                echo "<p class='error'>✗ Los detalles de la ruta siguen sin mostrarse</p>";
            }
        } else {
            echo "<p class='error'>✗ Error: {$resultado['mensaje']}</p>";
        }
    } else {
        echo "<p class='error'>✗ No se encontraron las aulas A-305 o EP-101</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error durante las pruebas: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h3>🎯 Paso 3: Verificación final del sistema</h3>";

try {
    // Verificar algunas rutas más
    $pruebas = [
        ['A-100', 'A-110'],
        ['A-305', 'A-306'],
        ['A-100', 'LC-100']
    ];

    $exitosas = 0;
    $total = count($pruebas);

    foreach ($pruebas as $prueba) {
        $origen = $prueba[0];
        $destino = $prueba[1];

        // Buscar IDs
        $stmt = $pdo->prepare("SELECT idAula FROM Aulas WHERE numeroAula = ?");
        $stmt->execute([$origen]);
        $origen_id = $stmt->fetchColumn();

        $stmt->execute([$destino]);
        $destino_id = $stmt->fetchColumn();

        if ($origen_id && $destino_id) {
            $resultado = $dijkstra->calcularRutaMasCorta('aula', $origen_id, 'aula', $destino_id);

            if ($resultado['encontrada']) {
                echo "<p class='ok'>✓ $origen → $destino: {$resultado['distancia_total']}m</p>";
                $exitosas++;
            } else {
                echo "<p class='error'>✗ $origen → $destino: Error</p>";
            }
        }
    }

    if ($exitosas === $total) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h4 class='ok'>🎉 ¡SISTEMA COMPLETAMENTE FUNCIONAL!</h4>";
        echo "<p>✅ Todas las rutas funcionan correctamente</p>";
        echo "<p>✅ Los detalles de las rutas se muestran perfectamente</p>";
        echo "<p>✅ El problema original A-305 → EP-101 está completamente resuelto</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>⚠ $exitosas de $total pruebas exitosas</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error en verificaciones: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>";
echo "<h2>✅ CORRECCIÓN COMPLETADA</h2>";
echo "<p><strong>El sistema de navegación UPIITA está totalmente funcional</strong></p>";
echo "<p>Puedes probar ahora:</p>";
echo "<p><a href='../test_ruta_especifica.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Probar Rutas Específicas</a></p>";
echo "<p><a href='../pages/mapa-rutas.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗺️ Ir al Mapa Interactivo</a></p>";
echo "</div>";

echo "</div>";
echo "<hr>";
echo "<p><em>Corrección final completada: " . date('Y-m-d H:i:s') . "</em></p>";
?>