<?php
// Ruta: WEBupita/api/calcular_ruta.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        $guardar_favorito = isset($_POST['guardar_favorito']) ? (bool)$_POST['guardar_favorito'] : false;
        $nombre_ruta = $_POST['nombre_ruta'] ?? null;
    } else {
        $origen = $data['origen'] ?? null;
        $destino = $data['destino'] ?? null;
        $guardar_favorito = isset($data['guardar_favorito']) ? (bool)$data['guardar_favorito'] : false;
        $nombre_ruta = $data['nombre_ruta'] ?? null;
    }

    if (!$origen || !$destino) {
        throw new Exception('Origen y destino son requeridos');
    }

    // Validar formato de origen y destino
    if (!preg_match('/^(aula|punto)_\d+$/', $origen) || !preg_match('/^(aula|punto)_\d+$/', $destino)) {
        throw new Exception('Formato de origen o destino inválido');
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
            try {
                $guardado = $dijkstra->guardarRutaFavorita(
                    $_SESSION['usuario_id'],
                    $origenTipo,
                    $origenId,
                    $destinoTipo,
                    $destinoId,
                    trim($nombre_ruta)
                );

                if (!$guardado) {
                    // No es error crítico, solo informativo
                    error_log('No se pudo guardar la ruta favorita');
                }
            } catch (Exception $e) {
                // Log del error pero no interrumpir el flujo principal
                error_log('Error guardando ruta favorita: ' . $e->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'ruta' => $resultado,
            'favorito_guardado' => ($guardar_favorito && isset($_SESSION['usuario_id']) && $nombre_ruta)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $resultado['mensaje'] ?? 'No se pudo calcular la ruta'
        ]);
    }

} catch (Exception $e) {
    error_log('Error en calcular_ruta.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Error al calcular ruta: ' . $e->getMessage()
    ]);
}
?>