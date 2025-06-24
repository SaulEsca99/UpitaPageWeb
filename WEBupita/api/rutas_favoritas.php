<?php
// Ruta: WEBupita/api/rutas_favoritas.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/conexion.php';
require_once '../includes/Dijkstra.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no autenticado'
    ]);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];

try {
    $dijkstra = new Dijkstra($pdo);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Obtener rutas favoritas del usuario
            $rutas = $dijkstra->obtenerRutasFavoritas($usuarioId);
            echo json_encode([
                'success' => true,
                'rutas' => $rutas
            ]);
            break;

        case 'POST':
            // Manejar diferentes acciones en POST
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) {
                // Fallback para form data
                $action = $_POST['action'] ?? 'create';
            } else {
                $action = $data['action'] ?? 'create';
            }

            if ($action === 'update_name') {
                // Actualizar nombre de ruta favorita
                $rutaId = $data['ruta_id'] ?? $_POST['ruta_id'] ?? null;
                $nombreRuta = $data['nombre_ruta'] ?? $_POST['nombre_ruta'] ?? null;

                if (!$rutaId || !$nombreRuta) {
                    throw new Exception('ID de ruta y nombre requeridos');
                }

                $stmt = $pdo->prepare("
                    UPDATE RutasFavoritas 
                    SET nombre_ruta = ? 
                    WHERE id = ? AND usuario_id = ?
                ");

                $resultado = $stmt->execute([$nombreRuta, $rutaId, $usuarioId]);

                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'mensaje' => 'Nombre de ruta actualizado exitosamente'
                    ]);
                } else {
                    throw new Exception('Error al actualizar el nombre de la ruta');
                }

            } else {
                // Agregar nueva ruta favorita
                $origenTipo = $data['origen_tipo'] ?? $_POST['origen_tipo'] ?? null;
                $origenId = $data['origen_id'] ?? $_POST['origen_id'] ?? null;
                $destinoTipo = $data['destino_tipo'] ?? $_POST['destino_tipo'] ?? null;
                $destinoId = $data['destino_id'] ?? $_POST['destino_id'] ?? null;
                $nombreRuta = $data['nombre_ruta'] ?? $_POST['nombre_ruta'] ?? null;

                if (!$origenTipo || !$origenId || !$destinoTipo || !$destinoId || !$nombreRuta) {
                    throw new Exception('Datos incompletos para guardar la ruta favorita');
                }

                $resultado = $dijkstra->guardarRutaFavorita(
                    $usuarioId,
                    $origenTipo,
                    $origenId,
                    $destinoTipo,
                    $destinoId,
                    $nombreRuta
                );

                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'mensaje' => 'Ruta favorita guardada exitosamente'
                    ]);
                } else {
                    throw new Exception('Error al guardar la ruta favorita');
                }
            }
            break;

        case 'DELETE':
            // Eliminar ruta favorita
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $rutaId = $data['ruta_id'] ?? $_GET['id'] ?? null;

            if (!$rutaId) {
                throw new Exception('ID de ruta requerido');
            }

            $stmt = $pdo->prepare("
                DELETE FROM RutasFavoritas 
                WHERE id = ? AND usuario_id = ?
            ");

            $resultado = $stmt->execute([$rutaId, $usuarioId]);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Ruta favorita eliminada exitosamente'
                ]);
            } else {
                throw new Exception('Error al eliminar la ruta favorita');
            }
            break;

        default:
            throw new Exception('Método no permitido');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en rutas favoritas: ' . $e->getMessage()
    ]);
}
?>