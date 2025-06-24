<?php
// Ruta: WEBupita/pages/mapa-rutas.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/header.php';
?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <main class="content">
        <h1 class="page-title">Mapa Interactivo con Rutas</h1>

        <!-- Panel de control de rutas -->
        <div class="route-controls" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                <!-- Selector de origen -->
                <div>
                    <label for="origen" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <i class="fas fa-map-marker-alt" style="color: #28a745;"></i> Origen:
                    </label>
                    <select id="origen" class="form-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Selecciona punto de origen...</option>
                    </select>
                </div>

                <!-- Selector de destino -->
                <div>
                    <label for="destino" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> Destino:
                    </label>
                    <select id="destino" class="form-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Selecciona punto de destino...</option>
                    </select>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; gap: 10px;">
                    <button id="calcularRuta" class="btn btn-primary" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-route"></i> Calcular Ruta
                    </button>
                    <button id="limpiarRuta" class="btn btn-secondary" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </div>

            <!-- Barra de búsqueda -->
            <div style="margin-top: 15px;">
                <input type="text" id="buscarLugar" placeholder="Buscar aula o lugar..."
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <!-- Opciones adicionales -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="guardarFavorito">
                        <span>Guardar como ruta favorita</span>
                    </label>
                    <input type="text" id="nombreRuta" placeholder="Nombre para la ruta favorita..."
                           style="width: 100%; padding: 6px; margin-top: 8px; border: 1px solid #ddd; border-radius: 4px; display: none;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Información de la ruta -->
        <div id="rutaInfo" class="route-info" style="display: none; background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <h3 style="margin: 0 0 10px 0; color: #155724;">
                <i class="fas fa-info-circle"></i> Información de la Ruta
            </h3>
            <div id="rutaDetalles"></div>
        </div>

        <!-- Contenedor del mapa -->
        <div class="map-container" style="position: relative; width: 100%; height: 600px; border: 2px solid #003366; border-radius: 8px; overflow: hidden;">
            <canvas id="mapaCanvas" width="1000" height="600" style="display: block; background: #f0f8ff;"></canvas>

            <!-- Controles del mapa -->
            <div class="map-controls" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.9); padding: 10px; border-radius: 4px;">
                <button id="zoomIn" style="display: block; margin-bottom: 5px; padding: 5px 10px; border: 1px solid #ddd; background: white; cursor: pointer;">
                    <i class="fas fa-plus"></i> Zoom +
                </button>
                <button id="zoomOut" style="display: block; padding: 5px 10px; border: 1px solid #ddd; background: white; cursor: pointer;">
                    <i class="fas fa-minus"></i> Zoom -
                </button>
            </div>
        </div>

        <!-- Leyenda del mapa -->
        <div class="map-legend" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0;">Leyenda</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #28a745; border-radius: 50%;"></div>
                    <span>Punto de Origen</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #dc3545; border-radius: 50%;"></div>
                    <span>Punto de Destino</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 30px; height: 4px; background: #007bff;"></div>
                    <span>Ruta Calculada</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #3498db; border: 2px solid #333;"></div>
                    <span>Aulas</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #e74c3c; border: 2px solid #333;"></div>
                    <span>Edificio de Gobierno</span>
                </div>
            </div>
        </div>

        <!-- Rutas favoritas (solo para usuarios logueados) -->
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="favorite-routes" style="margin-top: 30px;">
                <h2 class="section-title">Mis Rutas Favoritas</h2>
                <div id="rutasFavoritas" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <p style="text-align: center; color: #666;">Cargando rutas favoritas...</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Variables globales
        let canvas, ctx;
        let rutaActual = null;
        let zoom = 1;
        let offsetX = 0, offsetY = 0;
        let isDragging = false;
        let lastMouseX, lastMouseY;

        // Configurar rutas base para APIs
        const BASE_PATH = '/TecnologiasParaElDesarrolloDeAplicacionesWeb/SchoolPathFinder/WEBupita';

        // Coordenadas de edificios principales (ejemplo simplificado)
        const edificios = {
            'A1': { x: 200, y: 150, width: 80, height: 60, color: '#3498db', nombre: 'Edificio A1' },
            'A2': { x: 350, y: 150, width: 80, height: 60, color: '#e91e63', nombre: 'Edificio A2' },
            'A3': { x: 500, y: 150, width: 80, height: 60, color: '#f39c12', nombre: 'Edificio A3' },
            'A4': { x: 650, y: 150, width: 80, height: 60, color: '#2ecc71', nombre: 'Edificio A4' },
            'LC': { x: 350, y: 300, width: 120, height: 80, color: '#34495e', nombre: 'Lab. Central' },
            'EG': { x: 550, y: 350, width: 100, height: 70, color: '#f1c40f', nombre: 'Ed. Gobierno' },
            'EP': { x: 200, y: 400, width: 100, height: 60, color: '#e74c3c', nombre: 'Lab. Pesados' }
        };

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('mapaCanvas');
            ctx = canvas.getContext('2d');

            // Ajustar canvas al contenedor
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            // Dibujar mapa inicial
            dibujarMapa();

            // Cargar lugares para los selectores
            cargarLugares();

            // Verificar si hay una ruta para cargar desde favoritos
            const rutaParaCargar = sessionStorage.getItem('cargarRuta');
            if (rutaParaCargar) {
                const datos = JSON.parse(rutaParaCargar);
                sessionStorage.removeItem('cargarRuta'); // Limpiar después de usar

                // Esperar a que se carguen los lugares y luego cargar la ruta
                setTimeout(() => {
                    document.getElementById('origen').value = datos.origen;
                    document.getElementById('destino').value = datos.destino;
                    calcularRuta();
                }, 1000);
            }

            // Event listeners
            document.getElementById('calcularRuta').addEventListener('click', calcularRuta);
            document.getElementById('limpiarRuta').addEventListener('click', limpiarRuta);
            document.getElementById('buscarLugar').addEventListener('input', buscarLugares);
            document.getElementById('zoomIn').addEventListener('click', () => cambiarZoom(1.2));
            document.getElementById('zoomOut').addEventListener('click', () => cambiarZoom(0.8));

            // Control de zoom con rueda del mouse
            canvas.addEventListener('wheel', function(e) {
                e.preventDefault();
                const factor = e.deltaY > 0 ? 0.9 : 1.1;
                cambiarZoom(factor);
            });

            // Control de arrastrar
            canvas.addEventListener('mousedown', startDrag);
            canvas.addEventListener('mousemove', drag);
            canvas.addEventListener('mouseup', stopDrag);
            canvas.addEventListener('mouseleave', stopDrag);

            // Checkbox para guardar favorito
            const checkbox = document.getElementById('guardarFavorito');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    const input = document.getElementById('nombreRuta');
                    input.style.display = this.checked ? 'block' : 'none';
                });
            }

            // Cargar rutas favoritas si el usuario está logueado
            <?php if (isset($_SESSION['usuario_id'])): ?>
            cargarRutasFavoritas();
            <?php endif; ?>
        });

        function resizeCanvas() {
            const container = canvas.parentElement;
            canvas.width = container.clientWidth;
            canvas.height = container.clientHeight;
            dibujarMapa();
        }

        function dibujarMapa() {
            // Limpiar canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Aplicar transformaciones de zoom y pan
            ctx.save();
            ctx.scale(zoom, zoom);
            ctx.translate(offsetX, offsetY);

            // Dibujar edificios
            Object.entries(edificios).forEach(([codigo, edificio]) => {
                ctx.fillStyle = edificio.color;
                ctx.fillRect(edificio.x, edificio.y, edificio.width, edificio.height);

                ctx.strokeStyle = '#333';
                ctx.lineWidth = 2;
                ctx.strokeRect(edificio.x, edificio.y, edificio.width, edificio.height);

                // Etiqueta del edificio
                ctx.fillStyle = 'white';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(codigo,
                    edificio.x + edificio.width/2,
                    edificio.y + edificio.height/2 + 4
                );
            });

            // Dibujar conexiones principales (simplificado)
            ctx.strokeStyle = '#ccc';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);

            // Algunas conexiones de ejemplo
            dibujarLinea(200, 150, 350, 150); // A1 a A2
            dibujarLinea(350, 150, 500, 150); // A2 a A3
            dibujarLinea(500, 150, 650, 150); // A3 a A4
            dibujarLinea(350, 210, 350, 300); // A2 a LC
            dibujarLinea(470, 300, 550, 350); // LC a EG
            dibujarLinea(300, 380, 550, 380); // Conexión horizontal

            ctx.setLineDash([]);

            // Dibujar ruta actual si existe
            if (rutaActual && rutaActual.ruta_detallada) {
                dibujarRuta(rutaActual.ruta_detallada);
            }

            ctx.restore();
        }

        function dibujarLinea(x1, y1, x2, y2) {
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        }

        function dibujarRuta(rutaDetallada) {
            if (rutaDetallada.length < 2) return;

            // Línea de la ruta
            ctx.strokeStyle = '#007bff';
            ctx.lineWidth = 4;
            ctx.setLineDash([]);

            ctx.beginPath();
            ctx.moveTo(rutaDetallada[0].coordenada_x, rutaDetallada[0].coordenada_y);

            for (let i = 1; i < rutaDetallada.length; i++) {
                ctx.lineTo(rutaDetallada[i].coordenada_x, rutaDetallada[i].coordenada_y);
            }
            ctx.stroke();

            // Marcadores de inicio y fin
            rutaDetallada.forEach((punto, index) => {
                if (index === 0) {
                    // Punto de origen (verde)
                    dibujarMarcador(punto.coordenada_x, punto.coordenada_y, '#28a745', 'O');
                } else if (index === rutaDetallada.length - 1) {
                    // Punto de destino (rojo)
                    dibujarMarcador(punto.coordenada_x, punto.coordenada_y, '#dc3545', 'D');
                } else {
                    // Puntos intermedios (azul)
                    dibujarMarcador(punto.coordenada_x, punto.coordenada_y, '#17a2b8', '•');
                }
            });
        }

        function dibujarMarcador(x, y, color, texto) {
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(x, y, 8, 0, 2 * Math.PI);
            ctx.fill();

            ctx.strokeStyle = 'white';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.fillStyle = 'white';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(texto, x, y + 3);
        }

        function cambiarZoom(factor) {
            zoom *= factor;
            zoom = Math.max(0.5, Math.min(3, zoom)); // Limitar zoom
            dibujarMapa();
        }

        function startDrag(e) {
            isDragging = true;
            lastMouseX = e.offsetX;
            lastMouseY = e.offsetY;
            canvas.style.cursor = 'grabbing';
        }

        function drag(e) {
            if (!isDragging) return;

            const deltaX = (e.offsetX - lastMouseX) / zoom;
            const deltaY = (e.offsetY - lastMouseY) / zoom;

            offsetX += deltaX;
            offsetY += deltaY;

            lastMouseX = e.offsetX;
            lastMouseY = e.offsetY;

            dibujarMapa();
        }

        function stopDrag() {
            isDragging = false;
            canvas.style.cursor = 'grab';
        }

        async function cargarLugares() {
            try {
                const response = await fetch(BASE_PATH + '/api/buscar_lugares.php');
                const data = await response.json();

                if (data.success) {
                    const origenSelect = document.getElementById('origen');
                    const destinoSelect = document.getElementById('destino');

                    // Limpiar selects
                    origenSelect.innerHTML = '<option value="">Selecciona punto de origen...</option>';
                    destinoSelect.innerHTML = '<option value="">Selecciona punto de destino...</option>';

                    // Agregar opciones agrupadas por edificio
                    data.data.forEach(edificio => {
                        const origenGroup = document.createElement('optgroup');
                        const destinoGroup = document.createElement('optgroup');
                        origenGroup.label = edificio.edificio;
                        destinoGroup.label = edificio.edificio;

                        edificio.lugares.forEach(lugar => {
                            const origenOption = document.createElement('option');
                            const destinoOption = document.createElement('option');

                            origenOption.value = lugar.valor_completo;
                            origenOption.textContent = `${lugar.codigo} - ${lugar.nombre}`;
                            destinoOption.value = lugar.valor_completo;
                            destinoOption.textContent = `${lugar.codigo} - ${lugar.nombre}`;

                            origenGroup.appendChild(origenOption);
                            destinoGroup.appendChild(destinoOption);
                        });

                        origenSelect.appendChild(origenGroup);
                        destinoSelect.appendChild(destinoGroup);
                    });
                }
            } catch (error) {
                console.error('Error cargando lugares:', error);
                mostrarMensaje('Error cargando lugares: ' + error.message, 'error');
            }
        }

        async function buscarLugares() {
            const termino = document.getElementById('buscarLugar').value;
            if (termino.length < 2) {
                cargarLugares();
                return;
            }

            try {
                const response = await fetch(BASE_PATH + `/api/buscar_lugares.php?q=${encodeURIComponent(termino)}`);
                const data = await response.json();

                if (data.success) {
                    // Actualizar selectores con resultados de búsqueda
                    // Similar a cargarLugares() pero con los resultados filtrados
                }
            } catch (error) {
                console.error('Error en búsqueda:', error);
            }
        }

        async function calcularRuta() {
            const origen = document.getElementById('origen').value;
            const destino = document.getElementById('destino').value;

            if (!origen || !destino) {
                mostrarMensaje('Por favor selecciona origen y destino', 'warning');
                return;
            }

            if (origen === destino) {
                mostrarMensaje('El origen y destino no pueden ser el mismo', 'warning');
                return;
            }

            try {
                const payload = {
                    origen: origen,
                    destino: destino
                };

                // Agregar datos de favorito si está marcado
                const guardarFavorito = document.getElementById('guardarFavorito');
                if (guardarFavorito && guardarFavorito.checked) {
                    const nombreRuta = document.getElementById('nombreRuta').value;
                    if (nombreRuta) {
                        payload.guardar_favorito = true;
                        payload.nombre_ruta = nombreRuta;
                    }
                }

                const response = await fetch(BASE_PATH + '/api/calcular_ruta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    rutaActual = data.ruta;
                    mostrarInformacionRuta(rutaActual);
                    dibujarMapa();

                    mostrarMensaje('Ruta calculada exitosamente', 'success');

                    // Limpiar formulario de favorito
                    if (guardarFavorito && guardarFavorito.checked) {
                        guardarFavorito.checked = false;
                        document.getElementById('nombreRuta').style.display = 'none';
                        document.getElementById('nombreRuta').value = '';

                        // Recargar favoritos
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                        cargarRutasFavoritas();
                        <?php endif; ?>
                    }
                } else {
                    mostrarMensaje('Error: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error calculando ruta:', error);
                mostrarMensaje('Error al calcular la ruta: ' + error.message, 'error');
            }
        }

        function mostrarInformacionRuta(ruta) {
            const rutaInfo = document.getElementById('rutaInfo');
            const rutaDetalles = document.getElementById('rutaDetalles');

            let html = `
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px;">
            <div><strong>Distancia Total:</strong> ${ruta.distancia_total.toFixed(1)} metros</div>
            <div><strong>Número de Pasos:</strong> ${ruta.numero_pasos}</div>
            <div><strong>Puntos en la Ruta:</strong> ${ruta.ruta_detallada.length}</div>
        </div>
        <div>
            <strong>Recorrido:</strong>
            <ol style="margin: 10px 0 0 20px;">
    `;

            ruta.ruta_detallada.forEach((punto, index) => {
                const icono = punto.tipo === 'aula' ? '🚪' : '🚶';
                html += `<li>${icono} ${punto.codigo} - ${punto.nombre}</li>`;
            });

            html += '</ol></div>';

            rutaDetalles.innerHTML = html;
            rutaInfo.style.display = 'block';
        }

        function limpiarRuta() {
            rutaActual = null;
            document.getElementById('rutaInfo').style.display = 'none';
            document.getElementById('origen').value = '';
            document.getElementById('destino').value = '';
            dibujarMapa();
            mostrarMensaje('Mapa limpiado', 'info');
        }

        <?php if (isset($_SESSION['usuario_id'])): ?>
        async function cargarRutasFavoritas() {
            try {
                const response = await fetch(BASE_PATH + '/api/rutas_favoritas.php');
                const data = await response.json();

                const container = document.getElementById('rutasFavoritas');

                if (data.success && data.rutas.length > 0) {
                    let html = '';
                    data.rutas.forEach(ruta => {
                        html += `
                    <div class="favorite-route-item" style="background: white; padding: 15px; margin-bottom: 10px; border-radius: 4px; border-left: 4px solid #007bff;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 8px 0; color: #003366;">${ruta.nombre_ruta}</h4>
                                <p style="margin: 0; color: #666;">
                                    <i class="fas fa-map-marker-alt" style="color: #28a745;"></i> ${ruta.origen_codigo}
                                    <i class="fas fa-arrow-right" style="margin: 0 8px;"></i>
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> ${ruta.destino_codigo}
                                </p>
                                <small style="color: #999;">Creada: ${new Date(ruta.fecha_creacion).toLocaleDateString()}</small>
                            </div>
                            <button onclick="cargarRutaFavorita('${ruta.origen_tipo}_${ruta.origen_id}', '${ruta.destino_tipo}_${ruta.destino_id}')"
                                    style="padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-route"></i> Cargar
                            </button>
                        </div>
                    </div>
                `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #666;">No tienes rutas favoritas guardadas</p>';
                }
            } catch (error) {
                console.error('Error cargando rutas favoritas:', error);
            }
        }

        function cargarRutaFavorita(origen, destino) {
            document.getElementById('origen').value = origen;
            document.getElementById('destino').value = destino;
            calcularRuta();
        }
        <?php endif; ?>

        function mostrarMensaje(mensaje, tipo) {
            const colores = {
                'success': { bg: '#d4edda', color: '#155724' },
                'error': { bg: '#f8d7da', color: '#721c24' },
                'warning': { bg: '#fff3cd', color: '#856404' },
                'info': { bg: '#d1ecf1', color: '#0c5460' }
            };

            const estilo = colores[tipo] || colores.info;

            const div = document.createElement('div');
            div.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${estilo.bg};
            color: ${estilo.color};
            padding: 15px 20px;
            border-radius: 4px;
            z-index: 1000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;

            div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${mensaje}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: ${estilo.color}; cursor: pointer; margin-left: 10px;">
                    &times;
                </button>
            </div>
        `;

            document.body.appendChild(div);

            setTimeout(() => {
                if (div.parentElement) {
                    div.remove();
                }
            }, 3000);
        }
    </script>

    <style>
        .form-select:focus, .btn:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        #mapaCanvas {
            cursor: grab;
        }

        .route-info {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>

<?php include '../includes/footer.php'; ?>