<?php
// Ruta: WEBupita/api/get_edificios.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/conexion.php';

try {
    // Obtener todos los edificios con información adicional
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(a.idAula) as total_aulas,
               GROUP_CONCAT(DISTINCT a.piso ORDER BY a.piso) as pisos_disponibles
        FROM Edificios e
        LEFT JOIN Aulas a ON e.idEdificio = a.idEdificio
        GROUP BY e.idEdificio
        ORDER BY e.nombre
    ");

    $edificios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar los datos para mejor formato
    foreach ($edificios as &$edificio) {
        $edificio['total_aulas'] = (int)$edificio['total_aulas'];
        $edificio['pisos_disponibles'] = $edificio['pisos_disponibles'] ?
            array_map('intval', explode(',', $edificio['pisos_disponibles'])) : [];
    }

    echo json_encode([
        'success' => true,
        'data' => $edificios,
        'total' => count($edificios)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener edificios: ' . $e->getMessage()
    ]);
}
?>