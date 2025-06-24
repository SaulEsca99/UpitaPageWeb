<?php
// Ruta: WEBupita/api/buscar_lugares.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/conexion.php';
require_once '../includes/Dijkstra.php';

try {
    $dijkstra = new Dijkstra($pdo);

    // Si hay término de búsqueda, buscar lugares específicos
    if (isset($_GET['q']) && !empty($_GET['q'])) {
        $termino = $_GET['q'];
        $lugares = $dijkstra->buscarLugares($termino);
    } else {
        // Obtener todos los lugares disponibles
        $lugares = $dijkstra->obtenerLugaresDisponibles();
    }

    // Agrupar por edificio para mejor organización
    $resultado = [];
    foreach ($lugares as $lugar) {
        $edificioId = $lugar['idEdificio'];
        if (!isset($resultado[$edificioId])) {
            // Obtener información del edificio
            $stmt = $pdo->prepare("SELECT nombre, descripcion FROM Edificios WHERE idEdificio = ?");
            $stmt->execute([$edificioId]);
            $edificio = $stmt->fetch(PDO::FETCH_ASSOC);

            $resultado[$edificioId] = [
                'edificio' => $edificio['nombre'],
                'descripcion' => $edificio['descripcion'],
                'lugares' => []
            ];
        }

        $resultado[$edificioId]['lugares'][] = [
            'tipo' => $lugar['tipo'],
            'id' => $lugar['id'],
            'codigo' => $lugar['codigo'],
            'nombre' => $lugar['nombre'],
            'piso' => $lugar['piso'],
            'valor_completo' => $lugar['tipo'] . '_' . $lugar['id']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => array_values($resultado)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al buscar lugares: ' . $e->getMessage()
    ]);
}
?>