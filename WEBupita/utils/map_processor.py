#!/usr/bin/env python3
"""
Procesador de Mapa UPIITA a CSV
==============================

Este programa procesa el mapa de UPIITA y genera un CSV binario para algoritmos
de búsqueda informada y no informada.

- Caminos amarillos → 0 (blanco/transitable)
- Edificios/obstáculos → 1 (negro/no transitable)
- Puntos de interés → 2 (edificios específicos)

Ruta sugerida: WEBupita/utils/map_processor.py

Dependencias:
pip install opencv-python numpy pandas pillow matplotlib
"""

import cv2
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
from PIL import Image
import argparse
import json
from pathlib import Path


class UPIITAMapProcessor:
    def __init__(self):
        # Definir puntos de interés basados en la imagen
        self.points_of_interest = {
            'A1': {'name': 'Aulas 1', 'color': 'cyan'},
            'A2': {'name': 'Aulas 2', 'color': 'magenta'},
            'A3': {'name': 'Aulas 3', 'color': 'orange'},
            'A4': {'name': 'Aulas 4', 'color': 'green'},
            'LC': {'name': 'Laboratorios 1', 'color': 'gray'},
            'EG': {'name': 'Edificio de Gobierno', 'color': 'yellow'},
            'EP': {'name': 'Laboratorios Pesados', 'color': 'red'},
            'CAF': {'name': 'Cafetería', 'color': 'purple'},
            'CAE': {'name': 'CAE', 'color': 'white'},
            'ENTRADA': {'name': 'Entrada Principal', 'color': 'lightgray'}
        }

        # Configuración de detección de colores
        self.color_ranges = {
            'yellow_path': {
                'hsv_lower': np.array([20, 100, 100]),
                'hsv_upper': np.array([30, 255, 255])
            },
            'green_area': {
                'hsv_lower': np.array([40, 40, 40]),
                'hsv_upper': np.array([80, 255, 255])
            }
        }

    def load_and_preprocess_image(self, image_path, target_size=(200, 300)):
        """
        Carga y preprocesa la imagen del mapa.

        Args:
            image_path (str): Ruta de la imagen
            target_size (tuple): Tamaño objetivo (altura, ancho)

        Returns:
            tuple: (imagen_original, imagen_redimensionada, factor_escala)
        """
        # Cargar imagen
        image = cv2.imread(image_path)
        if image is None:
            raise ValueError(f"No se pudo cargar la imagen: {image_path}")

        # Obtener solo la región del mapa (recortar la simbología)
        # Basado en la imagen, el mapa está aproximadamente en el centro-izquierda
        height, width = image.shape[:2]

        # Definir región de interés (ROI) - aproximada del mapa real
        roi_x1 = int(width * 0.05)  # 5% desde la izquierda
        roi_x2 = int(width * 0.75)  # hasta 75% del ancho
        roi_y1 = int(height * 0.1)  # 10% desde arriba
        roi_y2 = int(height * 0.9)  # hasta 90% de la altura

        map_region = image[roi_y1:roi_y2, roi_x1:roi_x2]

        # Redimensionar para análisis
        original_height, original_width = map_region.shape[:2]
        resized_map = cv2.resize(map_region, (target_size[1], target_size[0]))

        scale_factor = {
            'x': target_size[1] / original_width,
            'y': target_size[0] / original_height
        }

        return image, resized_map, scale_factor

    def detect_yellow_paths(self, image):
        """
        Detecta los caminos amarillos en la imagen.

        Args:
            image (np.array): Imagen en formato BGR

        Returns:
            np.array: Máscara binaria de caminos amarillos
        """
        # Convertir a HSV
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Crear máscara para amarillo
        yellow_mask = cv2.inRange(hsv,
                                  self.color_ranges['yellow_path']['hsv_lower'],
                                  self.color_ranges['yellow_path']['hsv_upper'])

        # Operaciones morfológicas para limpiar la máscara
        kernel = np.ones((3, 3), np.uint8)
        yellow_mask = cv2.morphologyEx(yellow_mask, cv2.MORPH_CLOSE, kernel)
        yellow_mask = cv2.morphologyEx(yellow_mask, cv2.MORPH_OPEN, kernel)

        # Dilatar ligeramente para conectar caminos
        yellow_mask = cv2.dilate(yellow_mask, kernel, iterations=1)

        return yellow_mask

    def detect_green_areas(self, image):
        """
        Detecta áreas verdes (transitables).

        Args:
            image (np.array): Imagen en formato BGR

        Returns:
            np.array: Máscara binaria de áreas verdes
        """
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Crear máscara para verde
        green_mask = cv2.inRange(hsv,
                                 self.color_ranges['green_area']['hsv_lower'],
                                 self.color_ranges['green_area']['hsv_upper'])

        # Operaciones morfológicas
        kernel = np.ones((2, 2), np.uint8)
        green_mask = cv2.morphologyEx(green_mask, cv2.MORPH_OPEN, kernel)

        return green_mask

    def detect_buildings(self, image):
        """
        Detecta edificios basándose en colores específicos.

        Args:
            image (np.array): Imagen en formato BGR

        Returns:
            dict: Máscaras de diferentes tipos de edificios
        """
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        building_masks = {}

        # Definir rangos de colores para edificios
        building_colors = {
            'cyan_buildings': {  # A1
                'lower': np.array([85, 50, 50]),
                'upper': np.array([95, 255, 255])
            },
            'magenta_buildings': {  # A2
                'lower': np.array([140, 50, 50]),
                'upper': np.array([170, 255, 255])
            },
            'orange_buildings': {  # A3
                'lower': np.array([10, 100, 100]),
                'upper': np.array([25, 255, 255])
            },
            'red_buildings': {  # EP
                'lower': np.array([0, 100, 100]),
                'upper': np.array([10, 255, 255])
            },
            'gray_buildings': {  # LC
                'lower': np.array([0, 0, 50]),
                'upper': np.array([180, 30, 200])
            }
        }

        for building_type, color_range in building_colors.items():
            mask = cv2.inRange(hsv, color_range['lower'], color_range['upper'])

            # Operaciones morfológicas para limpiar
            kernel = np.ones((2, 2), np.uint8)
            mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel)

            building_masks[building_type] = mask

        return building_masks

    def create_binary_grid(self, image):
        """
        Crea una grilla binaria del mapa.

        Args:
            image (np.array): Imagen procesada

        Returns:
            tuple: (grilla_binaria, info_adicional)
        """
        height, width = image.shape[:2]

        # Inicializar grilla (1 = obstáculo, 0 = transitable)
        grid = np.ones((height, width), dtype=np.uint8)

        # Detectar caminos amarillos
        yellow_paths = self.detect_yellow_paths(image)

        # Detectar áreas verdes
        green_areas = self.detect_green_areas(image)

        # Detectar edificios
        building_masks = self.detect_buildings(image)

        # Crear máscara combinada de áreas transitables
        transitable = np.logical_or(yellow_paths > 0, green_areas > 0)

        # Marcar áreas transitables como 0
        grid[transitable] = 0

        # Crear grilla con información adicional
        info_grid = np.zeros((height, width), dtype=np.uint8)

        # Marcar diferentes tipos de elementos
        info_grid[yellow_paths > 0] = 1  # Caminos amarillos
        info_grid[green_areas > 0] = 2  # Áreas verdes

        # Marcar edificios con valores específicos
        building_values = {
            'cyan_buildings': 10,  # A1
            'magenta_buildings': 11,  # A2
            'orange_buildings': 12,  # A3
            'red_buildings': 13,  # EP
            'gray_buildings': 14  # LC
        }

        for building_type, value in building_values.items():
            if building_type in building_masks:
                info_grid[building_masks[building_type] > 0] = value

        return grid, info_grid

    def find_building_centers(self, building_masks, grid_shape):
        """
        Encuentra los centros de los edificios para usarlos como puntos de interés.

        Args:
            building_masks (dict): Máscaras de edificios
            grid_shape (tuple): Forma de la grilla

        Returns:
            dict: Coordenadas de centros de edificios
        """
        building_centers = {}

        building_mapping = {
            'cyan_buildings': 'A1',
            'magenta_buildings': 'A2',
            'orange_buildings': 'A3',
            'red_buildings': 'EP',
            'gray_buildings': 'LC'
        }

        for building_type, mask in building_masks.items():
            if building_type in building_mapping:
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

                        building_name = building_mapping[building_type]
                        building_centers[building_name] = {
                            'x': cx,
                            'y': cy,
                            'name': self.points_of_interest.get(building_name, {}).get('name', building_name)
                        }

        return building_centers

    def save_csv_grid(self, grid, output_path, separator=','):
        """
        Guarda la grilla como archivo CSV.

        Args:
            grid (np.array): Grilla binaria
            output_path (str): Ruta de salida
            separator (str): Separador para CSV
        """
        df = pd.DataFrame(grid)
        df.to_csv(output_path, index=False, header=False, sep=separator)
        print(f"✓ Grilla CSV guardada en: {output_path}")

    def save_metadata(self, building_centers, grid_shape, output_path):
        """
        Guarda metadata del mapa procesado.

        Args:
            building_centers (dict): Centros de edificios
            grid_shape (tuple): Forma de la grilla
            output_path (str): Ruta de salida
        """
        metadata = {
            'grid_dimensions': {
                'height': int(grid_shape[0]),
                'width': int(grid_shape[1])
            },
            'building_centers': building_centers,
            'legend': {
                '0': 'Transitable (caminos/áreas verdes)',
                '1': 'Obstáculo (edificios/no transitable)'
            },
            'points_of_interest': self.points_of_interest
        }

        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(metadata, f, indent=2, ensure_ascii=False)

        print(f"✓ Metadata guardada en: {output_path}")

    def visualize_processing_steps(self, original, processed_grid, building_centers, output_path):
        """
        Crea una visualización de los pasos de procesamiento.

        Args:
            original (np.array): Imagen original
            processed_grid (np.array): Grilla procesada
            building_centers (dict): Centros de edificios
            output_path (str): Ruta de salida
        """
        fig, axes = plt.subplots(2, 2, figsize=(15, 12))

        # Imagen original
        axes[0, 0].imshow(cv2.cvtColor(original, cv2.COLOR_BGR2RGB))
        axes[0, 0].set_title('Imagen Original')
        axes[0, 0].axis('off')

        # Grilla binaria
        axes[0, 1].imshow(processed_grid, cmap='gray')
        axes[0, 1].set_title('Grilla Binaria\n(Negro=Obstáculo, Blanco=Transitable)')
        axes[0, 1].axis('off')

        # Grilla con centros de edificios
        axes[1, 0].imshow(processed_grid, cmap='gray')
        for building, center in building_centers.items():
            axes[1, 0].plot(center['x'], center['y'], 'ro', markersize=8)
            axes[1, 0].annotate(building, (center['x'], center['y']),
                                xytext=(5, 5), textcoords='offset points',
                                color='red', fontweight='bold')
        axes[1, 0].set_title('Grilla con Centros de Edificios')
        axes[1, 0].axis('off')

        # Información de la grilla
        info_text = f"Dimensiones: {processed_grid.shape[1]} x {processed_grid.shape[0]}\n"
        info_text += f"Píxeles transitables: {np.sum(processed_grid == 0)}\n"
        info_text += f"Píxeles obstáculo: {np.sum(processed_grid == 1)}\n"
        info_text += f"Edificios detectados: {len(building_centers)}\n\n"
        info_text += "Edificios encontrados:\n"
        for building, center in building_centers.items():
            info_text += f"• {building}: ({center['x']}, {center['y']})\n"

        axes[1, 1].text(0.05, 0.95, info_text, transform=axes[1, 1].transAxes,
                        verticalalignment='top', fontfamily='monospace',
                        bbox=dict(boxstyle='round', facecolor='lightgray', alpha=0.8))
        axes[1, 1].set_title('Información del Procesamiento')
        axes[1, 1].axis('off')

        plt.tight_layout()
        plt.savefig(output_path, dpi=300, bbox_inches='tight')
        plt.close()

        print(f"✓ Visualización guardada en: {output_path}")

    def process_map(self, image_path, output_dir="output", grid_size=(100, 150)):
        """
        Procesa completamente el mapa y genera todos los archivos de salida.

        Args:
            image_path (str): Ruta de la imagen del mapa
            output_dir (str): Directorio de salida
            grid_size (tuple): Tamaño objetivo de la grilla (altura, ancho)
        """
        print(f"🗺️  Procesando mapa UPIITA: {image_path}")

        # Crear directorio de salida
        output_path = Path(output_dir)
        output_path.mkdir(exist_ok=True)

        try:
            # 1. Cargar y preprocesar imagen
            print("📸 Cargando y preprocesando imagen...")
            original, processed_image, scale_factor = self.load_and_preprocess_image(
                image_path, grid_size
            )

            # 2. Crear grilla binaria
            print("🔲 Creando grilla binaria...")
            binary_grid, info_grid = self.create_binary_grid(processed_image)

            # 3. Detectar edificios y encontrar centros
            print("🏢 Detectando edificios...")
            building_masks = self.detect_buildings(processed_image)
            building_centers = self.find_building_centers(building_masks, binary_grid.shape)

            # 4. Guardar CSV
            print("💾 Guardando archivos...")
            csv_path = output_path / "upiita_map_binary.csv"
            self.save_csv_grid(binary_grid, csv_path)

            # 5. Guardar CSV con información adicional
            info_csv_path = output_path / "upiita_map_info.csv"
            self.save_csv_grid(info_grid, info_csv_path)

            # 6. Guardar metadata
            metadata_path = output_path / "upiita_map_metadata.json"
            self.save_metadata(building_centers, binary_grid.shape, metadata_path)

            # 7. Crear visualización
            viz_path = output_path / "processing_visualization.png"
            self.visualize_processing_steps(processed_image, binary_grid,
                                            building_centers, viz_path)

            # 8. Estadísticas finales
            total_pixels = binary_grid.size
            transitable_pixels = np.sum(binary_grid == 0)
            obstacle_pixels = np.sum(binary_grid == 1)

            print(f"\n📊 Estadísticas del procesamiento:")
            print(f"   Dimensiones de grilla: {binary_grid.shape[1]} x {binary_grid.shape[0]}")
            print(f"   Píxeles transitables: {transitable_pixels} ({transitable_pixels / total_pixels * 100:.1f}%)")
            print(f"   Píxeles obstáculo: {obstacle_pixels} ({obstacle_pixels / total_pixels * 100:.1f}%)")
            print(f"   Edificios detectados: {len(building_centers)}")
            print(f"   Archivos generados en: {output_path.absolute()}")

            return True

        except Exception as e:
            print(f"❌ Error procesando mapa: {e}")
            return False


def main():
    parser = argparse.ArgumentParser(
        description="Procesa el mapa de UPIITA para algoritmos de búsqueda"
    )
    parser.add_argument("image_path", help="Ruta de la imagen del mapa")
    parser.add_argument("-o", "--output", default="map_output",
                        help="Directorio de salida (default: map_output)")
    parser.add_argument("-s", "--size", nargs=2, type=int, default=[100, 150],
                        help="Tamaño de grilla altura ancho (default: 100 150)")

    args = parser.parse_args()

    # Verificar que existe la imagen
    if not Path(args.image_path).exists():
        print(f"❌ Error: No se encuentra el archivo {args.image_path}")
        return

    # Crear procesador y procesar mapa
    processor = UPIITAMapProcessor()
    success = processor.process_map(
        args.image_path,
        args.output,
        tuple(args.size)
    )

    if success:
        print("\n✅ Procesamiento completado exitosamente!")
        print("\nArchivos generados:")
        print("  • upiita_map_binary.csv - Grilla binaria para algoritmos de búsqueda")
        print("  • upiita_map_info.csv - Grilla con información detallada")
        print("  • upiita_map_metadata.json - Metadata y puntos de interés")
        print("  • processing_visualization.png - Visualización del procesamiento")
    else:
        print("\n❌ Error en el procesamiento")


if __name__ == "__main__":
    main()