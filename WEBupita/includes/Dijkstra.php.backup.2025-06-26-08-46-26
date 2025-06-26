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
        
        for ($i = 0; $i < count($ruta); $i++) {
            $nodo = $ruta[$i];
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

                $info = $stmt->fetch();
                
                if ($info) {
                    // Calcular distancia al siguiente punto
                    $distancia = 0;
                    if ($i < count($ruta) - 1) {
                        $siguienteNodo = $ruta[$i + 1];
                        if (isset($this->grafo[$nodo][$siguienteNodo])) {
                            $distancia = $this->grafo[$nodo][$siguienteNodo];
                        }
                    }
                    
                    $detalles[] = [
                        'tipo' => $tipo,
                        'codigo' => $info['codigo'],
                        'nombre' => $info['nombre'],
                        'descripcion' => $info['codigo'] . ' - ' . $info['nombre'],
                        'distancia' => round($distancia, 2),
                        'piso' => $info['piso'],
                        'edificio' => $info['edificio_nombre'],
                        'coordenada_x' => $info['coordenada_x'],
                        'coordenada_y' => $info['coordenada_y']
                    ];
                }
            } catch (Exception $e) {
                error_log('Error obteniendo detalles del nodo ' . $nodo . ': ' . $e->getMessage());
                $detalles[] = [
                    'tipo' => $tipo,
                    'codigo' => 'Desconocido',
                    'nombre' => 'Punto no encontrado',
                    'descripcion' => 'Error al cargar información',
                    'distancia' => 0,
                    'piso' => 0,
                    'edificio' => 'Desconocido'
                ];
            }
        }

        return $detalles;
    }

    /**
     * Obtiene todos los lugares disponibles para búsqueda
     */
    public function obtenerLugaresDisponibles() {
        try {
            $stmt = $this->pdo->query("
                SELECT 'aula' as tipo, idAula as id, numeroAula as codigo, nombreAula as nombre, 
                       piso, idEdificio, coordenada_x, coordenada_y
                FROM Aulas 
                WHERE coordenada_x IS NOT NULL AND coordenada_y IS NOT NULL
                UNION ALL
                SELECT 'punto' as tipo, id, nombre as codigo, nombre, 
                       piso, idEdificio, coordenada_x, coordenada_y
                FROM PuntosConexion
                ORDER BY codigo
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error obteniendo lugares: ' . $e->getMessage());
            return [];
        }
    }
}
?>