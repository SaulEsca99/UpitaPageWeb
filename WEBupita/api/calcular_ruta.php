<?php
// Ruta: WEBupita/api/calcular_ruta.php
// API para calcular rutas - VERSIÓN DEBUG

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no-content-type',
    'input_raw' => file_get_contents('php://input')
];

try {
    // Verificar que los archivos existan
    if (!file_exists('../includes/conexion.php')) {
        throw new Exception('Archivo conexion.php no encontrado');
    }

    if (!file_exists('../includes/Dijkstra.php')) {
        throw new Exception('Archivo Dijkstra.php no encontrado');
    }

    require_once '../includes/conexion.php';
    require_once '../includes/Dijkstra.php';

    $debug_info['files_loaded'] = 'OK';

    // Verificar conexión a base de datos
    if (!isset($pdo)) {
        throw new Exception('Variable $pdo no está definida');
    }

    $debug_info['database_connection'] = 'OK';

    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $debug_info['json_decode_error'] = json_last_error_msg();

    if (!$input) {
        // Intentar con datos GET para debug
        if (isset($_GET['origen']) && isset($_GET['destino'])) {
            $input = [
                'origen' => $_GET['origen'],
                'destino' => $_GET['destino']
            ];
            $debug_info['using_get_params'] = true;
        } else {
            throw new Exception('No se pudieron obtener los parámetros. Raw input: ' . substr(file_get_contents('php://input'), 0, 200));
        }
    }

    if (!isset($input['origen']) || !isset($input['destino'])) {
        throw new Exception('Faltan parámetros: origen y destino son requeridos');
    }

    $origen = $input['origen'];
    $destino = $input['destino'];

    $debug_info['params'] = ['origen' => $origen, 'destino' => $destino];

    // Buscar aula de origen
    $stmt = $pdo->prepare("SELECT idAula FROM Aulas WHERE numeroAula = ? AND coordenada_x IS NOT NULL LIMIT 1");
    $stmt->execute([$origen]);
    $origen_id = $stmt->fetchColumn();

    $debug_info['origen_search'] = [
        'aula' => $origen,
        'found_id' => $origen_id,
        'query_executed' => true
    ];

    if (!$origen_id) {
        // Buscar aulas similares para debug
        $stmt = $pdo->prepare("SELECT numeroAula FROM Aulas WHERE numeroAula LIKE ? LIMIT 5");
        $stmt->execute(['%' . substr($origen, 0, 3) . '%']);
        $similares = $stmt->fetchAll(PDO::FETCH_COLUMN);

        throw new Exception("Aula de origen '$origen' no encontrada. Aulas similares: " . implode(', ', $similares));
    }

    // Buscar aula de destino
    $stmt = $pdo->prepare("SELECT idAula FROM Aulas WHERE numeroAula = ? AND coordenada_x IS NOT NULL LIMIT 1");
    $stmt->execute([$destino]);
    $destino_id = $stmt->fetchColumn();

    $debug_info['destino_search'] = [
        'aula' => $destino,
        'found_id' => $destino_id,
        'query_executed' => true
    ];

    if (!$destino_id) {
        // Buscar aulas similares para debug
        $stmt = $pdo->prepare("SELECT numeroAula FROM Aulas WHERE numeroAula LIKE ? LIMIT 5");
        $stmt->execute(['%' . substr($destino, 0, 3) . '%']);
        $similares = $stmt->fetchAll(PDO::FETCH_COLUMN);

        throw new Exception("Aula de destino '$destino' no encontrada. Aulas similares: " . implode(', ', $similares));
    }

    // Verificar que la clase Dijkstra se pueda instanciar
    try {
        $dijkstra = new Dijkstra($pdo);
        $debug_info['dijkstra_instance'] = 'OK';
    } catch (Exception $e) {
        throw new Exception('Error al crear instancia de Dijkstra: ' . $e->getMessage());
    }

    // Calcular ruta usando Dijkstra
    try {
        $resultado = $dijkstra->calcularRutaMasCorta('aula', $origen_id, 'aula', $destino_id);
        $debug_info['dijkstra_execution'] = 'OK';
    } catch (Exception $e) {
        throw new Exception('Error en cálculo Dijkstra: ' . $e->getMessage());
    }

    if ($resultado['encontrada']) {
        // Éxito - devolver ruta calculada
        echo json_encode([
            'success' => true,
            'ruta' => $resultado,
            'mensaje' => 'Ruta calculada exitosamente',
            'origen' => $origen,
            'destino' => $destino,
            'debug' => $debug_info,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // No se encontró ruta pero sin error técnico
        echo json_encode([
            'success' => false,
            'mensaje' => $resultado['mensaje'] ?? 'No se pudo encontrar una ruta entre estos puntos',
            'origen' => $origen,
            'destino' => $destino,
            'debug' => $debug_info,
            'dijkstra_result' => $resultado
        ]);
    }

} catch (Exception $e) {
    // Error en el procesamiento
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage(),
        'error' => true,
        'debug' => $debug_info,
        'trace' => $e->getTraceAsString()
    ]);
}

// Para testing directo en el navegador
if (isset($_GET['test'])) {
    echo '<h1>Test API Calcular Ruta</h1>';
    echo '<form method="GET">';
    echo '<input type="hidden" name="test" value="1">';
    echo 'Origen: <input type="text" name="origen" value="' . ($_GET['origen'] ?? 'A-305') . '"><br><br>';
    echo 'Destino: <input type="text" name="destino" value="' . ($_GET['destino'] ?? 'EP-101') . '"><br><br>';
    echo '<input type="submit" value="Probar Ruta">';
    echo '</form>';

    if (isset($_GET['origen'])) {
        echo '<hr><h2>Resultado:</h2>';
        echo '<pre style="background: #f0f0f0; padding: 10px; border-radius: 5px;">';
        // El resultado JSON ya se imprimió arriba
        echo '</pre>';
    }
}
?>