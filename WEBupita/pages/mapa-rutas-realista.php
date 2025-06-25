<?php
// Ruta: WEBupita/pages/mapa-rutas-realista.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/header.php';
?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <main class="content">
        <h1 class="page-title">
            <i class="fas fa-map-marked-alt"></i> Mapa Interactivo UPIITA - Realista
        </h1>

        <!-- Panel de control superior -->
        <div class="mapa-controls" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; color: white;">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 15px; align-items: end;">
                <!-- Selector de origen -->
                <div>
                    <label for="origen" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <i class="fas fa-map-marker-alt" style="color: #4CAF50;"></i> Punto de Origen:
                    </label>
                    <select id="origen" class="form-select" style="width: 100%; padding: 10px; border: none; border-radius: 6px; font-size: 14px;">
                        <option value="">🏫 Selecciona punto de origen...</option>
                    </select>
                </div>

                <!-- Selector de destino -->
                <div>
                    <label for="destino" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <i class="fas fa-map-marker-alt" style="color: #f44336;"></i> Punto de Destino:
                    </label>
                    <select id="destino" class="form-select" style="width: 100%; padding: 10px; border: none; border-radius: 6px; font-size: 14px;">
                        <option value="">🎯 Selecciona punto de destino...</option>
                    </select>
                </div>

                <!-- Botones principales -->
                <div style="display: flex; gap: 10px;">
                    <button id="calcularRuta" class="btn-calcular" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
                        <i class="fas fa-route"></i> Calcular
                    </button>
                    <button id="limpiarRuta" class="btn-limpiar" style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s;">
                        <i class="fas fa-broom"></i> Limpiar
                    </button>
                </div>

                <!-- Controles adicionales -->
                <div class="dropdown" style="position: relative;">
                    <button id="menuControles" style="padding: 10px 15px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-cog"></i> Opciones
                    </button>
                    <div id="menuDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; border-radius: 6px; padding: 10px; margin-top: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; min-width: 200px;">
                        <button id="centrarMapa" style="display: block; width: 100%; padding: 8px; background: none; border: none; text-align: left; cursor: pointer; border-radius: 4px; margin-bottom: 5px;">
                            <i class="fas fa-crosshairs"></i> Centrar Mapa
                        </button>
                        <button id="vistaGeneral" style="display: block; width: 100%; padding: 8px; background: none; border: none; text-align: left; cursor: pointer; border-radius: 4px; margin-bottom: 5px;">
                            <i class="fas fa-expand"></i> Vista General
                        </button>
                        <button id="exportarMapa" style="display: block; width: 100%; padding: 8px; background: none; border: none; text-align: left; cursor: pointer; border-radius: 4px; margin-bottom: 5px;">
                            <i class="fas fa-download"></i> Exportar Imagen
                        </button>
                        <hr style="margin: 8px 0;">
                        <label style="display: flex; align-items: center; gap: 8px; padding: 4px;">
                            <input type="checkbox" id="mostrarEtiquetas" checked>
                            <span>Mostrar Etiquetas</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 4px;">
                            <input type="checkbox" id="mostrarDistancias" checked>
                            <span>Mostrar Distancias</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Barra de búsqueda -->
            <div style="margin-top: 15px; position: relative;">
                <input type="text" id="buscarLugar" placeholder="🔍 Buscar aula, laboratorio o edificio..."
                       style="width: 100%; padding: 12px 40px 12px 15px; border: none; border-radius: 25px; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <button id="limpiarBusqueda" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Panel de rutas favoritas (solo usuarios logueados) -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <input type="checkbox" id="guardarFavorito">
                            <span>💾 Guardar como favorita</span>
                        </label>
                        <input type="text" id="nombreRuta" placeholder="Nombre para la ruta..."
                               style="display: none; padding: 8px 12px; border: none; border-radius: 4px; font-size: 14px; flex: 1;">
                        <button id="verFavoritos" style="padding: 8px 15px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-star"></i> Mis Favoritas
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panel de información de la ruta -->
        <div id="rutaInfo" class="route-info" style="display: none; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <i class="fas fa-route" style="font-size: 1.5rem;"></i>
                <h3 style="margin: 0; font-size: 1.3rem;">Información de la Ruta</h3>
            </div>
            <div id="rutaDetalles"></div>
        </div>

        <!-- Contenedor principal del mapa -->
        <div class="mapa-container" style="position: relative; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
            <!-- Canvas del mapa -->
            <canvas id="mapaCanvas" width="1200" height="700" style="display: block; width: 100%; height: 700px; cursor: grab;"></canvas>

            <!-- Controles de zoom -->
            <div class="zoom-controls" style="position: absolute; top: 20px; right: 20px; display: flex; flex-direction: column; gap: 5px;">
                <button id="zoomIn" style="width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; transition: all 0.3s;">
                    +
                </button>
                <button id="zoomOut" style="width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; transition: all 0.3s;">
                    −
                </button>
                <button id="resetZoom" style="width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; transition: all 0.3s;">
                    <i class="fas fa-home"></i>
                </button>
            </div>

            <!-- Panel de información del edificio seleccionado -->
            <div id="infoEdificio" style="display: none; position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.85); color: white; padding: 15px; border-radius: 8px; max-width: 300px;">
                <div id="infoEdificioContent"></div>
                <button id="cerrarInfo" style="position: absolute; top: 5px; right: 8px; background: none; border: none; color: white; cursor: pointer; font-size: 18px;">×</button>
            </div>

            <!-- Indicador de carga -->
            <div id="loadingIndicator" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 8px; display: none;">
                <div style="text-align: center;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <div>Cargando mapa...</div>
                </div>
            </div>
        </div>

        <!-- Panel de controles del piso (cuando se selecciona un edificio) -->
        <div id="controlPisos" style="display: none; margin-top: 20px; text-align: center;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: inline-block;">
                <label style="margin-right: 15px; font-weight: bold;">Seleccionar Piso:</label>
                <div id="botonespisos" style="display: inline-flex; gap: 10px;"></div>
                <button id="volverGeneral" style="margin-left: 15px; padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-arrow-left"></i> Vista General
                </button>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../js/MapaRealista.js"></script>
    <script>
        let mapaRealista;
        let rutaActual = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar indicador de carga
            document.getElementById('loadingIndicator').style.display = 'block';

            // Inicializar mapa
            const baseUrl = window.API_BASE_URL || '/WEBupita';
            mapaRealista = new MapaRealista('mapaCanvas', baseUrl);

            // Ocultar indicador de carga cuando el mapa esté listo
            setTimeout(() => {
                document.getElementById('loadingIndicator').style.display = 'none';
            }, 1500);

            // Cargar lugares para los selectores
            cargarLugares();

            // Event listeners
            setupEventListeners();

            // Verificar si hay una ruta para cargar desde favoritos
            const rutaParaCargar = sessionStorage.getItem('cargarRuta');
            if (rutaParaCargar) {
                const datos = JSON.parse(rutaParaCargar);
                sessionStorage.removeItem('cargarRuta');

                setTimeout(() => {
                    document.getElementById('origen').value = datos.origen;
                    document.getElementById('destino').value = datos.destino;
                    calcularRuta();
                }, 2000);
            }

            // Cargar rutas favoritas si el usuario está logueado
            <?php if (isset($_SESSION['usuario_id'])): ?>
            cargarRutasFavoritas();
            <?php endif; ?>
        });

        function setupEventListeners() {
            // Botones principales
            document.getElementById('calcularRuta').addEventListener('click', calcularRuta);
            document.getElementById('limpiarRuta').addEventListener('click', limpiarRuta);
            document.getElementById('buscarLugar').addEventListener('input', buscarLugares);
            document.getElementById('limpiarBusqueda').addEventListener('click', limpiarBusqueda);

            // Controles de zoom
            document.getElementById('zoomIn').addEventListener('click', () => mapaRealista.cambiarZoom(1.2));
            document.getElementById('zoomOut').addEventListener('click', () => mapaRealista.cambiarZoom(0.8));
            document.getElementById('resetZoom').addEventListener('click', () => mapaRealista.resetearVista());

            // Menú de opciones
            document.getElementById('menuControles').addEventListener('click', toggleMenuOpciones);
            document.getElementById('centrarMapa').addEventListener('click', () => mapaRealista.resetearVista());
            document.getElementById('vistaGeneral').addEventListener('click', () => mapaRealista.volverVistaGeneral());
            document.getElementById('exportarMapa').addEventListener('click', () => mapaRealista.exportarImagen());

            // Checkboxes de opciones
            document.getElementById('mostrarEtiquetas').addEventListener('change', toggleEtiquetas);
            document.getElementById('mostrarDistancias').addEventListener('change', toggleDistancias);

            // Controles de rutas favoritas
            <?php if (isset($_SESSION['usuario_id'])): ?>
            document.getElementById('guardarFavorito').addEventListener('change', toggleGuardarFavorito);
            document.getElementById('verFavoritos').addEventListener('click', () => {
                window.location.href = '../Public/favoritos.php';
            });
            <?php endif; ?>

            // Event listeners del mapa
            const canvas = document.getElementById('mapaCanvas');
            canvas.addEventListener('edificioSeleccionado', manejarEdificioSeleccionado);

            // Controles de piso
            document.getElementById('volverGeneral').addEventListener('click', () => {
                mapaRealista.volverVistaGeneral();
                document.getElementById('controlPisos').style.display = 'none';
                document.getElementById('infoEdificio').style.display = 'none';
            });

            document.getElementById('cerrarInfo').addEventListener('click', () => {
                document.getElementById('infoEdificio').style.display = 'none';
            });

            // Cerrar menús al hacer clic fuera
            document.addEventListener('click', function(e) {
                const menu = document.getElementById('menuDropdown');
                const boton = document.getElementById('menuControles');

                if (!menu.contains(e.target) && !boton.contains(e.target)) {
                    menu.style.display = 'none';
                }
            });
        }

        async function cargarLugares() {
            try {
                const response = await fetch(`${window.API_BASE_URL}/api/buscar_lugares.php`);
                const data = await response.json();

                if (data.success) {
                    const origenSelect = document.getElementById('origen');
                    const destinoSelect = document.getElementById('destino');

                    origenSelect.innerHTML = '<option value="">🏫 Selecciona punto de origen...</option>';
                    destinoSelect.innerHTML = '<option value="">🎯 Selecciona punto de destino...</option>';

                    data.data.forEach(edificio => {
                        const origenGroup = document.createElement('optgroup');
                        const destinoGroup = document.createElement('optgroup');
                        origenGroup.label = `🏢 ${edificio.edificio}`;
                        destinoGroup.label = `🏢 ${edificio.edificio}`;

                        edificio.lugares.forEach(lugar => {
                            const icono = obtenerIconoLugar(lugar.nombre);

                            const origenOption = document.createElement('option');
                            const destinoOption = document.createElement('option');

                            origenOption.value = lugar.valor_completo;
                            origenOption.textContent = `${icono} ${lugar.codigo} - ${lugar.nombre}`;
                            destinoOption.value = lugar.valor_completo;
                            destinoOption.textContent = `${icono} ${lugar.codigo} - ${lugar.nombre}`;

                            origenGroup.appendChild(origenOption);
                            destinoGroup.appendChild(destinoOption);
                        });

                        origenSelect.appendChild(origenGroup);
                        destinoSelect.appendChild(destinoGroup);
                    });
                }
            } catch (error) {
                console.error('Error cargando lugares:', error);
                mostrarMensaje('Error cargando lugares disponibles', 'error');
            }
        }

        function obtenerIconoLugar(nombre) {
            const nombreLower = nombre.toLowerCase();
            if (nombreLower.includes('laboratorio') || nombreLower.includes('lab')) return '🧪';
            if (nombreLower.includes('aula')) return '📚';
            if (nombreLower.includes('biblioteca')) return '📖';
            if (nombreLower.includes('auditorio')) return '🎭';
            if (nombreLower.includes('oficina') || nombreLower.includes('dirección')) return '🏢';
            if (nombreLower.includes('médico') || nombreLower.includes('salud')) return '🏥';
            if (nombreLower.includes('deporte')) return '⚽';
            return '📍';
        }

        async function buscarLugares() {
            const termino = document.getElementById('buscarLugar').value;
            if (termino.length < 2) {
                cargarLugares();
                return;
            }

            try {
                const response = await fetch(`${window.API_BASE_URL}/api/buscar_lugares.php?q=${encodeURIComponent(termino)}`);
                const data = await response.json();

                if (data.success) {
                    // Actualizar selectores con resultados filtrados
                    actualizarSelectoresConResultados(data.data);

                    // Resaltar en el mapa si hay un resultado exacto
                    if (data.data.length === 1 && data.data[0].lugares.length === 1) {
                        const lugar = data.data[0].lugares[0];
                        mapaRealista.centrarEnAula(lugar.codigo);
                    }
                }
            } catch (error) {
                console.error('Error en búsqueda:', error);
            }
        }

        function actualizarSelectoresConResultados(resultados) {
            const origenSelect = document.getElementById('origen');
            const destinoSelect = document.getElementById('destino');

            origenSelect.innerHTML = '<option value="">🏫 Selecciona punto de origen...</option>';
            destinoSelect.innerHTML = '<option value="">🎯 Selecciona punto de destino...</option>';

            resultados.forEach(edificio => {
                if (edificio.lugares.length > 0) {
                    const origenGroup = document.createElement('optgroup');
                    const destinoGroup = document.createElement('optgroup');
                    origenGroup.label = `🔍 ${edificio.edificio}`;
                    destinoGroup.label = `🔍 ${edificio.edificio}`;

                    edificio.lugares.forEach(lugar => {
                        const icono = obtenerIconoLugar(lugar.nombre);

                        const origenOption = document.createElement('option');
                        const destinoOption = document.createElement('option');

                        origenOption.value = lugar.valor_completo;
                        origenOption.textContent = `${icono} ${lugar.codigo} - ${lugar.nombre}`;
                        destinoOption.value = lugar.valor_completo;
                        destinoOption.textContent = `${icono} ${lugar.codigo} - ${lugar.nombre}`;

                        origenGroup.appendChild(origenOption);
                        destinoGroup.appendChild(destinoOption);
                    });

                    origenSelect.appendChild(origenGroup);
                    destinoSelect.appendChild(destinoGroup);
                }
            });
        }

        function limpiarBusqueda() {
            document.getElementById('buscarLugar').value = '';
            cargarLugares();
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

            // Mostrar indicador de carga
            const calcularBtn = document.getElementById('calcularRuta');
            const originalText = calcularBtn.innerHTML;
            calcularBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculando...';
            calcularBtn.disabled = true;

            try {
                const payload = {
                    origen: origen,
                    destino: destino
                };

                // Agregar datos de favorito si está marcado
                <?php if (isset($_SESSION['usuario_id'])): ?>
                const guardarFavorito = document.getElementById('guardarFavorito');
                if (guardarFavorito && guardarFavorito.checked) {
                    const nombreRuta = document.getElementById('nombreRuta').value.trim();
                    if (nombreRuta) {
                        payload.guardar_favorito = true;
                        payload.nombre_ruta = nombreRuta;
                    }
                }
                <?php endif; ?>

                const response = await fetch(`${window.API_BASE_URL}/api/calcular_ruta.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    rutaActual = data.ruta;
                    mapaRealista.establecerRuta(rutaActual.ruta_detallada);
                    mostrarInformacionRuta(rutaActual);
                    mostrarMensaje('¡Ruta calculada exitosamente!', 'success');

                    // Limpiar formulario de favorito
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    if (guardarFavorito && guardarFavorito.checked) {
                        guardarFavorito.checked = false;
                        document.getElementById('nombreRuta').style.display = 'none';
                        document.getElementById('nombreRuta').value = '';
                        cargarRutasFavoritas();
                    }
                    <?php endif; ?>
                } else {
                    mostrarMensaje('Error: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error calculando ruta:', error);
                mostrarMensaje('Error de conexión al calcular la ruta', 'error');
            } finally {
                calcularBtn.innerHTML = originalText;
                calcularBtn.disabled = false;
            }
        }

        function mostrarInformacionRuta(ruta) {
            const rutaInfo = document.getElementById('rutaInfo');
            const rutaDetalles = document.getElementById('rutaDetalles');

            const tiempoEstimado = Math.ceil(ruta.distancia_total / 80); // 80 metros por minuto

            let html = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;">
                        ${ruta.distancia_total.toFixed(0)}m
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Distancia Total</div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;">
                        ${tiempoEstimado}min
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Tiempo Estimado</div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;">
                        ${ruta.numero_pasos}
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Puntos de Ruta</div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;">
                        ${ruta.ruta_detallada.length}
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Ubicaciones</div>
                </div>
            </div>

            <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
                <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list-ol"></i> Recorrido Detallado:
                </h4>
                <div style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
        `;

            ruta.ruta_detallada.forEach((punto, index) => {
                const icono = index === 0 ? '🟢' :
                    index === ruta.ruta_detallada.length - 1 ? '🔴' :
                        punto.tipo === 'aula' ? '🚪' : '🚶';

                const esOrigen = index === 0;
                const esDestino = index === ruta.ruta_detallada.length - 1;

                html += `
                <div style="display: flex; align-items: center; gap: 12px; padding: 8px; margin-bottom: 8px; background: rgba(255,255,255,${esOrigen || esDestino ? '0.2' : '0.1'}); border-radius: 6px; border-left: 4px solid ${esOrigen ? '#4CAF50' : esDestino ? '#f44336' : '#2196F3'};">
                    <span style="font-size: 1.2rem;">${icono}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: bold; margin-bottom: 2px;">
                            ${punto.codigo}
                            ${esOrigen ? ' (ORIGEN)' : esDestino ? ' (DESTINO)' : ''}
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">
                            ${punto.nombre}
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 0.8rem; opacity: 0.8;">
                        Paso ${index + 1}
                    </div>
                </div>
            `;
            });

            html += '</div></div>';

            rutaDetalles.innerHTML = html;
            rutaInfo.style.display = 'block';

            // Scroll suave hacia la información
            rutaInfo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function limpiarRuta() {
            rutaActual = null;
            mapaRealista.limpiarRuta();
            document.getElementById('rutaInfo').style.display = 'none';
            document.getElementById('origen').value = '';
            document.getElementById('destino').value = '';
            mostrarMensaje('Mapa limpiado', 'info');
        }

        function toggleMenuOpciones() {
            const menu = document.getElementById('menuDropdown');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        function toggleEtiquetas() {
            const mostrar = document.getElementById('mostrarEtiquetas').checked;
            // Implementar lógica para mostrar/ocultar etiquetas
            console.log('Toggle etiquetas:', mostrar);
        }

        function toggleDistancias() {
            const mostrar = document.getElementById('mostrarDistancias').checked;
            // Implementar lógica para mostrar/ocultar distancias
            console.log('Toggle distancias:', mostrar);
        }

        <?php if (isset($_SESSION['usuario_id'])): ?>
        function toggleGuardarFavorito() {
            const checkbox = document.getElementById('guardarFavorito');
            const input = document.getElementById('nombreRuta');
            input.style.display = checkbox.checked ? 'block' : 'none';

            if (checkbox.checked) {
                // Generar nombre automático
                const origen = document.getElementById('origen');
                const destino = document.getElementById('destino');

                if (origen.value && destino.value) {
                    const origenTexto = origen.options[origen.selectedIndex].text.split(' - ')[0].replace(/[🧪📚📖🎭🏢🏥⚽📍]/g, '').trim();
                    const destinoTexto = destino.options[destino.selectedIndex].text.split(' - ')[0].replace(/[🧪📚📖🎭🏢🏥⚽📍]/g, '').trim();
                    input.value = `${origenTexto} → ${destinoTexto}`;
                }

                input.focus();
            }
        }

        async function cargarRutasFavoritas() {
            // Esta función se implementa en favoritos.php
            console.log('Cargando rutas favoritas...');
        }
        <?php endif; ?>

        function manejarEdificioSeleccionado(event) {
            const { codigo, edificio } = event.detail;
            mostrarInfoEdificio(codigo, edificio);
            mostrarControlPisos(codigo, edificio.pisos);
        }

        function mostrarInfoEdificio(codigo, edificio) {
            const infoPanel = document.getElementById('infoEdificio');
            const content = document.getElementById('infoEdificioContent');

            content.innerHTML = `
            <h4 style="margin: 0 0 10px 0; color: #74b9ff;">🏢 ${edificio.nombre}</h4>
            <div style="font-size: 0.9rem; line-height: 1.4;">
                <div><strong>Código:</strong> ${codigo}</div>
                <div><strong>Pisos:</strong> ${edificio.pisos}</div>
                <div style="margin-top: 10px;">
                    <em>Haz clic en los controles de piso para explorar el edificio</em>
                </div>
            </div>
        `;

            infoPanel.style.display = 'block';
        }

        function mostrarControlPisos(codigo, totalPisos) {
            const controlPanel = document.getElementById('controlPisos');
            const botonesContainer = document.getElementById('botonespisos');

            botonesContainer.innerHTML = '';

            for (let piso = 1; piso <= totalPisos; piso++) {
                const boton = document.createElement('button');
                boton.textContent = `Piso ${piso}`;
                boton.style.cssText = `
                padding: 8px 15px;
                background: ${piso === 1 ? '#007bff' : '#f8f9fa'};
                color: ${piso === 1 ? 'white' : '#333'};
                border: 1px solid ${piso === 1 ? '#007bff' : '#dee2e6'};
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.3s;
            `;

                boton.addEventListener('click', () => {
                    // Actualizar estilos de botones
                    botonesContainer.querySelectorAll('button').forEach(b => {
                        b.style.background = '#f8f9fa';
                        b.style.color = '#333';
                        b.style.borderColor = '#dee2e6';
                    });

                    boton.style.background = '#007bff';
                    boton.style.color = 'white';
                    boton.style.borderColor = '#007bff';

                    // Cambiar piso en el mapa
                    mapaRealista.cambiarPiso(piso);
                });

                botonesContainer.appendChild(boton);
            }

            controlPanel.style.display = 'block';
        }

        function mostrarMensaje(mensaje, tipo = 'info', duracion = 4000) {
            const colores = {
                'success': { bg: '#d4edda', color: '#155724', border: '#c3e6cb', icon: 'check-circle' },
                'error': { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb', icon: 'exclamation-triangle' },
                'warning': { bg: '#fff3cd', color: '#856404', border: '#ffeaa7', icon: 'exclamation-circle' },
                'info': { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb', icon: 'info-circle' }
            };

            const estilo = colores[tipo] || colores.info;

            const div = document.createElement('div');
            div.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${estilo.bg};
            color: ${estilo.color};
            border: 1px solid ${estilo.border};
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            max-width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.4s ease-out;
            font-family: Arial, sans-serif;
        `;

            div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-${estilo.icon}" style="font-size: 1.2rem;"></i>
                <span style="flex: 1;">${mensaje}</span>
                <button onclick="this.parentElement.parentElement.remove()"
                        style="background: none; border: none; color: ${estilo.color}; cursor: pointer; font-size: 18px; padding: 0; margin-left: 10px;">
                    ×
                </button>
            </div>
        `;

            document.body.appendChild(div);

            if (duracion > 0) {
                setTimeout(() => {
                    if (div.parentElement) {
                        div.style.animation = 'slideOutRight 0.4s ease-in';
                        setTimeout(() => {
                            if (div.parentElement) div.remove();
                        }, 400);
                    }
                }, duracion);
            }
        }

        // Cleanup al salir de la página
        window.addEventListener('beforeunload', function() {
            if (mapaRealista) {
                mapaRealista.destruir();
            }
        });
    </script>

    <style>
        /* Estilos adicionales para la página */
        .btn-calcular:hover {
            background: #45a049 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-limpiar:hover {
            background: #da190b !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
        }

        .zoom-controls button:hover {
            background: white !important;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        #menuDropdown button:hover {
            background: #f8f9fa !important;
        }

        #mapaCanvas {
            transition: filter 0.3s ease;
        }

        #mapaCanvas:active {
            cursor: grabbing !important;
        }

        /* Animaciones */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mapa-controls > div:first-child {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            .zoom-controls {
                top: 10px !important;
                right: 10px !important;
            }

            .zoom-controls button {
                width: 35px !important;
                height: 35px !important;
                font-size: 16px !important;
            }

            #infoEdificio {
                bottom: 10px !important;
                left: 10px !important;
                right: 10px !important;
                max-width: none !important;
            }

            #controlPisos > div {
                padding: 10px !important;
            }

            #botonespisos {
                flex-wrap: wrap !important;
                justify-content: center !important;
            }
        }

        /* Mejoras visuales */
        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(116, 185, 255, 0.3);
        }

        #buscarLugar:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(116, 185, 255, 0.3);
        }

        /* Scrollbar personalizado para la información de ruta */
        .route-info div::-webkit-scrollbar {
            width: 6px;
        }

        .route-info div::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .route-info div::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .route-info div::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
    </style>

<?php include '../includes/footer.php'; ?>