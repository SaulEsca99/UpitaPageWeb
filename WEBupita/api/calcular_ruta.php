<?php
// Ruta: WEBupita/api/calcular_ruta.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/conexion.php';
require_once '../includes/Dijkstra.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos del POST
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        // Fallback para form data
        $origen = $_POST['origen'] ?? null;
        $destino = $_POST['destino'] ?? null;
        $guardar_favorito = $_POST['guardar_favorito'] ?? false;
        $nombre_ruta = $_POST['nombre_ruta'] ?? null;
    } else {
        $origen = $data['origen'] ?? null;
        $destino = $data['destino'] ?? null;
        $guardar_favorito = $data['guardar_favorito'] ?? false;
        $nombre_ruta = $data['nombre_ruta'] ?? null;
    }

    if (!$origen || !$destino) {
        throw new Exception('Origen y destino son requeridos');
    }

    // Parsear origen y destino
    list($origenTipo, $origenId) = explode('_', $origen);
    list($destinoTipo, $destinoId) = explode('_', $destino);

    if ($origen === $destino) {
        echo json_encode([
            'success' => false,
            'error' => 'El origen y destino no pueden ser el mismo'
        ]);
        exit;
    }

    $dijkstra = new Dijkstra($pdo);
    $resultado = $dijkstra->calcularRutaMasCorta($origenTipo, $origenId, $destinoTipo, $destinoId);

    if ($resultado['encontrada']) {
        // Si se solicita guardar como favorito y hay usuario logueado
        if ($guardar_favorito && isset($_SESSION['usuario_id']) && $nombre_ruta) {
            $dijkstra->guardarRutaFavorita(
                $_SESSION['usuario_id'],
                $origenTipo,
                $origenId,
                $destinoTipo,
                $destinoId,
                $nombre_ruta
            );
        }

        echo json_encode([
            'success' => true,
            'ruta' => $resultado
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $resultado['mensaje']
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al calcular ruta: ' . $e->getMessage()
    ]);
}
?>