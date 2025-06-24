// Ruta: WEBupita/js/script.js

// Variables globales
let edificiosData = [];
let aulasData = [];

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('UPIITA JavaScript cargado');

    // Cargar datos iniciales
    cargarEdificios();

    // Inicializar funcionalidades específicas de página
    initializePageFunctions();

    // Event listeners globales
    setupGlobalEventListeners();
});

// Funciones principales
async function cargarEdificios() {
    try {
        const response = await fetch('/WEBupita/api/get_edificios.php');
        const data = await response.json();

        if (data.success) {
            edificiosData = data.data;
            console.log('Edificios cargados:', edificiosData.length);
        } else {
            console.error('Error cargando edificios:', data.error);
        }
    } catch (error) {
        console.error('Error en petición de edificios:', error);
    }
}

async function cargarAulas(edificioId = null, piso = null) {
    try {
        let url = '/WEBupita/api/get_aulas.php';
        const params = new URLSearchParams();

        if (edificioId) params.append('edificio_id', edificioId);
        if (piso) params.append('piso', piso);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            aulasData = data.data;
            console.log('Aulas cargadas:', aulasData.length);
            return aulasData;
        } else {
            console.error('Error cargando aulas:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error en petición de aulas:', error);
        return [];
    }
}

// Funciones de utilidad
function mostrarCargando(elemento, mostrar = true) {
    if (typeof elemento === 'string') {
        elemento = document.getElementById(elemento);
    }

    if (elemento) {
        if (mostrar) {
            elemento.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        } else {
            // Remover indicador de carga
            const loading = elemento.querySelector('.loading');
            if (loading) {
                loading.remove();
            }
        }
    }
}

function mostrarError(mensaje, elemento = null) {
    console.error(mensaje);

    if (elemento) {
        if (typeof elemento === 'string') {
            elemento = document.getElementById(elemento);
        }

        if (elemento) {
            elemento.innerHTML = `
                <div class="error-message" style="color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;">
                    <i class="fas fa-exclamation-triangle"></i> ${mensaje}
                </div>
            `;
        }
    }
}

function mostrarMensaje(mensaje, tipo = 'info', duracion = 3000) {
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
        z-index: 1000;
        max-width: 300px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease-out;
    `;

    div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${mensaje}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: ${estilo.color}; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(div);

    if (duracion > 0) {
        setTimeout(() => {
            if (div.parentElement) {
                div.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => div.remove(), 300);
            }
        }, duracion);
    }
}

// Funciones específicas de página
function initializePageFunctions() {
    const currentPage = window.location.pathname;

    if (currentPage.includes('mapa-interactivo')) {
        initializeMapaInteractivo();
    }

    if (currentPage.includes('mapa-rutas')) {
        initializeMapaRutas();
    }

    if (currentPage.includes('favoritos')) {
        initializeFavoritos();
    }
}

function initializeMapaInteractivo() {
    console.log('Inicializando mapa interactivo básico');
    // Funcionalidad específica para el mapa básico
}

function initializeMapaRutas() {
    console.log('Inicializando mapa con rutas');
    // Funcionalidad específica para el mapa con rutas
}

function initializeFavoritos() {
    console.log('Inicializando página de favoritos');
    // Funcionalidad específica para favoritos
}

// Event listeners globales
function setupGlobalEventListeners() {
    // Manejar errores de carga de imágenes
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            console.warn('Error cargando imagen:', e.target.src);
            e.target.style.display = 'none';
        }
    }, true);

    // Manejar clics en enlaces de navegación
    document.addEventListener('click', function(e) {
        if (e.target.matches('a[href^="/WEBupita"]')) {
            // Aquí podrías agregar lógica especial para navegación
            console.log('Navegando a:', e.target.href);
        }
    });
}

// Funciones de búsqueda y filtrado
function buscarEnDatos(termino, datos, campos = []) {
    if (!termino || termino.length < 2) return datos;

    const terminoLower = termino.toLowerCase();

    return datos.filter(item => {
        if (campos.length === 0) {
            // Buscar en todas las propiedades del objeto
            return Object.values(item).some(valor =>
                String(valor).toLowerCase().includes(terminoLower)
            );
        } else {
            // Buscar solo en los campos especificados
            return campos.some(campo =>
                item[campo] && String(item[campo]).toLowerCase().includes(terminoLower)
            );
        }
    });
}

function filtrarPorEdificio(datos, edificioId) {
    if (!edificioId) return datos;
    return datos.filter(item => item.idEdificio == edificioId);
}

function filtrarPorPiso(datos, piso) {
    if (!piso) return datos;
    return datos.filter(item => item.piso == piso);
}

// Funciones de formato y visualización
function formatearNombreAula(aula) {
    return `${aula.numeroAula} - ${aula.nombreAula}`;
}

function formatearNombreEdificio(edificio) {
    return edificio.nombre;
}

function obtenerIconoTipo(tipo) {
    const iconos = {
        'aula': 'fas fa-door-open',
        'laboratorio': 'fas fa-flask',
        'oficina': 'fas fa-building',
        'biblioteca': 'fas fa-book',
        'auditorio': 'fas fa-users',
        'sala': 'fas fa-users',
        'default': 'fas fa-map-marker-alt'
    };

    // Buscar coincidencia en el tipo
    for (const [clave, icono] of Object.entries(iconos)) {
        if (tipo.toLowerCase().includes(clave)) {
            return icono;
        }
    }

    return iconos.default;
}

// Funciones de validación
function validarFormulario(formulario) {
    const campos = formulario.querySelectorAll('input[required], select[required], textarea[required]');
    let valido = true;

    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.classList.add('error');
            valido = false;
        } else {
            campo.classList.remove('error');
        }
    });

    return valido;
}

// Funciones de localStorage (para funcionalidad offline)
function guardarEnLocalStorage(clave, datos) {
    try {
        localStorage.setItem(`upiita_${clave}`, JSON.stringify(datos));
        return true;
    } catch (error) {
        console.warn('No se pudo guardar en localStorage:', error);
        return false;
    }
}

function cargarDeLocalStorage(clave) {
    try {
        const datos = localStorage.getItem(`upiita_${clave}`);
        return datos ? JSON.parse(datos) : null;
    } catch (error) {
        console.warn('No se pudo cargar de localStorage:', error);
        return null;
    }
}

function limpiarLocalStorage() {
    try {
        Object.keys(localStorage).forEach(clave => {
            if (clave.startsWith('upiita_')) {
                localStorage.removeItem(clave);
            }
        });
        console.log('LocalStorage limpiado');
    } catch (error) {
        console.warn('Error limpiando localStorage:', error);
    }
}

// Exportar funciones para uso global
window.UPIITAUtils = {
    cargarEdificios,
    cargarAulas,
    mostrarCargando,
    mostrarError,
    mostrarMensaje,
    buscarEnDatos,
    filtrarPorEdificio,
    filtrarPorPiso,
    formatearNombreAula,
    formatearNombreEdificio,
    obtenerIconoTipo,
    validarFormulario,
    guardarEnLocalStorage,
    cargarDeLocalStorage,
    limpiarLocalStorage
};

// CSS adicional para efectos
const style = document.createElement('style');
style.textContent = `
    .loading {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .error-message {
        animation: shake 0.5s ease-in-out;
    }
    
    .error {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
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
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .fa-spin {
        animation: fa-spin 1s infinite linear;
    }
    
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;

document.head.appendChild(style);