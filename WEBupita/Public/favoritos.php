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
                        <option value="A1">Edificio A1</option>
                        <option value="A2">Edificio A2</option>
                        <option value="A3">Edificio A3</option>
                        <option value="A4">Edificio A4</option>
                        <option value="LC">Laboratorio Central</option>
                        <option value="EG">Edificio de Gobierno</option>
                        <option value="EP">Laboratorios Pesados</option>
                    </select>
                </div>
                <div>
                    <button id="limpiarFiltros" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de rutas favoritas -->
        <div id="rutasFavoritasContainer">
            <div class="loading-spinner" style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                Cargando tus rutas favoritas...
            </div>
        </div>

        <!-- Template para rutas favoritas -->
        <template id="rutaFavoritaTemplate">
            <div class="favorite-route-card" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #007bff;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <h3 class="route-name" style="margin: 0 0 8px 0; color: #003366; font-size: 1.2rem;"></h3>
                        <div class="route-path" style="color: #666; margin-bottom: 8px;"></div>
                        <div class="route-meta" style="font-size: 0.9rem; color: #999;"></div>
                    </div>
                    <div class="route-actions" style="display: flex; gap: 8px;">
                        <button class="btn-load-route" style="padding: 8px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Cargar ruta en el mapa">
                            <i class="fas fa-route"></i> Cargar
                        </button>
                        <button class="btn-edit-route" style="padding: 8px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Editar nombre">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete-route" style="padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Eliminar ruta">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Información adicional expandible -->
                <div class="route-details" style="border-top: 1px solid #eee; padding-top: 15px; display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div>
                            <strong>Origen:</strong>
                            <div class="origin-info"></div>
                        </div>
                        <div>
                            <strong>Destino:</strong>
                            <div class="destination-info"></div>
                        </div>
                        <div>
                            <strong>Fecha de creación:</strong>
                            <div class="creation-date"></div>
                        </div>
                    </div>
                    <button class="btn-toggle-details" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.9rem;">
                        <i class="fas fa-chevron-up"></i> Ocultar detalles
                    </button>
                </div>

                <button class="btn-show-details" style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.9rem; margin-top: 10px;">
                    <i class="fas fa-chevron-down"></i> Ver detalles
                </button>
            </div>
        </template>

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
    </main>

    <script>
        let rutasFavoritas = [];
        let rutasFiltradas = [];
        let rutaEditando = null;

        document.addEventListener('DOMContentLoaded', function() {
            cargarRutasFavoritas();

            // Event listeners
            document.getElementById('buscarFavoritas').addEventListener('input', filtrarRutas);
            document.getElementById('filtroEdificio').addEventListener('change', filtrarRutas);
            document.getElementById('limpiarFiltros').addEventListener('click', limpiarFiltros);

            // Modal de edición
            document.querySelector('.close').addEventListener('click', cerrarModal);
            document.getElementById('cancelEdit').addEventListener('click', cerrarModal);
            document.getElementById('saveEdit').addEventListener('click', guardarEdicion);

            // Cerrar modal al hacer clic fuera
            window.addEventListener('click', function(e) {
                const modal = document.getElementById('editModal');
                if (e.target === modal) {
                    cerrarModal();
                }
            });
        });

        async function cargarRutasFavoritas() {
            try {
                const response = await fetch('/WEBupita/api/rutas_favoritas.php');
                const data = await response.json();

                if (data.success) {
                    rutasFavoritas = data.rutas;
                    rutasFiltradas = [...rutasFavoritas];
                    mostrarRutasFavoritas();
                } else {
                    mostrarError('Error al cargar rutas favoritas: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar las rutas favoritas');
            }
        }

        function mostrarRutasFavoritas() {
            const container = document.getElementById('rutasFavoritasContainer');

            if (rutasFiltradas.length === 0) {
                container.innerHTML = `
            <div class="no-favorites" style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-route" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
                <h3 style="color: #999; margin-bottom: 10px;">No tienes rutas favoritas</h3>
                <p>Crea tu primera ruta favorita usando el
                   <a href="/WEBupita/pages/mapa-rutas.php" style="color: #007bff;">mapa interactivo</a>
                </p>
            </div>
        `;
                return;
            }

            const template = document.getElementById('rutaFavoritaTemplate');
            let html = '';

            rutasFiltradas.forEach(ruta => {
                const clone = template.content.cloneNode(true);

                // Configurar datos de la ruta
                clone.querySelector('.route-name').textContent = ruta.nombre_ruta;
                clone.querySelector('.route-path').innerHTML = `
            <i class="fas fa-map-marker-alt" style="color: #28a745;"></i> ${ruta.origen_codigo} - ${ruta.origen_nombre}
            <i class="fas fa-arrow-right" style="margin: 0 10px; color: #007bff;"></i>
            <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i> ${ruta.destino_codigo} - ${ruta.destino_nombre}
        `;
                clone.querySelector('.route-meta').textContent = `Creada el ${new Date(ruta.fecha_creacion).toLocaleDateString('es-ES')}`;

                // Información detallada
                clone.querySelector('.origin-info').textContent = `${ruta.origen_codigo} - ${ruta.origen_nombre}`;
                clone.querySelector('.destination-info').textContent = `${ruta.destino_codigo} - ${ruta.destino_nombre}`;
                clone.querySelector('.creation-date').textContent = new Date(ruta.fecha_creacion).toLocaleString('es-ES');

                // Event listeners para botones
                clone.querySelector('.btn-load-route').addEventListener('click', () => cargarRutaEnMapa(ruta));
                clone.querySelector('.btn-edit-route').addEventListener('click', () => editarRuta(ruta));
                clone.querySelector('.btn-delete-route').addEventListener('click', () => eliminarRuta(ruta));
                clone.querySelector('.btn-show-details').addEventListener('click', (e) => mostrarDetalles(e.target));
                clone.querySelector('.btn-toggle-details').addEventListener('click', (e) => ocultarDetalles(e.target));

                // Convertir a HTML string
                const div = document.createElement('div');
                div.appendChild(clone);
                html += div.innerHTML;
            });

            container.innerHTML = html;
        }

        function filtrarRutas() {
            const busqueda = document.getElementById('buscarFavoritas').value.toLowerCase();
            const edificio = document.getElementById('filtroEdificio').value;

            rutasFiltradas = rutasFavoritas.filter(ruta => {
                const matchBusqueda = !busqueda ||
                    ruta.nombre_ruta.toLowerCase().includes(busqueda) ||
                    ruta.origen_codigo.toLowerCase().includes(busqueda) ||
                    ruta.destino_codigo.toLowerCase().includes(busqueda) ||
                    ruta.origen_nombre.toLowerCase().includes(busqueda) ||
                    ruta.destino_nombre.toLowerCase().includes(busqueda);

                const matchEdificio = !edificio ||
                    ruta.origen_codigo.startsWith(edificio) ||
                    ruta.destino_codigo.startsWith(edificio);

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
        }

        function ocultarDetalles(button) {
            const card = button.closest('.favorite-route-card');
            const details = card.querySelector('.route-details');
            const showBtn = card.querySelector('.btn-show-details');

            details.style.display = 'none';
            showBtn.style.display = 'block';
        }

        function cargarRutaEnMapa(ruta) {
            // Redirigir al mapa con parámetros de la ruta
            const origen = `${ruta.origen_tipo}_${ruta.origen_id}`;
            const destino = `${ruta.destino_tipo}_${ruta.destino_id}`;

            // Usar sessionStorage para pasar los datos
            sessionStorage.setItem('cargarRuta', JSON.stringify({
                origen: origen,
                destino: destino,
                nombre: ruta.nombre_ruta
            }));

            window.location.href = '/WEBupita/pages/mapa-rutas.php';
        }

        function editarRuta(ruta) {
            rutaEditando = ruta;
            document.getElementById('editRuteName').value = ruta.nombre_ruta;
            document.getElementById('editModal').style.display = 'block';
        }

        async function guardarEdicion() {
            const nuevoNombre = document.getElementById('editRuteName').value.trim();

            if (!nuevoNombre) {
                alert('Por favor ingresa un nombre para la ruta');
                return;
            }

            try {
                const response = await fetch(`/WEBupita/api/rutas_favoritas.php`, {
                    method: 'PUT', // Simularemos PUT con POST y parámetro
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update',
                        ruta_id: rutaEditando.id,
                        nombre_ruta: nuevoNombre
                    })
                });

                // Como PHP no maneja PUT fácilmente, usaremos una query UPDATE directa
                // Por simplicidad, actualizaremos localmente y recargaremos
                await actualizarNombreRuta(rutaEditando.id, nuevoNombre);

                cerrarModal();
                cargarRutasFavoritas();

                mostrarMensaje('Nombre de ruta actualizado exitosamente', 'success');

            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error al actualizar el nombre de la ruta', 'error');
            }
        }

        async function actualizarNombreRuta(rutaId, nuevoNombre) {
            // Hacer una petición especial para actualizar el nombre
            const formData = new FormData();
            formData.append('action', 'update_name');
            formData.append('ruta_id', rutaId);
            formData.append('nombre_ruta', nuevoNombre);

            const response = await fetch('/WEBupita/api/rutas_favoritas.php', {
                method: 'POST',
                body: formData
            });

            return response.json();
        }

        async function eliminarRuta(ruta) {
            if (!confirm(`¿Estás seguro de que quieres eliminar la ruta "${ruta.nombre_ruta}"?`)) {
                return;
            }

            try {
                const response = await fetch('/WEBupita/api/rutas_favoritas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ruta_id: ruta.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarMensaje('Ruta eliminada exitosamente', 'success');
                    cargarRutasFavoritas();
                } else {
                    mostrarMensaje('Error al eliminar la ruta: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error de conexión al eliminar la ruta', 'error');
            }
        }

        function cerrarModal() {
            document.getElementById('editModal').style.display = 'none';
            rutaEditando = null;
        }

        function mostrarError(mensaje) {
            const container = document.getElementById('rutasFavoritasContainer');
            container.innerHTML = `
        <div class="error-message" style="text-align: center; padding: 40px; color: #dc3545; background: #f8d7da; border-radius: 8px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <p>${mensaje}</p>
            <button onclick="cargarRutasFavoritas()" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Reintentar
            </button>
        </div>
    `;
        }

        function mostrarMensaje(mensaje, tipo) {
            const color = tipo === 'success' ? '#28a745' : '#dc3545';
            const backgroundColor = tipo === 'success' ? '#d4edda' : '#f8d7da';

            const div = document.createElement('div');
            div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${backgroundColor};
        color: ${color};
        padding: 15px 20px;
        border-radius: 4px;
        border-left: 4px solid ${color};
        z-index: 1001;
        max-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
            div.textContent = mensaje;

            document.body.appendChild(div);

            setTimeout(() => {
                div.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => div.remove(), 300);
            }, 3000);
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
`;
        document.head.appendChild(style);
    </script>

<?php include '../includes/footer.php'; ?>