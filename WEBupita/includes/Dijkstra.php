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
    }

    /**
     * Implementación del algoritmo de Dijkstra
     */
    public function calcularRutaMasCorta($origenTipo, $origenId, $destinoTipo, $destinoId) {
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

        // Distancia infinita para todos los nodos excepto el origen
        foreach ($nodos as $nodo) {
            $distancias[$nodo] = PHP_FLOAT_MAX;
            $predecesores[$nodo] = null;
            $visitados[$nodo] = false;
        }
        $distancias[$origen] = 0;

        // Algoritmo de Dijkstra
        while (true) {
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
            'distancia_total' => $distancias[$destino],
            'ruta' => $ruta,
            'ruta_detallada' => $rutaDetallada,
            'numero_pasos' => count($ruta) - 1
        ];
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

            if ($tipo === 'aula') {
                $stmt = $this->pdo->prepare("
                    SELECT numeroAula as codigo, nombreAula as nombre, piso, idEdificio,
                           coordenada_x, coordenada_y
                    FROM Aulas 
                    WHERE idAula = ?
                ");
                $stmt->execute([$id]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT nombre as codigo, nombre, piso, idEdificio,
                           coordenada_x, coordenada_y
                    FROM PuntosConexion 
                    WHERE id = ?
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
                    'piso' => $punto['piso'],
                    'edificio' => $punto['idEdificio'],
                    'coordenada_x' => floatval($punto['coordenada_x']),
                    'coordenada_y' => floatval($punto['coordenada_y'])
                ];
            }
        }

        return $detalles;
    }

    /**
     * Obtiene todos los lugares disponibles para rutas
     */
    public function obtenerLugaresDisponibles() {
        $stmt = $this->pdo->query("
            SELECT tipo, id, codigo, nombre, piso, idEdificio
            FROM vista_lugares
            ORDER BY codigo
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca lugares por término de búsqueda
     */
    public function buscarLugares($termino) {
        $stmt = $this->pdo->prepare("
            SELECT tipo, id, codigo, nombre, piso, idEdificio
            FROM vista_lugares
            WHERE codigo LIKE ? OR nombre LIKE ?
            ORDER BY codigo
            LIMIT 10
        ");

        $termino = '%' . $termino . '%';
        $stmt->execute([$termino, $termino]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda una ruta como favorita para un usuario
     */
    public function guardarRutaFavorita($usuarioId, $origenTipo, $origenId, $destinoTipo, $destinoId, $nombreRuta) {
        $stmt = $this->pdo->prepare("
            INSERT INTO RutasFavoritas (usuario_id, origen_tipo, origen_id, destino_tipo, destino_id, nombre_ruta)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$usuarioId, $origenTipo, $origenId, $destinoTipo, $destinoId, $nombreRuta]);
    }

    /**
     * Obtiene las rutas favoritas de un usuario
     */
    public function obtenerRutasFavoritas($usuarioId) {
        $stmt = $this->pdo->prepare("
            SELECT rf.*, 
                   o.codigo as origen_codigo, o.nombre as origen_nombre,
                   d.codigo as destino_codigo, d.nombre as destino_nombre
            FROM RutasFavoritas rf
            LEFT JOIN vista_lugares o ON rf.origen_tipo = o.tipo AND rf.origen_id = o.id
            LEFT JOIN vista_lugares d ON rf.destino_tipo = d.tipo AND rf.destino_id = d.id
            WHERE rf.usuario_id = ?
            ORDER BY rf.fecha_creacion DESC
        ");

        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>