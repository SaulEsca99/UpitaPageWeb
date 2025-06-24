#!/usr/bin/env python3
"""
Procesador Específico para Mapa UPIITA
======================================

Script especializado para procesar el mapa específico de UPIITA que proporcionaste.
Detecta caminos amarillos, áreas verdes y genera puntos de interés.

Ruta sugerida: WEBupita/utils/upiita_specific_processor.py

Uso:
python upiita_specific_processor.py upiita_map.png
"""

import cv2
import numpy as np
import pandas as pd
import json
import matplotlib.pyplot as plt
from pathlib import Path
import argparse


class UPIITASpecificProcessor:
    def __init__(self):
        # Coordenadas aproximadas de edificios basadas en la imagen
        # Estas coordenadas son relativas a la imagen completa
        self.building_locations = {
            'A1': {'x': 0.42, 'y': 0.58, 'name': 'Aulas 1'},
            'A2': {'x': 0.42, 'y': 0.48, 'name': 'Aulas 2'},
            'A3': {'x': 0.32, 'y': 0.68, 'name': 'Aulas 3'},
            'A4': {'x': 0.32, 'y': 0.58, 'name': 'Aulas 4'},
            'LC': {'x': 0.38, 'y': 0.55, 'name': 'Laboratorio Central'},
            'EG': {'x': 0.48, 'y': 0.55, 'name': 'Edificio de Gobierno'},
            'EP': {'x': 0.52, 'y': 0.42, 'name': 'Laboratorios Pesados'},
            'CAF': {'x': 0.45, 'y': 0.72, 'name': 'Cafetería'},
            'ENTRADA': {'x': 0.55, 'y': 0.85, 'name': 'Entrada Principal'}
        }

    def extract_map_region(self, image):
        """
        Extrae solo la región del mapa de la imagen completa.

        Args:
            image (np.array): Imagen completa

        Returns:
            np.array: Región del mapa extraída
        """
        height, width = image.shape[:2]

        # Basado en la imagen, el mapa está en la región central-izquierda
        # Ajustar estos valores según la imagen específica
        x1 = int(width * 0.05)  # Comenzar un poco desde la izquierda
        x2 = int(width * 0.78)  # Hasta antes de la simbología
        y1 = int(height * 0.12)  # Desde después del título
        y2 = int(height * 0.88)  # Hasta antes del texto inferior

        return image[y1:y2, x1:x2]

    def detect_yellow_paths(self, image):
        """
        Detecta específicamente los caminos amarillos de UPIITA.

        Args:
            image (np.array): Imagen del mapa

        Returns:
            np.array: Máscara de caminos amarillos
        """
        # Convertir a HSV para mejor detección de amarillo
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Rango específico para el amarillo de los caminos UPIITA
        # Ajustado para capturar el amarillo específico de la imagen
        lower_yellow = np.array([18, 120, 120])
        upper_yellow = np.array([32, 255, 255])

        yellow_mask = cv2.inRange(hsv, lower_yellow, upper_yellow)

        # Operaciones morfológicas para limpiar y conectar caminos
        kernel_small = np.ones((2, 2), np.uint8)
        kernel_medium = np.ones((3, 3), np.uint8)

        # Cerrar pequeños huecos
        yellow_mask = cv2.morphologyEx(yellow_mask, cv2.MORPH_CLOSE, kernel_medium)

        # Remover ruido pequeño
        yellow_mask = cv2.morphologyEx(yellow_mask, cv2.MORPH_OPEN, kernel_small)

        # Dilatar ligeramente para asegurar conectividad
        yellow_mask = cv2.dilate(yellow_mask, kernel_small, iterations=1)

        return yellow_mask

    def detect_green_areas(self, image):
        """
        Detecta áreas verdes transitables.

        Args:
            image (np.array): Imagen del mapa

        Returns:
            np.array: Máscara de áreas verdes
        """
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Rango para áreas verdes claras (césped, jardines)
        lower_green = np.array([35, 30, 30])
        upper_green = np.array([85, 200, 200])

        green_mask = cv2.inRange(hsv, lower_green, upper_green)

        # Limpiar máscara
        kernel = np.ones((2, 2), np.uint8)
        green_mask = cv2.morphologyEx(green_mask, cv2.MORPH_OPEN, kernel)

        return green_mask

    def detect_buildings(self, image):
        """
        Detecta edificios por color.

        Args:
            image (np.array): Imagen del mapa

        Returns:
            dict: Máscaras de diferentes edificios
        """
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Definir rangos de colores para cada edificio
        building_colors = {
            'cyan': np.array([[80, 100, 100], [100, 255, 255]]),  # A1
            'magenta': np.array([[140, 100, 100], [170, 255, 255]]),  # A2
            'orange': np.array([[8, 100, 100], [20, 255, 255]]),  # A3
            'green': np.array([[50, 100, 100], [70, 255, 255]]),  # A4
            'gray': np.array([[0, 0, 50], [180, 30, 150]]),  # LC
            'red': np.array([[0, 100, 100], [10, 255, 255]])  # EP
        }

        building_masks = {}
        for color_name, (lower, upper) in building_colors.items():
            mask = cv2.inRange(hsv, lower, upper)

            # Limpiar máscara
            kernel = np.ones((3, 3), np.uint8)
            mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel)
            mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel)

            building_masks[color_name] = mask

        return building_masks

    def create_binary_grid(self, image, grid_size=(80, 120)):
        """
        Crea grilla binaria optimizada para UPIITA.

        Args:
            image (np.array): Imagen del mapa
            grid_size (tuple): Tamaño de la grilla (altura, ancho)

        Returns:
            tuple: (grilla_binaria, información_adicional)
        """
        # Redimensionar imagen al tamaño de grilla
        resized = cv2.resize(image, (grid_size[1], grid_size[0]))

        # Detectar elementos
        yellow_paths = self.detect_yellow_paths(resized)
        green_areas = self.detect_green_areas(resized)
        building_masks = self.detect_buildings(resized)

        # Inicializar grilla (1 = obstáculo, 0 = transitable)
        binary_grid = np.ones(grid_size, dtype=np.uint8)

        # Marcar áreas transitables
        transitable_mask = np.logical_or(yellow_paths > 0, green_areas > 0)
        binary_grid[transitable_mask] = 0

        # Información adicional para debugging
        info_grid = np.zeros(grid_size, dtype=np.uint8)
        info_grid[yellow_paths > 0] = 1  # Caminos amarillos
        info_grid[green_areas > 0] = 2  # Áreas verdes

        # Marcar edificios
        building_map = {
            'cyan': 10, 'magenta': 11, 'orange': 12,
            'green': 13, 'gray': 14, 'red': 15
        }

        for color, value in building_map.items():
            if color in building_masks:
                info_grid[building_masks[color] > 0] = value

        return binary_grid, info_grid, building_masks

    def find_building_centers(self, building_masks, grid_size):
        """
        Encuentra centros de edificios para puntos de interés.

        Args:
            building_masks (dict): Máscaras de edificios
            grid_size (tuple): Tamaño de la grilla

        Returns:
            dict: Coordenadas de centros de edificios
        """
        building_centers = {}

        # Mapeo de colores a edificios
        color_to_building = {
            'cyan': 'A1',
            'magenta': 'A2',
            'orange': 'A3',
            'green': 'A4',
            'gray': 'LC',
            'red': 'EP'
        }

        for color, mask in building_masks.items():
            if color in color_to_building:
                # Encontrar contornos
                contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

                if contours:
                    # Obtener el contorno más grande
                    largest_contour = max(contours, key=cv2.contourArea)

                    # Calcular centroide
                    M = cv2.moments(largest_contour)
                    if M["m00"] != 0:
                        cx = int(M["m10"] / M["m00"])
                        cy = int(M["m01"] / M["m00"])

                        building_id = color_to_building[color]
                        building_centers[building_id] = {
                            'x': cx,
                            'y': cy,
                            'name': self.building_locations[building_id]['name']
                        }

        # Agregar puntos calculados de ubicaciones aproximadas si no se detectaron
        for building_id, location in self.building_locations.items():
            if building_id not in building_centers:
                x = int(location['x'] * grid_size[1])
                y = int(location['y'] * grid_size[0])
                building_centers[building_id] = {
                    'x': x,
                    'y': y,
                    'name': location['name']
                }

        return building_centers

    def generate_pathfinding_scenarios(self, building_centers, grid):
        """
        Genera escenarios específicos de pathfinding para UPIITA.

        Args:
            building_centers (dict): Centros de edificios
            grid (np.array): Grilla binaria

        Returns:
            dict: Escenarios de pathfinding
        """
        scenarios = {
            'entrada_a_A1': {
                'description': 'Entrada principal a Aulas 1',
                'start': None,
                'goal': None
            },
            'A1_a_LC': {
                'description': 'Aulas 1 a Laboratorio Central',
                'start': None,
                'goal': None
            },
            'LC_a_EG': {
                'description': 'Laboratorio Central a Edificio de Gobierno',
                'start': None,
                'goal': None
            },
            'A3_a_CAF': {
                'description': 'Aulas 3 a Cafetería',
                'start': None,
                'goal': None
            },
            'EG_a_EP': {
                'description': 'Edificio de Gobierno a Laboratorios Pesados',
                'start': None,
                'goal': None
            }
        }

        # Mapear escenarios a edificios
        scenario_mapping = {
            'entrada_a_A1': ('ENTRADA', 'A1'),
            'A1_a_LC': ('A1', 'LC'),
            'LC_a_EG': ('LC', 'EG'),
            'A3_a_CAF': ('A3', 'CAF'),
            'EG_a_EP': ('EG', 'EP')
        }

        for scenario_name, (start_building, goal_building) in scenario_mapping.items():
            if start_building in building_centers and goal_building in building_centers:
                start_center = building_centers[start_building]
                goal_center = building_centers[goal_building]

                # Verificar que los puntos sean transitables
                start_y, start_x = start_center['y'], start_center['x']
                goal_y, goal_x = goal_center['y'], goal_center['x']

                # Ajustar puntos si están en obstáculos
                start_y, start_x = self.find_nearest_transitable(grid, start_y, start_x)
                goal_y, goal_x = self.find_nearest_transitable(grid, goal_y, goal_x)

                scenarios[scenario_name]['start'] = [start_y, start_x]
                scenarios[scenario_name]['goal'] = [goal_y, goal_x]

        return scenarios

    def find_nearest_transitable(self, grid, y, x, max_radius=5):
        """
        Encuentra la celda transitable más cercana a una coordenada.

        Args:
            grid (np.array): Grilla binaria
            y, x (int): Coordenadas originales
            max_radius (int): Radio máximo de búsqueda

        Returns:
            tuple: Coordenadas de celda transitable más cercana
        """
        height, width = grid.shape

        # Si la celda original es transitable, devolverla
        if 0 <= y < height and 0 <= x < width and grid[y, x] == 0:
            return y, x

        # Buscar en círculos concéntricos
        for radius in range(1, max_radius + 1):
            for dy in range(-radius, radius + 1):
                for dx in range(-radius, radius + 1):
                    if abs(dy) == radius or abs(dx) == radius:  # Solo en el borde del círculo
                        ny, nx = y + dy, x + dx
                        if 0 <= ny < height and 0 <= nx < width and grid[ny, nx] == 0:
                            return ny, nx

        # Si no se encuentra, devolver la original
        return y, x

    def visualize_result(self, original_image, binary_grid, building_centers, scenarios):
        """
        Crea visualización completa del resultado.

        Args:
            original_image (np.array): Imagen original
            binary_grid (np.array): Grilla binaria
            building_centers (dict): Centros de edificios
            scenarios (dict): Escenarios de pathfinding
        """
        fig, axes = plt.subplots(2, 2, figsize=(16, 12))

        # Imagen original
        axes[0, 0].imshow(cv2.cvtColor(original_image, cv2.COLOR_BGR2RGB))
        axes[0, 0].set_title('Imagen Original del Mapa UPIITA')
        axes[0, 0].axis('off')

        # Grilla binaria
        axes[0, 1].imshow(binary_grid, cmap='gray')
        axes[0, 1].set_title('Grilla Binaria\n(Blanco=Transitable, Negro=Obstáculo)')
        axes[0, 1].grid(True, alpha=0.3)

        # Grilla con edificios marcados
        display_grid = binary_grid.copy().astype(float)
        axes[1, 0].imshow(display_grid, cmap='gray')

        # Marcar centros de edificios
        colors = ['red', 'blue', 'green', 'orange', 'purple', 'brown', 'pink', 'cyan', 'yellow']
        for i, (building, center) in enumerate(building_centers.items()):
            color = colors[i % len(colors)]
            axes[1, 0].plot(center['x'], center['y'], 'o', color=color, markersize=8, markeredgecolor='white',
                            markeredgewidth=2)
            axes[1, 0].annotate(building, (center['x'], center['y']),
                                xytext=(5, 5), textcoords='offset points',
                                color=color, fontweight='bold', fontsize=10,
                                bbox=dict(boxstyle='round,pad=0.3', facecolor='white', alpha=0.8))

        axes[1, 0].set_title('Grilla con Edificios Identificados')
        axes[1, 0].grid(True, alpha=0.3)
        axes[1, 0].set_xlabel('X (columnas)')
        axes[1, 0].set_ylabel('Y (filas)')

        # Escenarios de pathfinding
        axes[1, 1].imshow(display_grid, cmap='gray')

        # Mostrar algunos escenarios
        scenario_colors = ['red', 'blue', 'green', 'purple', 'orange']
        valid_scenarios = {k: v for k, v in scenarios.items() if v['start'] is not None and v['goal'] is not None}

        for i, (name, scenario) in enumerate(list(valid_scenarios.items())[:5]):
            color = scenario_colors[i % len(scenario_colors)]
            start_y, start_x = scenario['start']
            goal_y, goal_x = scenario['goal']

            # Marcar inicio y meta
            axes[1, 1].plot(start_x, start_y, 's', color=color, markersize=10, markeredgecolor='white',
                            markeredgewidth=2, label=f'{name} (inicio)')
            axes[1, 1].plot(goal_x, goal_y, '^', color=color, markersize=10, markeredgecolor='white', markeredgewidth=2,
                            label=f'{name} (meta)')

            # Línea directa (no es el camino real, solo referencia visual)
            axes[1, 1].plot([start_x, goal_x], [start_y, goal_y], '--', color=color, alpha=0.6, linewidth=2)

        axes[1, 1].set_title('Escenarios de Pathfinding')
        axes[1, 1].grid(True, alpha=0.3)
        axes[1, 1].set_xlabel('X (columnas)')
        axes[1, 1].set_ylabel('Y (filas)')
        axes[1, 1].legend(bbox_to_anchor=(1.05, 1), loc='upper left', fontsize=8)

        plt.tight_layout()
        return fig

    def process_upiita_map(self, image_path, output_dir="upiita_output", grid_size=(80, 120)):
        """
        Procesa completamente el mapa de UPIITA.

        Args:
            image_path (str): Ruta de la imagen
            output_dir (str): Directorio de salida
            grid_size (tuple): Tamaño de la grilla

        Returns:
            dict: Información del procesamiento
        """
        print(f"🏫 Procesando mapa específico de UPIITA...")
        print(f"   Imagen: {image_path}")
        print(f"   Tamaño de grilla: {grid_size[1]}x{grid_size[0]}")

        # Crear directorio de salida
        output_path = Path(output_dir)
        output_path.mkdir(exist_ok=True)

        try:
            # 1. Cargar imagen
            print("📖 Cargando imagen...")
            image = cv2.imread(image_path)
            if image is None:
                raise ValueError(f"No se pudo cargar la imagen: {image_path}")

            # 2. Extraer región del mapa
            print("✂️  Extrayendo región del mapa...")
            map_region = self.extract_map_region(image)

            # 3. Crear grilla binaria
            print("🔲 Creando grilla binaria...")
            binary_grid, info_grid, building_masks = self.create_binary_grid(map_region, grid_size)

            # 4. Encontrar centros de edificios
            print("🏢 Identificando edificios...")
            building_centers = self.find_building_centers(building_masks, grid_size)

            # 5. Generar escenarios de pathfinding
            print("🎯 Generando escenarios de pathfinding...")
            scenarios = self.generate_pathfinding_scenarios(building_centers, binary_grid)

            # 6. Guardar archivos
            print("💾 Guardando archivos...")

            # CSV principal
            csv_path = output_path / "upiita_binary_grid.csv"
            pd.DataFrame(binary_grid).to_csv(csv_path, index=False, header=False)

            # CSV con información detallada
            info_csv_path = output_path / "upiita_detailed_grid.csv"
            pd.DataFrame(info_grid).to_csv(info_csv_path, index=False, header=False)

            # Metadata completa
            metadata = {
                'grid_info': {
                    'dimensions': {'width': grid_size[1], 'height': grid_size[0]},
                    'total_cells': binary_grid.size,
                    'transitable_cells': int(np.sum(binary_grid == 0)),
                    'obstacle_cells': int(np.sum(binary_grid == 1)),
                    'transitable_percentage': float(np.sum(binary_grid == 0) / binary_grid.size * 100)
                },
                'buildings': building_centers,
                'pathfinding_scenarios': scenarios,
                'legend': {
                    'binary_grid': {
                        '0': 'Transitable (caminos amarillos, áreas verdes)',
                        '1': 'Obstáculo (edificios, áreas no transitables)'
                    },
                    'detailed_grid': {
                        '0': 'Obstáculo',
                        '1': 'Camino amarillo',
                        '2': 'Área verde',
                        '10': 'Edificio A1 (cyan)',
                        '11': 'Edificio A2 (magenta)',
                        '12': 'Edificio A3 (orange)',
                        '13': 'Edificio A4 (green)',
                        '14': 'Laboratorio Central (gray)',
                        '15': 'Laboratorios Pesados (red)'
                    }
                }
            }

            metadata_path = output_path / "upiita_metadata.json"
            with open(metadata_path, 'w', encoding='utf-8') as f:
                json.dump(metadata, f, indent=2, ensure_ascii=False)

            # Generar código de ejemplo para pathfinding
            example_code = self.generate_example_code(scenarios, grid_size)
            example_path = output_path / "pathfinding_example.py"
            with open(example_path, 'w', encoding='utf-8') as f:
                f.write(example_code)

            # README con instrucciones
            readme_content = self.generate_readme(metadata, scenarios)
            readme_path = output_path / "README.md"
            with open(readme_path, 'w', encoding='utf-8') as f:
                f.write(readme_content)

            # 7. Crear visualización
            print("📊 Creando visualización...")
            fig = self.visualize_result(map_region, binary_grid, building_centers, scenarios)
            viz_path = output_path / "upiita_processing_result.png"
            fig.savefig(viz_path, dpi=300, bbox_inches='tight')
            plt.close(fig)

            # 8. Estadísticas finales
            total_cells = binary_grid.size
            transitable_cells = np.sum(binary_grid == 0)

            print(f"\n📊 Estadísticas del procesamiento:")
            print(f"   ✓ Grilla: {grid_size[1]}x{grid_size[0]} ({total_cells} celdas)")
            print(f"   ✓ Celdas transitables: {transitable_cells} ({transitable_cells / total_cells * 100:.1f}%)")
            print(f"   ✓ Edificios identificados: {len(building_centers)}")
            print(f"   ✓ Escenarios de pathfinding: {len([s for s in scenarios.values() if s['start'] is not None])}")
            print(f"   ✓ Archivos generados en: {output_path.absolute()}")

            return {
                'success': True,
                'binary_grid': binary_grid,
                'building_centers': building_centers,
                'scenarios': scenarios,
                'metadata': metadata,
                'output_directory': str(output_path.absolute())
            }

        except Exception as e:
            print(f"❌ Error procesando mapa: {e}")
            return {'success': False, 'error': str(e)}

    def generate_example_code(self, scenarios, grid_size):
        """
        Genera código de ejemplo para usar con algoritmos de pathfinding.

        Args:
            scenarios (dict): Escenarios de pathfinding
            grid_size (tuple): Tamaño de la grilla

        Returns:
            str: Código de ejemplo
        """
        valid_scenarios = {k: v for k, v in scenarios.items() if v['start'] is not None and v['goal'] is not None}

        code = '''#!/usr/bin/env python3
"""
Ejemplo de uso del mapa UPIITA con algoritmos de pathfinding
===========================================================

Este archivo muestra cómo cargar y usar el mapa procesado de UPIITA
con diferentes algoritmos de búsqueda.

Dependencias:
pip install numpy pandas matplotlib
"""

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
from collections import deque
import heapq
import json

class UPIITAPathfinder:
    def __init__(self, csv_path="upiita_binary_grid.csv"):
        """Inicializa el pathfinder con el mapa de UPIITA."""
        self.grid = pd.read_csv(csv_path, header=None).values
        self.height, self.width = self.grid.shape

        # Cargar metadata si está disponible
        try:
            with open("upiita_metadata.json", 'r') as f:
                self.metadata = json.load(f)
        except FileNotFoundError:
            self.metadata = None

    def get_neighbors(self, y, x):
        """Obtiene vecinos válidos de una celda."""
        neighbors = []
        # Movimientos: arriba, abajo, izquierda, derecha
        directions = [(-1, 0), (1, 0), (0, -1), (0, 1)]

        for dy, dx in directions:
            ny, nx = y + dy, x + dx
            if (0 <= ny < self.height and 0 <= nx < self.width and 
                self.grid[ny, nx] == 0):  # 0 = transitable
                neighbors.append((ny, nx))

        return neighbors

    def bfs(self, start, goal):
        """
        Búsqueda en anchura (BFS) - búsqueda no informada.

        Args:
            start (tuple): Coordenadas de inicio (y, x)
            goal (tuple): Coordenadas de meta (y, x)

        Returns:
            tuple: (camino, nodos_expandidos)
        """
        if start == goal:
            return [start], 1

        queue = deque([(start, [start])])
        visited = {start}
        nodes_expanded = 0

        while queue:
            (y, x), path = queue.popleft()
            nodes_expanded += 1

            for ny, nx in self.get_neighbors(y, x):
                if (ny, nx) == goal:
                    return path + [(ny, nx)], nodes_expanded + 1

                if (ny, nx) not in visited:
                    visited.add((ny, nx))
                    queue.append(((ny, nx), path + [(ny, nx)]))

        return None, nodes_expanded  # No se encontró camino

    def manhattan_distance(self, pos1, pos2):
        """Calcula distancia Manhattan entre dos posiciones."""
        return abs(pos1[0] - pos2[0]) + abs(pos1[1] - pos2[1])

    def a_star(self, start, goal):
        """
        Algoritmo A* - búsqueda informada.

        Args:
            start (tuple): Coordenadas de inicio (y, x)
            goal (tuple): Coordenadas de meta (y, x)

        Returns:
            tuple: (camino, nodos_expandidos)
        """
        if start == goal:
            return [start], 1

        open_set = [(0, start, [start])]  # (f_score, posición, camino)
        g_scores = {start: 0}
        nodes_expanded = 0

        while open_set:
            f_score, (y, x), path = heapq.heappop(open_set)
            nodes_expanded += 1

            if (y, x) == goal:
                return path, nodes_expanded

            for ny, nx in self.get_neighbors(y, x):
                tentative_g = g_scores[(y, x)] + 1

                if (ny, nx) not in g_scores or tentative_g < g_scores[(ny, nx)]:
                    g_scores[(ny, nx)] = tentative_g
                    h_score = self.manhattan_distance((ny, nx), goal)
                    f_score = tentative_g + h_score

                    new_path = path + [(ny, nx)]
                    heapq.heappush(open_set, (f_score, (ny, nx), new_path))

        return None, nodes_expanded  # No se encontró camino

    def visualize_path(self, path, start, goal, title="Camino encontrado"):
        """Visualiza un camino en el mapa."""
        fig, ax = plt.subplots(figsize=(12, 8))

        # Mostrar grilla
        ax.imshow(self.grid, cmap='gray', alpha=0.8)

        # Mostrar camino
        if path:
            path_y = [pos[0] for pos in path]
            path_x = [pos[1] for pos in path]
            ax.plot(path_x, path_y, 'b-', linewidth=3, label='Camino')

            # Marcar inicio y meta
            ax.plot(start[1], start[0], 'go', markersize=10, label='Inicio')
            ax.plot(goal[1], goal[0], 'ro', markersize=10, label='Meta')

        ax.set_title(title)
        ax.legend()
        ax.grid(True, alpha=0.3)
        ax.set_xlabel('X (columnas)')
        ax.set_ylabel('Y (filas)')

        return fig

def main():
    """Función principal de ejemplo."""
    print("🏫 Ejemplo de Pathfinding en UPIITA")

    # Inicializar pathfinder
    pathfinder = UPIITAPathfinder()
    print(f"   Mapa cargado: {pathfinder.width}x{pathfinder.height}")

    # Escenarios de prueba específicos de UPIITA
    scenarios = {
'''

        # Agregar escenarios específicos
        for name, scenario in valid_scenarios.items():
            if scenario['start'] and scenario['goal']:
                start = scenario['start']
                goal = scenario['goal']
                code += f'''        '{name}': {{
            'description': '{scenario["description"]}',
            'start': ({start[0]}, {start[1]}),
            'goal': ({goal[0]}, {goal[1]})
        }},
'''

        code += '''    }

    # Probar diferentes algoritmos
    for scenario_name, scenario in scenarios.items():
        print(f"\\n🎯 Probando: {scenario['description']}")
        start = scenario['start']
        goal = scenario['goal']

        # BFS (búsqueda no informada)
        path_bfs, nodes_bfs = pathfinder.bfs(start, goal)

        # A* (búsqueda informada)
        path_astar, nodes_astar = pathfinder.a_star(start, goal)

        if path_bfs and path_astar:
            print(f"   BFS: {len(path_bfs)} pasos, {nodes_bfs} nodos expandidos")
            print(f"   A*:  {len(path_astar)} pasos, {nodes_astar} nodos expandidos")
            print(f"   Eficiencia A*: {nodes_bfs/nodes_astar:.2f}x menos nodos")

            # Visualizar resultado de A*
            fig = pathfinder.visualize_path(
                path_astar, start, goal, 
                f"A* - {scenario['description']}"
            )
            plt.savefig(f"path_{scenario_name}.png", dpi=150, bbox_inches='tight')
            plt.close(fig)
        else:
            print("   ❌ No se encontró camino")

if __name__ == "__main__":
    main()
'''

        return code

    def generate_readme(self, metadata, scenarios):
        """
        Genera README con documentación completa.

        Args:
            metadata (dict): Metadata del mapa
            scenarios (dict): Escenarios de pathfinding

        Returns:
            str: Contenido del README
        """
        grid_info = metadata['grid_info']
        valid_scenarios = {k: v for k, v in scenarios.items() if v['start'] is not None and v['goal'] is not None}

        readme = f'''# Mapa UPIITA para Algoritmos de Pathfinding

Este directorio contiene el mapa de la Unidad Profesional Interdisciplinaria en Ingeniería y Tecnologías Avanzadas (UPIITA) procesado para su uso con algoritmos de búsqueda informada y no informada.

## Archivos Generados

### Archivos Principales
- `upiita_binary_grid.csv` - Grilla binaria para algoritmos de pathfinding
- `upiita_detailed_grid.csv` - Grilla con información detallada por tipo de área
- `upiita_metadata.json` - Metadata completa del mapa y puntos de interés
- `pathfinding_example.py` - Código de ejemplo para usar el mapa
- `upiita_processing_result.png` - Visualización del procesamiento

### Información del Mapa
- **Dimensiones**: {grid_info['dimensions']['width']} × {grid_info['dimensions']['height']} celdas
- **Total de celdas**: {grid_info['total_cells']:,}
- **Celdas transitables**: {grid_info['transitable_cells']:,} ({grid_info['transitable_percentage']:.1f}%)
- **Celdas obstáculo**: {grid_info['obstacle_cells']:,} ({100 - grid_info['transitable_percentage']:.1f}%)

## Formato de la Grilla

### Grilla Binaria (`upiita_binary_grid.csv`)
- `0` = **Transitable** (caminos amarillos, áreas verdes)
- `1` = **Obstáculo** (edificios, áreas no transitables)

### Grilla Detallada (`upiita_detailed_grid.csv`)
- `0` = Obstáculo
- `1` = Camino amarillo
- `2` = Área verde
- `10` = Edificio A1 (cyan)
- `11` = Edificio A2 (magenta)  
- `12` = Edificio A3 (orange)
- `13` = Edificio A4 (green)
- `14` = Laboratorio Central (gray)
- `15` = Laboratorios Pesados (red)

## Edificios Identificados

'''

        # Agregar información de edificios
        buildings = metadata.get('buildings', {})
        for building_id, info in buildings.items():
            readme += f"- **{building_id}**: {info['name']} - Posición ({info['x']}, {info['y']})\n"

        readme += f'''

## Escenarios de Pathfinding

Se han definido {len(valid_scenarios)} escenarios específicos para testing:

'''

        # Agregar escenarios
        for name, scenario in valid_scenarios.items():
            start = scenario['start']
            goal = scenario['goal']
            readme += f'''### {scenario['description']}
- **Inicio**: ({start[1]}, {start[0]}) - Formato (x, y)
- **Meta**: ({goal[1]}, {goal[0]}) - Formato (x, y)
- **Array**: [{start[0]}, {start[1]}] → [{goal[0]}, {goal[1]}] - Formato [y, x]

'''

        readme += '''## Uso Básico

### Cargar el mapa
```python
import pandas as pd
import numpy as np

# Cargar grilla binaria
grid = pd.read_csv('upiita_binary_grid.csv', header=None).values
height, width = grid.shape
```

### Verificar si una celda es transitable
```python
def is_transitable(grid, y, x):
    return 0 <= y < grid.shape[0] and 0 <= x < grid.shape[1] and grid[y, x] == 0
```

### Obtener vecinos de una celda
```python
def get_neighbors(grid, y, x):
    neighbors = []
    directions = [(-1, 0), (1, 0), (0, -1), (0, 1)]  # arriba, abajo, izquierda, derecha

    for dy, dx in directions:
        ny, nx = y + dy, x + dx
        if is_transitable(grid, ny, nx):
            neighbors.append((ny, nx))

    return neighbors
```

## Algoritmos Recomendados

### Búsqueda No Informada
- **BFS (Breadth-First Search)**: Garantiza el camino más corto en términos de número de pasos
- **DFS (Depth-First Search)**: Útil para exploración, no garantiza camino óptimo
- **Dijkstra**: Camino más corto considerando costos (útil si se agregan costos por tipo de terreno)

### Búsqueda Informada
- **A*** (A-star): Búsqueda óptima con heurística (recomendado: distancia Manhattan)
- **Greedy Best-First**: Más rápido pero no garantiza óptimo
- **IDA*** (Iterative Deepening A*): Para casos con memoria limitada

## Ejemplo de Implementación A*

```python
import heapq

def manhattan_distance(pos1, pos2):
    return abs(pos1[0] - pos2[0]) + abs(pos1[1] - pos2[1])

def a_star(grid, start, goal):
    open_set = [(0, start, [start])]
    g_scores = {start: 0}

    while open_set:
        f_score, current, path = heapq.heappop(open_set)

        if current == goal:
            return path

        for neighbor in get_neighbors(grid, current[0], current[1]):
            tentative_g = g_scores[current] + 1

            if neighbor not in g_scores or tentative_g < g_scores[neighbor]:
                g_scores[neighbor] = tentative_g
                h_score = manhattan_distance(neighbor, goal)
                f_score = tentative_g + h_score

                heapq.heappush(open_set, (f_score, neighbor, path + [neighbor]))

    return None  # No path found
```

## Visualización

Para visualizar resultados:

```python
import matplotlib.pyplot as plt

def visualize_path(grid, path, start, goal):
    plt.figure(figsize=(12, 8))
    plt.imshow(grid, cmap='gray', alpha=0.8)

    if path:
        path_y = [pos[0] for pos in path]
        path_x = [pos[1] for pos in path]
        plt.plot(path_x, path_y, 'b-', linewidth=2, label='Camino')

    plt.plot(start[1], start[0], 'go', markersize=10, label='Inicio')
    plt.plot(goal[1], goal[0], 'ro', markersize=10, label='Meta')

    plt.legend()
    plt.grid(True, alpha=0.3)
    plt.xlabel('X (columnas)')
    plt.ylabel('Y (filas)')
    plt.title('Pathfinding en UPIITA')
    plt.show()
```

## Notas Importantes

1. **Sistema de Coordenadas**: 
   - Los arrays usan formato [y, x] (fila, columna)
   - Las visualizaciones usan formato (x, y) (columna, fila)

2. **Conectividad**: Solo se permiten movimientos ortogonales (arriba, abajo, izquierda, derecha)

3. **Optimización**: Para mapas grandes, considerar:
   - Hierarchical pathfinding
   - Jump Point Search (JPS)
   - Precomputed paths

4. **Extensiones Posibles**:
   - Agregar costos por tipo de terreno
   - Implementar movimientos diagonales
   - Añadir obstáculos dinámicos

## Dependencias

```bash
pip install numpy pandas matplotlib
```

## Créditos

Mapa generado automáticamente desde la imagen oficial de UPIITA-IPN.
Procesado con algoritmos de visión computacional para extracción de caminos y edificios.
'''

        return readme


def main():
    parser = argparse.ArgumentParser(
        description="Procesador específico para el mapa de UPIITA",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Ejemplos de uso:
  python upiita_specific_processor.py upiita_map.png
  python upiita_specific_processor.py upiita_map.png -o mi_output -s 100 150
  python upiita_specific_processor.py upiita_map.png --preview
        """
    )

    parser.add_argument("image_path", help="Ruta de la imagen del mapa de UPIITA")
    parser.add_argument("-o", "--output", default="upiita_output",
                        help="Directorio de salida (default: upiita_output)")
    parser.add_argument("-s", "--size", nargs=2, type=int, default=[80, 120],
                        help="Tamaño de grilla [altura ancho] (default: 80 120)")
    parser.add_argument("--preview", action='store_true',
                        help="Mostrar preview del procesamiento")

    args = parser.parse_args()

    if not Path(args.image_path).exists():
        print(f"❌ Error: No se encuentra el archivo {args.image_path}")
        return

    # Crear procesador y procesar mapa
    processor = UPIITASpecificProcessor()
    result = processor.process_upiita_map(
        args.image_path,
        args.output,
        tuple(args.size)
    )

    if result['success']:
        print(f"\n✅ ¡Procesamiento completado exitosamente!")
        print(f"\n📁 Archivos generados:")
        print(f"   • upiita_binary_grid.csv - Grilla principal para pathfinding")
        print(f"   • upiita_detailed_grid.csv - Grilla con información detallada")
        print(f"   • upiita_metadata.json - Metadata y puntos de interés")
        print(f"   • pathfinding_example.py - Código de ejemplo")
        print(f"   • README.md - Documentación completa")
        print(f"   • upiita_processing_result.png - Visualización del resultado")
        print(f"\n🚀 ¡Listo para usar con algoritmos de búsqueda!")

        if args.preview:
            plt.show()
    else:
        print(f"\n❌ Error en el procesamiento: {result.get('error', 'Error desconocido')}")


if __name__ == "__main__":
    main()