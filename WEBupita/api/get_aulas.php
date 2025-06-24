<?php
// Ruta: WEBupita/api/get_aulas.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../includes/conexion.php';

try {
    $edificio_id = $_GET['edificio_id'] ?? null;
    $piso = $_GET['piso'] ?? null;

    $sql = "SELECT a.*, e.nombre as edificio_nombre 
            FROM Aulas a 
            JOIN Edificios e ON a.idEdificio = e.idEdificio";

    $params = [];
    $conditions = [];

    if ($edificio_id) {
        $conditions[] = "a.idEdificio = ?";
        $params[] = $edificio_id;
    }

    if ($piso) {
        $conditions[] = "a.piso = ?";
        $params[] = $piso;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY a.numeroAula";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $aulas,
        'total' => count($aulas)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener aulas: ' . $e->getMessage()
    ]);
}
?>