<?php
// Ruta: WEBupita/api/mapa_coordenadas.php
// API para obtener las coordenadas reales del mapa de UPIITA

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/conexion.php';
require_once '../includes/MapaReal.php';

try {
    $mapaReal = new MapaReal($pdo);

    // Obtener coordenadas de edificios
    $edificios = $mapaReal->obtenerCoordenadasEdificios();

    // Obtener aulas organizadas por edificio
    $aulas = [];
    foreach (array_keys($edificios) as $codigoEdificio) {
        $aulas[$codigoEdificio] = $mapaReal->obtenerAulasEdificio($codigoEdificio);
    }

    // Obtener estadísticas del campus
    $estadisticas = $mapaReal->obtenerEstadisticasCampus();

    // Respuesta completa
    echo json_encode([
        'success' => true,
        'edificios' => $edificios,
        'aulas' => $aulas,
        'estadisticas' => $estadisticas,
        'metadatos' => [
            'version' => '1.0',
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
            'total_edificios' => count($edificios),
            'total_aulas' => $estadisticas['total_aulas']
        ]
    ]);

} catch (Exception $e) {
    error_log('Error en mapa_coordenadas.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener coordenadas del mapa',
        'mensaje' => $e->getMessage()
    ]);
}
?>