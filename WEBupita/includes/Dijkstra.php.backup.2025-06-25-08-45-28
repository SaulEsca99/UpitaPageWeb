<?php
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
                $origen = $ruta['origen_tipo'] . '_' . $ruta['origen_id'];
                $destino = $ruta['destino_tipo'] . '_' . $ruta['destino_id'];
                $distancia = floatval($ruta['distancia']);

                // Agregar arista origen -> destino
                if (!isset($this->grafo[$origen])) {
                    $this->grafo[$origen] = [];
                }
                $this->grafo[$origen][$destino] = $distancia;

                // Si es bidireccional, agregar destino -> origen
                if ($ruta['es_bidireccional']) {
                    if (!isset($this->grafo[$destino])) {
                        $this->grafo[$destino] = [];
                    }
                    $this->grafo[$destino][$origen] = $distancia;
                }
            }
        } catch (Exception $e) {
            error_log('Error construyendo grafo: ' . $e->getMessage());
            throw new Exception('Error al construir el grafo de rutas');
        }
    }

    /**
     * Implementación del algoritmo de Dijkstra
     */
    public function calcularRutaMasCorta($origenTipo, $origenId, $destinoTipo, $destinoId) {
        try {
            $this->construirGrafo();

            $origen = $origenTipo . '_' . $origenId;
            $destino = $destinoTipo . '_' . $destinoId;

            // Verificar que los nodos existen en el grafo
            if (!isset($this->grafo[$origen]) && !$this->existeNodoEnDestinos($origen)) {
                return [
                    'encontrada' => false,
                    'mensaje' => 'Punto de origen no encontrado en el mapa de rutas'
                ];
            }

            if (!isset($this->grafo[$destino]) && !$this->existeNodoEnDestinos($destino)) {
                return [
                    'encontrada' => false,
                    'mensaje' => 'Punto de destino no encontrado en el mapa de rutas'
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
                    'encontrada' => false,
                    'mensaje' => 'No hay rutas disponibles en el sistema'
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
                    'encontrada' => false,
                    'mensaje' => 'No existe una ruta entre los puntos seleccionados'
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
                'encontrada' => true,
                'distancia_total' => round($distancias[$destino], 2),
                'ruta' => $ruta,
                'ruta_detallada' => $rutaDetallada,
                'numero_pasos' => count($ruta) - 1
            ];

        } catch (Exception $e) {
            error_log('Error en calcularRutaMasCorta: ' . $e->getMessage());
            return [
                'encontrada' => false,
                'mensaje' => 'Error interno al calcular la ruta'
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

        foreach ($ruta as $nodo) {
            list($tipo, $id) = explode('_', $nodo);

            try {
                if ($tipo === 'aula') {
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

                $punto = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($punto) {
                    $detalles[] = [
                        'tipo' => $tipo,
                        'id' => $id,
                        'codigo' => $punto['codigo'],
                        'nombre' => $punto['nombre'],
                        'piso' => (int)$punto['piso'],
                        'edificio' => (int)$punto['idEdificio'],
                        'edificio_nombre' => $punto['edificio_nombre'],
                        'coordenada_x' => floatval($punto['coordenada_x']),
                        'coordenada_y' => floatval($punto['coordenada_y'])
                    ];
                } else {
                    // Agregar punto genérico si no se encuentra en BD
                    $detalles[] = [
                        'tipo' => $tipo,
                        'id' => $id,
                        'codigo' => 'N/A',
                        'nombre' => 'Punto no encontrado',
                        'piso' => 0,
                        'edificio' => 0,
                        'edificio_nombre' => 'Desconocido',
                        'coordenada_x' => 0,
                        'coordenada_y' => 0
                    ];
                }
            } catch (Exception $e) {
                error_log('Error obteniendo detalles de punto ' . $nodo . ': ' . $e->getMessage());
                // Continuar con el siguiente punto
                continue;
            }
        }

        return $detalles;
    }

    /**
     * Obtiene todos los lugares disponibles para rutas
     */
    public function obtenerLugaresDisponibles() {
        try {
            $stmt = $this->pdo->query("
                SELECT tipo, id, codigo, nombre, piso, idEdificio
                FROM vista_lugares
                ORDER BY codigo
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo lugares disponibles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca lugares por término de búsqueda
     */
    public function buscarLugares($termino) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT tipo, id, codigo, nombre, piso, idEdificio
                FROM vista_lugares
                WHERE codigo LIKE ? OR nombre LIKE ?
                ORDER BY codigo
                LIMIT 50
            ");

            $termino = '%' . $termino . '%';
            $stmt->execute([$termino, $termino]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error buscando lugares: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda una ruta como favorita para un usuario
     */
    public function guardarRutaFavorita($usuarioId, $origenTipo, $origenId, $destinoTipo, $destinoId, $nombreRuta) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO RutasFavoritas (usuario_id, origen_tipo, origen_id, destino_tipo, destino_id, nombre_ruta)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([$usuarioId, $origenTipo, $origenId, $destinoTipo, $destinoId, $nombreRuta]);
        } catch (Exception $e) {
            error_log('Error guardando ruta favorita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las rutas favoritas de un usuario
     */
    public function obtenerRutasFavoritas($usuarioId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rf.*,
                       COALESCE(ao.numeroAula, po.nombre) as origen_codigo,
                       COALESCE(ao.nombreAula, po.nombre) as origen_nombre,
                       COALESCE(ad.numeroAula, pd.nombre) as destino_codigo,
                       COALESCE(ad.nombreAula, pd.nombre) as destino_nombre
                FROM RutasFavoritas rf
                LEFT JOIN Aulas ao ON rf.origen_tipo = 'aula' AND rf.origen_id = ao.idAula
                LEFT JOIN PuntosConexion po ON rf.origen_tipo = 'punto' AND rf.origen_id = po.id
                LEFT JOIN Aulas ad ON rf.destino_tipo = 'aula' AND rf.destino_id = ad.idAula
                LEFT JOIN PuntosConexion pd ON rf.destino_tipo = 'punto' AND rf.destino_id = pd.id
                WHERE rf.usuario_id = ?
                ORDER BY rf.fecha_creacion DESC
            ");

            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo rutas favoritas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Elimina una ruta favorita
     */
    public function eliminarRutaFavorita($usuarioId, $rutaId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM RutasFavoritas 
                WHERE id = ? AND usuario_id = ?
            ");

            return $stmt->execute([$rutaId, $usuarioId]);
        } catch (Exception $e) {
            error_log('Error eliminando ruta favorita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el nombre de una ruta favorita
     */
    public function actualizarNombreRutaFavorita($usuarioId, $rutaId, $nuevoNombre) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE RutasFavoritas 
                SET nombre_ruta = ? 
                WHERE id = ? AND usuario_id = ?
            ");

            return $stmt->execute([$nuevoNombre, $rutaId, $usuarioId]);
        } catch (Exception $e) {
            error_log('Error actualizando nombre de ruta favorita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas del sistema de rutas
     */
    public function obtenerEstadisticas() {
        try {
            $stats = [];

            // Total de aulas
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM Aulas");
            $stats['total_aulas'] = $stmt->fetchColumn();

            // Total de puntos de conexión
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM PuntosConexion");
            $stats['total_puntos'] = $stmt->fetchColumn();

            // Total de rutas
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM Rutas");
            $stats['total_rutas'] = $stmt->fetchColumn();

            // Total de edificios
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM Edificios");
            $stats['total_edificios'] = $stmt->fetchColumn();

            // Rutas favoritas por usuario
            $stmt = $this->pdo->query("
                SELECT u.nombre, COUNT(rf.id) as total_favoritos
                FROM usuarios u
                LEFT JOIN RutasFavoritas rf ON u.id = rf.usuario_id
                GROUP BY u.id
                ORDER BY total_favoritos DESC
                LIMIT 10
            ");
            $stats['top_usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log('Error obteniendo estadísticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida la integridad del grafo
     */
    public function validarGrafo() {
        try {
            $this->construirGrafo();

            $problemas = [];

            // Verificar nodos sin conexiones salientes
            $nodosAislados = [];
            foreach ($this->obtenerTodosLosNodos() as $nodo) {
                if (!isset($this->grafo[$nodo]) || empty($this->grafo[$nodo])) {
                    $nodosAislados[] = $nodo;
                }
            }

            if (!empty($nodosAislados)) {
                $problemas[] = 'Nodos sin conexiones salientes: ' . implode(', ', $nodosAislados);
            }

            // Verificar conexiones bidireccionales
            $conexionesUnidireccionales = [];
            foreach ($this->grafo as $origen => $vecinos) {
                foreach ($vecinos as $destino => $peso) {
                    if (!isset($this->grafo[$destino][$origen])) {
                        $conexionesUnidireccionales[] = "$origen -> $destino";
                    }
                }
            }

            if (!empty($conexionesUnidireccionales)) {
                $problemas[] = 'Conexiones unidireccionales: ' . implode(', ', $conexionesUnidireccionales);
            }

            return [
                'valido' => empty($problemas),
                'problemas' => $problemas,
                'total_nodos' => count($this->obtenerTodosLosNodos()),
                'total_conexiones' => array_sum(array_map('count', $this->grafo))
            ];

        } catch (Exception $e) {
            error_log('Error validando grafo: ' . $e->getMessage());
            return [
                'valido' => false,
                'problemas' => ['Error interno: ' . $e->getMessage()],
                'total_nodos' => 0,
                'total_conexiones' => 0
            ];
        }
    }
}
?>