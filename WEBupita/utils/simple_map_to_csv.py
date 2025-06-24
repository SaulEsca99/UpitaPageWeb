#!/usr/bin/env python3
"""
Conversor Simple de Mapa a CSV
=============================

Script simple para convertir cualquier imagen de mapa a CSV binario.
Amarillo/claro = 0 (transitable), Oscuro = 1 (obstáculo)

Ruta sugerida: WEBupita/utils/simple_map_to_csv.py

Uso básico:
python simple_map_to_csv.py imagen_mapa.png
"""

import cv2
import numpy as np
import pandas as pd
import argparse
from pathlib import Path
import matplotlib.pyplot as plt


def process_image_to_csv(image_path, output_path=None, target_size=(50, 50),
                         yellow_threshold=0.7, show_preview=False):
    """
    Convierte una imagen a CSV binario para algoritmos de búsqueda.

    Args:
        image_path (str): Ruta de la imagen
        output_path (str): Ruta de salida (opcional)
        target_size (tuple): Tamaño objetivo (filas, columnas)
        yellow_threshold (float): Umbral para detectar amarillo (0-1)
        show_preview (bool): Mostrar preview del resultado

    Returns:
        np.array: Grilla binaria
    """

    # Cargar imagen
    print(f"📖 Cargando imagen: {image_path}")
    image = cv2.imread(image_path)
    if image is None:
        raise ValueError(f"No se pudo cargar la imagen: {image_path}")

    # Redimensionar imagen
    print(f"🔄 Redimensionando a {target_size[1]}x{target_size[0]}...")
    resized = cv2.resize(image, (target_size[1], target_size[0]))

    # Convertir a HSV para mejor detección de amarillo
    hsv = cv2.cvtColor(resized, cv2.COLOR_BGR2HSV)

    # Detectar amarillo
    # Rango amplio para capturar diferentes tonos de amarillo
    lower_yellow = np.array([15, 30, 30])
    upper_yellow = np.array([35, 255, 255])
    yellow_mask = cv2.inRange(hsv, lower_yellow, upper_yellow)

    # Detectar áreas claras (alta luminosidad)
    gray = cv2.cvtColor(resized, cv2.COLOR_BGR2GRAY)
    light_areas = gray > (255 * yellow_threshold)

    # Combinar detección de amarillo y áreas claras
    transitable_mask = np.logical_or(yellow_mask > 0, light_areas)

    # Crear grilla binaria (0 = transitable, 1 = obstáculo)
    binary_grid = np.ones(target_size, dtype=np.uint8)
    binary_grid[transitable_mask] = 0

    # Aplicar operaciones morfológicas para limpiar
    kernel = np.ones((2, 2), np.uint8)
    binary_grid = cv2.morphologyEx(binary_grid, cv2.MORPH_CLOSE, kernel)
    binary_grid = cv2.morphologyEx(binary_grid, cv2.MORPH_OPEN, kernel)

    # Mostrar preview si se solicita
    if show_preview:
        show_processing_preview(resized, binary_grid, yellow_mask, light_areas)

    # Guardar CSV
    if output_path is None:
        path = Path(image_path)
        output_path = path.parent / f"{path.stem}_map.csv"

    print(f"💾 Guardando CSV: {output_path}")
    df = pd.DataFrame(binary_grid)
    df.to_csv(output_path, index=False, header=False)

    # Estadísticas
    total_cells = binary_grid.size
    transitable_cells = np.sum(binary_grid == 0)
    obstacle_cells = np.sum(binary_grid == 1)

    print(f"\n📊 Estadísticas:")
    print(f"   Dimensiones: {target_size[1]} x {target_size[0]}")
    print(f"   Celdas transitables: {transitable_cells} ({transitable_cells / total_cells * 100:.1f}%)")
    print(f"   Celdas obstáculo: {obstacle_cells} ({obstacle_cells / total_cells * 100:.1f}%)")

    return binary_grid


def show_processing_preview(original, binary_grid, yellow_mask, light_areas):
    """Muestra preview del procesamiento."""
    fig, axes = plt.subplots(2, 2, figsize=(12, 10))

    # Imagen original
    axes[0, 0].imshow(cv2.cvtColor(original, cv2.COLOR_BGR2RGB))
    axes[0, 0].set_title('Imagen Original')
    axes[0, 0].axis('off')

    # Detección de amarillo
    axes[0, 1].imshow(yellow_mask, cmap='gray')
    axes[0, 1].set_title('Detección de Amarillo')
    axes[0, 1].axis('off')

    # Áreas claras
    axes[1, 0].imshow(light_areas, cmap='gray')
    axes[1, 0].set_title('Áreas Claras')
    axes[1, 0].axis('off')

    # Resultado final
    axes[1, 1].imshow(binary_grid, cmap='gray')
    axes[1, 1].set_title('Grilla Final\n(Blanco=Transitable, Negro=Obstáculo)')
    axes[1, 1].axis('off')

    plt.tight_layout()
    plt.show()


def create_sample_points(grid_shape, num_points=5):
    """
    Crea puntos de muestra para testing de algoritmos.

    Args:
        grid_shape (tuple): Forma de la grilla
        num_points (int): Número de puntos a generar

    Returns:
        list: Lista de puntos (y, x)
    """
    height, width = grid_shape
    points = []

    # Generar puntos distribuidos
    for i in range(num_points):
        y = int((i + 1) * height / (num_points + 1))
        x = int(width / 2)  # En el centro horizontal
        points.append((y, x))

    return points


def validate_csv_for_pathfinding(csv_path, show_analysis=False):
    """
    Valida que el CSV sea adecuado para algoritmos de búsqueda.

    Args:
        csv_path (str): Ruta del archivo CSV
        show_analysis (bool): Mostrar análisis visual

    Returns:
        dict: Información de validación
    """
    print(f"🔍 Validando CSV: {csv_path}")

    # Cargar grilla
    grid = pd.read_csv(csv_path, header=None).values

    # Análisis básico
    height, width = grid.shape
    unique_values = np.unique(grid)
    transitable_cells = np.sum(grid == 0)
    obstacle_cells = np.sum(grid == 1)

    # Verificar conectividad básica
    # Buscar el componente conectado más grande de celdas transitables
    from scipy import ndimage

    # Crear máscara de áreas transitables
    transitable_mask = (grid == 0)

    # Etiquetar componentes conectados
    labeled, num_components = ndimage.label(transitable_mask)

    if num_components > 0:
        # Encontrar el componente más grande
        component_sizes = [(labeled == i).sum() for i in range(1, num_components + 1)]
        largest_component_size = max(component_sizes)
        connectivity_ratio = largest_component_size / transitable_cells
    else:
        connectivity_ratio = 0

    validation_info = {
        'dimensions': (height, width),
        'total_cells': grid.size,
        'transitable_cells': transitable_cells,
        'obstacle_cells': obstacle_cells,
        'unique_values': unique_values.tolist(),
        'is_binary': len(unique_values) == 2 and set(unique_values) == {0, 1},
        'connectivity_components': num_components,
        'largest_component_ratio': connectivity_ratio,
        'is_suitable_for_pathfinding': (
                len(unique_values) == 2 and
                set(unique_values) == {0, 1} and
                connectivity_ratio > 0.5 and
                transitable_cells > grid.size * 0.1
        )
    }

    print(f"✓ Dimensiones: {height}x{width}")
    print(f"✓ Valores únicos: {unique_values}")
    print(f"✓ Es binario: {'Sí' if validation_info['is_binary'] else 'No'}")
    print(f"✓ Celdas transitables: {transitable_cells} ({transitable_cells / grid.size * 100:.1f}%)")
    print(f"✓ Componentes conectados: {num_components}")
    print(f"✓ Conectividad principal: {connectivity_ratio * 100:.1f}%")
    print(f"✓ Apto para pathfinding: {'Sí' if validation_info['is_suitable_for_pathfinding'] else 'No'}")

    if show_analysis:
        show_csv_analysis(grid, labeled)

    return validation_info


def show_csv_analysis(grid, labeled_components):
    """Muestra análisis visual del CSV."""
    fig, axes = plt.subplots(1, 3, figsize=(15, 5))

    # Grilla original
    axes[0].imshow(grid, cmap='gray')
    axes[0].set_title('Grilla Binaria\n(Blanco=Transitable, Negro=Obstáculo)')
    axes[0].grid(True, alpha=0.3)

    # Componentes conectados
    axes[1].imshow(labeled_components, cmap='tab10')
    axes[1].set_title('Componentes Conectados')
    axes[1].grid(True, alpha=0.3)

    # Análisis de conectividad
    connectivity_map = np.zeros_like(grid)
    connectivity_map[grid == 0] = 1  # Transitable
    connectivity_map[grid == 1] = 0  # Obstáculo

    axes[2].imshow(connectivity_map, cmap='RdYlGn')
    axes[2].set_title('Mapa de Conectividad\n(Verde=Buena, Rojo=Problema)')
    axes[2].grid(True, alpha=0.3)

    for ax in axes:
        ax.set_xlabel('X (columnas)')
        ax.set_ylabel('Y (filas)')

    plt.tight_layout()
    plt.show()


def generate_pathfinding_examples(csv_path, output_dir="pathfinding_examples"):
    """
    Genera ejemplos de puntos para testing de algoritmos de pathfinding.

    Args:
        csv_path (str): Ruta del CSV
        output_dir (str): Directorio de salida
    """
    print(f"🎯 Generando ejemplos de pathfinding...")

    # Cargar grilla
    grid = pd.read_csv(csv_path, header=None).values
    height, width = grid.shape

    # Crear directorio
    output_path = Path(output_dir)
    output_path.mkdir(exist_ok=True)

    # Encontrar celdas transitables
    transitable_coords = np.where(grid == 0)
    transitable_points = list(zip(transitable_coords[0], transitable_coords[1]))

    if len(transitable_points) < 2:
        print("❌ No hay suficientes puntos transitables para generar ejemplos")
        return

    # Generar diferentes tipos de ejemplos
    examples = {
        'corner_to_corner': {
            'description': 'Esquina a esquina',
            'start': None,
            'goal': None
        },
        'short_distance': {
            'description': 'Distancia corta',
            'start': None,
            'goal': None
        },
        'medium_distance': {
            'description': 'Distancia media',
            'start': None,
            'goal': None
        },
        'random_points': {
            'description': 'Puntos aleatorios',
            'start': None,
            'goal': None
        }
    }

    # Esquina a esquina (si es posible)
    corners = [
        (0, 0), (0, width - 1), (height - 1, 0), (height - 1, width - 1)
    ]
    valid_corners = [(y, x) for y, x in corners if y < height and x < width and grid[y, x] == 0]

    if len(valid_corners) >= 2:
        examples['corner_to_corner']['start'] = valid_corners[0]
        examples['corner_to_corner']['goal'] = valid_corners[-1]

    # Distancia corta
    if len(transitable_points) >= 2:
        center_y, center_x = height // 2, width // 2
        # Buscar puntos cerca del centro
        center_points = [
            (y, x) for y, x in transitable_points
            if abs(y - center_y) <= height // 4 and abs(x - center_x) <= width // 4
        ]
        if len(center_points) >= 2:
            examples['short_distance']['start'] = center_points[0]
            examples['short_distance']['goal'] = center_points[min(5, len(center_points) - 1)]

    # Distancia media
    if len(transitable_points) >= 2:
        quarter_points = [
            transitable_points[len(transitable_points) // 4],
            transitable_points[3 * len(transitable_points) // 4]
        ]
        examples['medium_distance']['start'] = quarter_points[0]
        examples['medium_distance']['goal'] = quarter_points[1]

    # Puntos aleatorios
    if len(transitable_points) >= 2:
        import random
        random_points = random.sample(transitable_points, 2)
        examples['random_points']['start'] = random_points[0]
        examples['random_points']['goal'] = random_points[1]

    # Guardar ejemplos
    valid_examples = {k: v for k, v in examples.items() if v['start'] is not None and v['goal'] is not None}

    examples_data = {
        'grid_info': {
            'dimensions': [height, width],
            'total_cells': int(grid.size),
            'transitable_cells': int(np.sum(grid == 0)),
            'csv_file': str(Path(csv_path).name)
        },
        'examples': {}
    }

    for name, example in valid_examples.items():
        examples_data['examples'][name] = {
            'description': example['description'],
            'start': list(example['start']),  # [y, x]
            'goal': list(example['goal']),  # [y, x]
            'start_notation': f"({example['start'][1]}, {example['start'][0]})",  # (x, y) notation
            'goal_notation': f"({example['goal'][1]}, {example['goal'][0]})"  # (x, y) notation
        }

    # Guardar como JSON
    import json
    examples_file = output_path / "pathfinding_examples.json"
    with open(examples_file, 'w', encoding='utf-8') as f:
        json.dump(examples_data, f, indent=2)

    # Guardar como texto legible
    readme_file = output_path / "README.md"
    with open(readme_file, 'w', encoding='utf-8') as f:
        f.write("# Ejemplos de Pathfinding\n\n")
        f.write(f"Grilla: {height}x{width} ({grid.size} celdas total)\n")
        f.write(f"Celdas transitables: {np.sum(grid == 0)} ({np.sum(grid == 0) / grid.size * 100:.1f}%)\n\n")
        f.write("## Formato de coordenadas\n")
        f.write("- Formato array: [fila, columna] (y, x)\n")
        f.write("- Formato cartesiano: (columna, fila) (x, y)\n\n")
        f.write("## Ejemplos para testing:\n\n")

        for name, example in examples_data['examples'].items():
            f.write(f"### {example['description']}\n")
            f.write(f"- **Inicio**: {example['start']} → {example['start_notation']}\n")
            f.write(f"- **Meta**: {example['goal']} → {example['goal_notation']}\n\n")

    print(f"✓ {len(valid_examples)} ejemplos generados en: {output_path}")

    return examples_data


def main():
    parser = argparse.ArgumentParser(
        description="Convierte imagen de mapa a CSV binario para algoritmos de búsqueda",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Ejemplos de uso:
  python simple_map_to_csv.py mapa.png
  python simple_map_to_csv.py mapa.png -s 100 150 -o mapa_100x150.csv
  python simple_map_to_csv.py mapa.png -p -e
  python simple_map_to_csv.py --validate mapa.csv
        """
    )

    parser.add_argument("input", help="Imagen de entrada o CSV para validar")
    parser.add_argument("-o", "--output", help="Archivo CSV de salida")
    parser.add_argument("-s", "--size", nargs=2, type=int, default=[50, 50],
                        help="Tamaño de grilla [filas columnas] (default: 50 50)")
    parser.add_argument("-t", "--threshold", type=float, default=0.7,
                        help="Umbral para detectar áreas claras (0-1, default: 0.7)")
    parser.add_argument("-p", "--preview", action='store_true',
                        help="Mostrar preview del procesamiento")
    parser.add_argument("-e", "--examples", action='store_true',
                        help="Generar ejemplos de pathfinding")
    parser.add_argument("--validate", action='store_true',
                        help="Validar CSV existente en lugar de procesar imagen")
    parser.add_argument("-a", "--analysis", action='store_true',
                        help="Mostrar análisis visual (con --validate)")

    args = parser.parse_args()

    if args.validate:
        # Modo validación
        if not Path(args.input).exists():
            print(f"❌ Error: No se encuentra el archivo {args.input}")
            return

        try:
            validation_info = validate_csv_for_pathfinding(args.input, args.analysis)

            if args.examples:
                generate_pathfinding_examples(args.input)

        except Exception as e:
            print(f"❌ Error validando CSV: {e}")

    else:
        # Modo procesamiento de imagen
        if not Path(args.input).exists():
            print(f"❌ Error: No se encuentra el archivo {args.input}")
            return

        try:
            # Procesar imagen
            binary_grid = process_image_to_csv(
                args.input,
                args.output,
                tuple(args.size),
                args.threshold,
                args.preview
            )

            # Generar ejemplos si se solicita
            if args.examples:
                output_csv = args.output or f"{Path(args.input).stem}_map.csv"
                generate_pathfinding_examples(output_csv)

            print(f"\n✅ Procesamiento completado!")
            print(f"   Archivo generado: {args.output or f'{Path(args.input).stem}_map.csv'}")
            print(f"   Listo para usar con algoritmos de búsqueda (A*, Dijkstra, etc.)")

        except Exception as e:
            print(f"❌ Error procesando imagen: {e}")


if __name__ == "__main__":
    # Importar scipy solo si es necesario
    try:
        from scipy import ndimage
    except ImportError:
        print("⚠️  Advertencia: scipy no está instalado. Algunas funciones de análisis no estarán disponibles.")
        print("   Instalar con: pip install scipy")

    main()