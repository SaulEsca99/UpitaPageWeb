<?php
// Ruta: WEBupita/Public/favoritos.php
require_once '../includes/auth.php';
include '../includes/header.php';
?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <main class="content">
        <h1 class="page-title">
            <i class="fas fa-star"></i> Mis Rutas Favoritas
        </h1>

        <div class="favorites-intro" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <p style="margin: 0; color: #666; text-align: center;">
                Aquí puedes gestionar todas tus rutas favoritas guardadas.
                <a href="/WEBupita/pages/mapa-rutas.php" style="color: #007bff; text-decoration: none;">
                    <i class="fas fa-plus"></i> Crear nueva ruta favorita
                </a>
            </p>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="filters-section" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 15px; align-items: center;">
                <div>
                    <input type="text" id="buscarFavoritas" placeholder="Buscar en mis rutas favoritas..."
                           style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <select id="filtroEdificio" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Todos los edificios</option>
                        <option value="A-1">Edificio A1</option>
                        <option value="A-2">Edificio A2</option>
                        <option value="A-3">Edificio A3</option>
                        <option value="A-4">Edificio A4</option>
                        <option value="LC-">Laboratorio Central</option>
                        <option value="EG-">Edificio de Gobierno</option>
                        <option value="EP-">Laboratorios Pesados</option>
                    </select>
                </div>
                <div>
                    <button id="limpiarFiltros" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div id="estadisticasRapidas" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <!-- Se llenarán dinámicamente -->
        </div>

        <!-- Lista de rutas favoritas -->
        <div id="rutasFavoritasContainer">
            <div class="loading-spinner" style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                Cargando tus rutas favoritas...
            </div>
        </div>

        <!-- Modal para editar nombre de ruta -->
        <div id="editModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
            <div class="modal-content" style="background: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">Editar nombre de ruta</h3>
                    <span class="close" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="text" id="editRuteName" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;" placeholder="Nuevo nombre para la ruta">
                    <div style="text-align: right;">
                        <button id="cancelEdit" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px;">
                            Cancelar
                        </button>
                        <button id="saveEdit" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación para eliminar -->
        <div id="deleteModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
            <div class="modal-content" style="background: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #dc3545;">Confirmar eliminación</h3>
                    <span class="close-delete" style="font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage" style="margin-bottom: 20px;"></p>
                    <div style="text-align: right;">
                        <button id="cancelDelete" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px;">
                            Cancelar
                        </button>
                        <button id="confirmDelete" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let rutasFavoritas = [];
        let rutasFiltradas = [];
        let rutaEditando = null;
        let rutaEliminando = null;

        document.addEventListener('DOMContentLoaded', function() {
            cargarRutasFavoritas();

            // Event listeners para filtros
            document.getElementById('buscarFavoritas').addEventListener('input', filtrarRutas);
            document.getElementById('filtroEdificio').addEventListener('change', filtrarRutas);
            document.getElementById('limpiarFiltros').addEventListener('click', limpiarFiltros);

            // Event listeners para modal de edición
            document.querySelector('.close').addEventListener('click', cerrarModalEditar);
            document.getElementById('cancelEdit').addEventListener('click', cerrarModalEditar);
            document.getElementById('saveEdit').addEventListener('click', guardarEdicion);

            // Event listeners para modal de eliminación
            document.querySelector('.close-delete').addEventListener('click', cerrarModalEliminar);
            document.getElementById('cancelDelete').addEventListener('click', cerrarModalEliminar);
            document.getElementById('confirmDelete').addEventListener('click', confirmarEliminacion);

            // Cerrar modales al hacer clic fuera
            window.addEventListener('click', function(e) {
                const editModal = document.getElementById('editModal');
                const deleteModal = document.getElementById('deleteModal');

                if (e.target === editModal) {
                    cerrarModalEditar();
                }
                if (e.target === deleteModal) {
                    cerrarModalEliminar();
                }
            });

            // Atajos de teclado
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    cerrarModalEditar();
                    cerrarModalEliminar();
                }
            });
        });

        async function cargarRutasFavoritas() {
            try {
                const response = await fetch('/WEBupita/api/rutas_favoritas.php');

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    rutasFavoritas = data.rutas || [];
                    rutasFiltradas = [...rutasFavoritas];
                    mostrarEstadisticas();
                    mostrarRutasFavoritas();
                } else {
                    mostrarError('Error al cargar rutas favoritas: ' + (data.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar las rutas favoritas');
            }
        }

        function mostrarEstadisticas() {
            const container = document.getElementById('estadisticasRapidas');
            const total = rutasFavoritas.length;

            // Edificios más utilizados
            const edificiosCount = {};
            rutasFavoritas.forEach(ruta => {
                const origenEdificio = ruta.origen_codigo ? ruta.origen_codigo.split('-')[0] : 'N/A';
                const destinoEdificio = ruta.destino_codigo ? ruta.destino_codigo.split('-')[0] : 'N/A';

                edificiosCount[origenEdificio] = (edificiosCount[origenEdificio] || 0) + 1;
                edificiosCount[destinoEdificio] = (edificiosCount[destinoEdificio] || 0) + 1;
            });

            const edificioMasUsado = Object.keys(edificiosCount).reduce((a, b) =>
                edificiosCount[a] > edificiosCount[b] ? a : b, 'N/A');

            // Ruta más reciente
            const rutaMasReciente = rutasFavoritas.length > 0 ?
                new Date(rutasFavoritas[0].fecha_creacion).toLocaleDateString('es-ES') : 'N/A';

            container.innerHTML = `
            <div class="stat-card" style="background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff;">
                <div style="font-size: 2rem; font-weight: bold; color: #007bff; margin-bottom: 5px;">${total}</div>
                <div style="color: #666;">Rutas Favoritas</div>
            </div>
            <div class="stat-card" style="background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #28a745;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745; margin-bottom: 5px;">${edificioMasUsado}</div>
                <div style="color: #666;">Edificio Más Usado</div>
            </div>
            <div class="stat-card" style="background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #ffc107;">
                <div style="font-size: 1.2rem; font-weight: bold; color: #ffc107; margin-bottom: 5px;">${rutaMasReciente}</div>
                <div style="color: #666;">Última Ruta Creada</div>
            </div>
        `;
        }

        function mostrarRutasFavoritas() {
            const container = document.getElementById('rutasFavoritasContainer');

            if (rutasFiltradas.length === 0) {
                const mensaje = rutasFavoritas.length === 0 ?
                    'No tienes rutas favoritas' :
                    'No se encontraron rutas con esos filtros';

                container.innerHTML = `
                <div class="no-favorites" style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-route" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
                    <h3 style="color: #999; margin-bottom: 10px;">${mensaje}</h3>
                    <p>Crea tu primera ruta favorita usando el
                       <a href="/WEBupita/pages/mapa-rutas.php" style="color: #007bff;">mapa interactivo con rutas</a>
                    </p>
                </div>
            `;
                return;
            }

            let html = '';

            rutasFiltradas.forEach((ruta, index) => {
                const fechaCreacion = new Date(ruta.fecha_creacion);
                const fechaTexto = fechaCreacion.toLocaleDateString('es-ES');
                const horaTexto = fechaCreacion.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

                html += `
                <div class="favorite-route-card" id="ruta-${ruta.id}" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #007bff; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <h3 class="route-name" style="margin: 0 0 8px 0; color: #003366; font-size: 1.2rem;">
                                <i class="fas fa-star" style="color: #ffc107; margin-right: 8px;"></i>
                                ${ruta.nombre_ruta}
                            </h3>
                            <div class="route-path" style="color: #666; margin-bottom: 8px;">
                                <i class="fas fa-map-marker-alt" style="color: #28a745;"></i>
                                <span class="origin-text">${ruta.origen_codigo || 'N/A'} - ${ruta.origen_nombre || 'Origen desconocido'}</span>
                                <i class="fas fa-arrow-right" style="margin: 0 10px; color: #007bff;"></i>
                                <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                                <span class="destination-text">${ruta.destino_codigo || 'N/A'} - ${ruta.destino_nombre || 'Destino desconocido'}</span>
                            </div>
                            <div class="route-meta" style="font-size: 0.9rem; color: #999;">
                                <i class="fas fa-calendar-alt"></i> Creada el ${fechaTexto} a las ${horaTexto}
                            </div>
                        </div>
                        <div class="route-actions" style="display: flex; gap: 8px; align-items: flex-start;">
                            <button class="btn-load-route" onclick="cargarRutaEnMapa(${ruta.id}, '${ruta.origen_tipo}_${ruta.origen_id}', '${ruta.destino_tipo}_${ruta.destino_id}', '${ruta.nombre_ruta.replace(/'/g, '\\\'')}')"
                                    style="padding: 8px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;"
                                    title="Cargar ruta en el mapa">
                                <i class="fas fa-route"></i> Cargar
                            </button>
                            <button class="btn-edit-route" onclick="editarRuta(${ruta.id}, '${ruta.nombre_ruta.replace(/'/g, '\\\'')}')"
                                    style="padding: 8px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;"
                                    title="Editar nombre">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete-route" onclick="eliminarRuta(${ruta.id}, '${ruta.nombre_ruta.replace(/'/g, '\\\'')}')"
                                    style="padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;"
                                    title="Eliminar ruta">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Información adicional expandible -->
                    <div class="route-details" style="border-top: 1px solid #eee; padding-top: 15px; display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                            <div>
                                <strong style="color: #003366;">Información de Origen:</strong>
                                <div class="origin-info" style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                    <div><strong>Código:</strong> ${ruta.origen_codigo || 'N/A'}</div>
                                    <div><strong>Nombre:</strong> ${ruta.origen_nombre || 'Desconocido'}</div>
                                    <div><strong>Tipo:</strong> ${ruta.origen_tipo}</div>
                                </div>
                            </div>
                            <div>
                                <strong style="color: #003366;">Información de Destino:</strong>
                                <div class="destination-info" style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                    <div><strong>Código:</strong> ${ruta.destino_codigo || 'N/A'}</div>
                                    <div><strong>Nombre:</strong> ${ruta.destino_nombre || 'Desconocido'}</div>
                                    <div><strong>Tipo:</strong> ${ruta.destino_tipo}</div>
                                </div>
                            </div>
                        </div>
                        <div style="background: #e8f5e8; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                            <strong style="color: #155724;">Fecha completa de creación:</strong>
                            <div style="color: #155724;">${fechaCreacion.toLocaleString('es-ES')}</div>
                        </div>
                        <button class="btn-toggle-details" onclick="ocultarDetalles(this)" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.9rem;">
                            <i class="fas fa-chevron-up"></i> Ocultar detalles
                        </button>
                    </div>

                    <button class="btn-show-details" onclick="mostrarDetalles(this)" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-chevron-down"></i> Ver detalles
                    </button>

                    <!-- Indicador de orden -->
                    <div class="route-number" style="position: absolute; top: -5px; right: -5px; background: #007bff; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                        ${index + 1}
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        function filtrarRutas() {
            const busqueda = document.getElementById('buscarFavoritas').value.toLowerCase();
            const edificio = document.getElementById('filtroEdificio').value;

            rutasFiltradas = rutasFavoritas.filter(ruta => {
                const matchBusqueda = !busqueda ||
                    (ruta.nombre_ruta && ruta.nombre_ruta.toLowerCase().includes(busqueda)) ||
                    (ruta.origen_codigo && ruta.origen_codigo.toLowerCase().includes(busqueda)) ||
                    (ruta.destino_codigo && ruta.destino_codigo.toLowerCase().includes(busqueda)) ||
                    (ruta.origen_nombre && ruta.origen_nombre.toLowerCase().includes(busqueda)) ||
                    (ruta.destino_nombre && ruta.destino_nombre.toLowerCase().includes(busqueda));

                const matchEdificio = !edificio ||
                    (ruta.origen_codigo && ruta.origen_codigo.startsWith(edificio)) ||
                    (ruta.destino_codigo && ruta.destino_codigo.startsWith(edificio));

                return matchBusqueda && matchEdificio;
            });

            mostrarRutasFavoritas();
        }

        function limpiarFiltros() {
            document.getElementById('buscarFavoritas').value = '';
            document.getElementById('filtroEdificio').value = '';
            filtrarRutas();
        }

        function mostrarDetalles(button) {
            const card = button.closest('.favorite-route-card');
            const details = card.querySelector('.route-details');
            const showBtn = card.querySelector('.btn-show-details');

            details.style.display = 'block';
            showBtn.style.display = 'none';

            // Animación suave
            details.style.animation = 'fadeInDown 0.3s ease-out';
        }

        function ocultarDetalles(button) {
            const card = button.closest('.favorite-route-card');
            const details = card.querySelector('.route-details');
            const showBtn = card.querySelector('.btn-show-details');

            details.style.animation = 'fadeOutUp 0.3s ease-in';
            setTimeout(() => {
                details.style.display = 'none';
                showBtn.style.display = 'block';
            }, 300);
        }

        function cargarRutaEnMapa(rutaId, origen, destino, nombre) {
            // Mostrar indicador de carga
            mostrarMensaje('Cargando ruta en el mapa...', 'info', 1000);

            // Usar sessionStorage para pasar los datos
            sessionStorage.setItem('cargarRuta', JSON.stringify({
                origen: origen,
                destino: destino,
                nombre: nombre,
                desde_favoritos: true
            }));

            // Agregar efecto visual a la tarjeta
            const card = document.getElementById(`ruta-${rutaId}`);
            if (card) {
                card.style.transform = 'scale(0.98)';
                card.style.opacity = '0.7';
                setTimeout(() => {
                    window.location.href = '/WEBupita/pages/mapa-rutas.php';
                }, 200);
            } else {
                window.location.href = '/WEBupita/pages/mapa-rutas.php';
            }
        }

        function editarRuta(rutaId, nombreActual) {
            rutaEditando = { id: rutaId, nombre: nombreActual };
            document.getElementById('editRuteName').value = nombreActual;
            document.getElementById('editModal').style.display = 'block';

            // Focus en el input
            setTimeout(() => {
                document.getElementById('editRuteName').focus();
                document.getElementById('editRuteName').select();
            }, 100);
        }

        function eliminarRuta(rutaId, nombreRuta) {
            rutaEliminando = { id: rutaId, nombre: nombreRuta };
            document.getElementById('deleteMessage').textContent =
                `¿Estás seguro de que quieres eliminar la ruta "${nombreRuta}"?`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        async function guardarEdicion() {
            const nuevoNombre = document.getElementById('editRuteName').value.trim();

            if (!nuevoNombre) {
                mostrarMensaje('Por favor ingresa un nombre para la ruta', 'error');
                return;
            }

            if (nuevoNombre === rutaEditando.nombre) {
                cerrarModalEditar();
                return;
            }

            if (!rutaEditando) {
                mostrarMensaje('Error: No hay ruta seleccionada para editar', 'error');
                return;
            }

            // Mostrar indicador de carga
            const saveBtn = document.getElementById('saveEdit');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            saveBtn.disabled = true;

            try {
                const response = await fetch('/WEBupita/api/rutas_favoritas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_name',
                        ruta_id: rutaEditando.id,
                        nombre_ruta: nuevoNombre
                    })
                });

                const data = await response.json();

                if (data.success) {
                    cerrarModalEditar();
                    cargarRutasFavoritas();
                    mostrarMensaje('Nombre de ruta actualizado exitosamente', 'success');
                } else {
                    mostrarMensaje('Error al actualizar el nombre: ' + (data.error || 'Error desconocido'), 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión al actualizar el nombre de la ruta', 'error');
            } finally {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        }

        async function confirmarEliminacion() {
            if (!rutaEliminando) {
                mostrarMensaje('Error: No hay ruta seleccionada para eliminar', 'error');
                return;
            }

            // Mostrar indicador de carga
            const deleteBtn = document.getElementById('confirmDelete');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
            deleteBtn.disabled = true;

            try {
                const response = await fetch('/WEBupita/api/rutas_favoritas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ruta_id: rutaEliminando.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    cerrarModalEliminar();

                    // Efecto visual de eliminación
                    const card = document.getElementById(`ruta-${rutaEliminando.id}`);
                    if (card) {
                        card.style.animation = 'slideOutRight 0.3s ease-in';
                        setTimeout(() => {
                            cargarRutasFavoritas();
                        }, 300);
                    } else {
                        cargarRutasFavoritas();
                    }

                    mostrarMensaje('Ruta eliminada exitosamente', 'success');
                } else {
                    mostrarMensaje('Error al eliminar la ruta: ' + (data.error || 'Error desconocido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión al eliminar la ruta', 'error');
            } finally {
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
            }
        }

        function cerrarModalEditar() {
            document.getElementById('editModal').style.display = 'none';
            rutaEditando = null;
            document.getElementById('editRuteName').value = '';
        }

        function cerrarModalEliminar() {
            document.getElementById('deleteModal').style.display = 'none';
            rutaEliminando = null;
        }

        function mostrarError(mensaje) {
            const container = document.getElementById('rutasFavoritasContainer');
            container.innerHTML = `
            <div class="error-message" style="text-align: center; padding: 40px; color: #dc3545; background: #f8d7da; border-radius: 8px; border: 1px solid #f5c6cb;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p style="margin-bottom: 15px;">${mensaje}</p>
                <button onclick="cargarRutasFavoritas()" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-refresh"></i> Reintentar
                </button>
            </div>
        `;
        }

        function mostrarMensaje(mensaje, tipo, duracion = 3000) {
            const colores = {
                'success': { bg: '#d4edda', color: '#155724', border: '#c3e6cb' },
                'error': { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb' },
                'warning': { bg: '#fff3cd', color: '#856404', border: '#ffeaa7' },
                'info': { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb' }
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
            border-radius: 4px;
            z-index: 1001;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;

            div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${mensaje}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: ${estilo.color}; cursor: pointer; margin-left: 10px; font-size: 18px;">
                    &times;
                </button>
            </div>
        `;

            document.body.appendChild(div);

            if (duracion > 0) {
                setTimeout(() => {
                    if (div.parentElement) {
                        div.style.animation = 'slideOut 0.3s ease-in';
                        setTimeout(() => {
                            if (div.parentElement) {
                                div.remove();
                            }
                        }, 300);
                    }
                }, duracion);
            }
        }

        // CSS para animaciones
        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @keyframes fadeInDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeOutUp {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-10px); opacity: 0; }
        }

        .btn-load-route:hover {
            background: #218838 !important;
            transform: translateY(-1px);
        }

        .btn-edit-route:hover {
            background: #0056b3 !important;
            transform: translateY(-1px);
        }

        .btn-delete-route:hover {
            background: #c82333 !important;
            transform: translateY(-1px);
        }

        .favorite-route-card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
        }

        .favorite-route-card:hover .route-number {
            background: #0056b3;
        }

        .modal {
            backdrop-filter: blur(2px);
        }

        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .loading-spinner i {
            color: #007bff;
        }

        .filters-section {
            border: 1px solid #e9ecef;
        }

        #buscarFavoritas:focus,
        #filtroEdificio:focus,
        #editRuteName:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .no-favorites a:hover {
            text-decoration: underline;
        }

        .route-actions button {
            transition: all 0.2s ease;
        }

        .route-actions button:active {
            transform: scale(0.95);
        }

        .stat-card {
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .route-path .origin-text:hover,
        .route-path .destination-text:hover {
            background: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .route-number {
            transition: all 0.2s ease;
        }

        @media (max-width: 768px) {
            .favorite-route-card {
                padding: 15px;
            }

            .route-actions {
                flex-direction: column;
                gap: 5px !important;
            }

            .route-actions button {
                width: 100%;
                font-size: 0.8rem !important;
                padding: 6px 10px !important;
            }

            .route-details > div {
                grid-template-columns: 1fr !important;
            }

            .filters-section > div {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            #estadisticasRapidas {
                grid-template-columns: 1fr !important;
            }
        }
    `;
        document.head.appendChild(style);
    </script>

<?php include '../includes/footer.php'; ?>