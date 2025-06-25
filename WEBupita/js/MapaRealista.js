// Ruta: WEBupita/js/MapaRealista.js
// Sistema de mapa realista basado en las imágenes de UPIITA

class MapaRealista {
    constructor(canvasId, baseUrl = '') {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.baseUrl = baseUrl;

        // Estado del mapa
        this.zoom = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.isDragging = false;
        this.lastMouseX = 0;
        this.lastMouseY = 0;

        // Datos del mapa
        this.edificios = {};
        this.aulas = {};
        this.rutaActual = null;
        this.pisoActual = 0; // 0 = vista general, 1+ = pisos específicos
        this.edificioSeleccionado = null;

        // Configuración visual
        this.config = {
            colores: {
                fondo: '#f0f8ff',
                pasto: '#90EE90',
                caminos: '#D3D3D3',
                sombras: 'rgba(0,0,0,0.3)',
                texto: '#333333',
                ruta: '#007bff',
                origen: '#28a745',
                destino: '#dc3545',
                hover: 'rgba(255,255,0,0.3)'
            },
            fuentes: {
                edificio: 'bold 14px Arial',
                aula: '12px Arial',
                info: '10px Arial'
            },
            efectos: {
                sombraEdificio: 5,
                bordeRedondeo: 4,
                anchoRuta: 4
            }
        };

        this.inicializar();
    }

    async inicializar() {
        await this.cargarDatos();
        this.configurarEventos();
        this.ajustarCanvas();
        this.dibujarMapa();
    }

    async cargarDatos() {
        try {
            // Cargar coordenadas de edificios desde la API
            const response = await fetch(`${this.baseUrl}/api/mapa_coordenadas.php`);
            const data = await response.json();

            if (data.success) {
                this.edificios = data.edificios;
                this.aulas = data.aulas;
            } else {
                console.error('Error cargando datos del mapa:', data.error);
                this.usarDatosPorDefecto();
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            this.usarDatosPorDefecto();
        }
    }

    usarDatosPorDefecto() {
        // Datos por defecto basados en las imágenes
        this.edificios = {
            'A1': {
                x: 300, y: 400, width: 120, height: 80,
                color: '#3498db', nombre: 'Edificio A1 - Aulas 1',
                pisos: 3, entrada: { x: 360, y: 480 }
            },
            'A2': {
                x: 450, y: 350, width: 120, height: 80,
                color: '#e91e63', nombre: 'Edificio A2 - Aulas 2',
                pisos: 3, entrada: { x: 510, y: 430 }
            },
            'A3': {
                x: 200, y: 250, width: 120, height: 80,
                color: '#f39c12', nombre: 'Edificio A3 - Aulas 3',
                pisos: 3, entrada: { x: 260, y: 330 }
            },
            'A4': {
                x: 350, y: 200, width: 120, height: 80,
                color: '#2ecc71', nombre: 'Edificio A4 - Aulas 4',
                pisos: 3, entrada: { x: 410, y: 280 }
            },
            'LC': {
                x: 500, y: 500, width: 180, height: 100,
                color: '#34495e', nombre: 'LC - Laboratorio Central',
                pisos: 3, entrada: { x: 590, y: 600 }
            },
            'EG': {
                x: 700, y: 300, width: 150, height: 120,
                color: '#f1c40f', nombre: 'EG - Edificio de Gobierno',
                pisos: 2, entrada: { x: 775, y: 420 }
            },
            'EP': {
                x: 150, y: 500, width: 140, height: 100,
                color: '#e74c3c', nombre: 'EP - Laboratorios Pesados',
                pisos: 2, entrada: { x: 220, y: 600 }
            }
        };
    }

    configurarEventos() {
        // Eventos del mouse
        this.canvas.addEventListener('mousedown', (e) => this.iniciarArrastre(e));
        this.canvas.addEventListener('mousemove', (e) => this.manejarMovimiento(e));
        this.canvas.addEventListener('mouseup', () => this.terminarArrastre());
        this.canvas.addEventListener('mouseleave', () => this.terminarArrastre());
        this.canvas.addEventListener('wheel', (e) => this.manejarZoom(e));
        this.canvas.addEventListener('click', (e) => this.manejarClick(e));

        // Eventos de redimensión
        window.addEventListener('resize', () => this.ajustarCanvas());

        // Eventos de teclado
        document.addEventListener('keydown', (e) => this.manejarTeclado(e));
    }

    ajustarCanvas() {
        const container = this.canvas.parentElement;
        const rect = container.getBoundingClientRect();

        this.canvas.width = rect.width;
        this.canvas.height = Math.max(600, rect.height);

        this.dibujarMapa();
    }

    dibujarMapa() {
        // Limpiar canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Aplicar transformaciones
        this.ctx.save();
        this.ctx.scale(this.zoom, this.zoom);
        this.ctx.translate(this.offsetX, this.offsetY);

        // Dibujar fondo
        this.dibujarFondo();

        // Dibujar caminos
        this.dibujarCaminos();

        if (this.pisoActual === 0) {
            // Vista general del campus
            this.dibujarVistaGeneral();
        } else {
            // Vista específica de un edificio
            this.dibujarVistaEdificio();
        }

        // Dibujar ruta actual
        if (this.rutaActual) {
            this.dibujarRuta(this.rutaActual);
        }

        // Dibujar información adicional
        this.dibujarInfoAdicional();

        this.ctx.restore();
    }

    dibujarFondo() {
        // Fondo de pasto
        this.ctx.fillStyle = this.config.colores.pasto;
        this.ctx.fillRect(-100, -100, 1200, 1000);

        // Áreas pavimentadas
        this.ctx.fillStyle = this.config.colores.fondo;
        this.ctx.fillRect(100, 150, 800, 500);
    }

    dibujarCaminos() {
        this.ctx.strokeStyle = this.config.colores.caminos;
        this.ctx.lineWidth = 8;
        this.ctx.lineCap = 'round';

        // Camino principal horizontal
        this.ctx.beginPath();
        this.ctx.moveTo(100, 450);
        this.ctx.lineTo(900, 450);
        this.ctx.stroke();

        // Camino vertical central
        this.ctx.beginPath();
        this.ctx.moveTo(500, 150);
        this.ctx.lineTo(500, 650);
        this.ctx.stroke();

        // Caminos secundarios
        this.ctx.lineWidth = 6;

        // Conexión a A1-A2
        this.ctx.beginPath();
        this.ctx.moveTo(360, 400);
        this.ctx.lineTo(510, 350);
        this.ctx.stroke();

        // Conexión a A3-A4
        this.ctx.beginPath();
        this.ctx.moveTo(260, 250);
        this.ctx.lineTo(410, 200);
        this.ctx.stroke();

        // Camino a EP
        this.ctx.beginPath();
        this.ctx.moveTo(220, 500);
        this.ctx.lineTo(100, 450);
        this.ctx.stroke();

        // Camino a EG
        this.ctx.beginPath();
        this.ctx.moveTo(700, 350);
        this.ctx.lineTo(500, 400);
        this.ctx.stroke();
    }

    dibujarVistaGeneral() {
        Object.entries(this.edificios).forEach(([codigo, edificio]) => {
            this.dibujarEdificio(codigo, edificio);
        });

        // Dibujar leyenda
        this.dibujarLeyenda();
    }

    dibujarEdificio(codigo, edificio) {
        const { x, y, width, height, color, nombre } = edificio;

        // Sombra del edificio
        this.ctx.fillStyle = this.config.colores.sombras;
        this.ctx.fillRect(
            x + this.config.efectos.sombraEdificio,
            y + this.config.efectos.sombraEdificio,
            width,
            height
        );

        // Edificio principal
        this.ctx.fillStyle = color;
        this.ctx.fillRect(x, y, width, height);

        // Borde del edificio
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(x, y, width, height);

        // Efecto 3D simple
        this.dibujarEfecto3D(x, y, width, height, color);

        // Etiqueta del edificio
        this.ctx.fillStyle = 'white';
        this.ctx.font = this.config.fuentes.edificio;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        // Sombra del texto
        this.ctx.fillStyle = 'rgba(0,0,0,0.5)';
        this.ctx.fillText(codigo, x + width/2 + 1, y + height/2 + 1);

        // Texto principal
        this.ctx.fillStyle = 'white';
        this.ctx.fillText(codigo, x + width/2, y + height/2);

        // Indicador de pisos
        this.dibujarIndicadorPisos(x, y, width, height, edificio.pisos);

        // Entrada del edificio
        this.dibujarEntrada(edificio.entrada);
    }

    dibujarEfecto3D(x, y, width, height, color) {
        // Cara superior
        this.ctx.fillStyle = this.iluminarColor(color, 0.3);
        this.ctx.beginPath();
        this.ctx.moveTo(x, y);
        this.ctx.lineTo(x + 8, y - 8);
        this.ctx.lineTo(x + width + 8, y - 8);
        this.ctx.lineTo(x + width, y);
        this.ctx.closePath();
        this.ctx.fill();

        // Cara lateral derecha
        this.ctx.fillStyle = this.oscurecerColor(color, 0.2);
        this.ctx.beginPath();
        this.ctx.moveTo(x + width, y);
        this.ctx.lineTo(x + width + 8, y - 8);
        this.ctx.lineTo(x + width + 8, y + height - 8);
        this.ctx.lineTo(x + width, y + height);
        this.ctx.closePath();
        this.ctx.fill();
    }

    dibujarIndicadorPisos(x, y, width, height, pisos) {
        const tamañoPunto = 3;
        const espaciado = 8;
        const inicioX = x + width - (pisos * espaciado) - 5;
        const inicioY = y + 5;

        for (let i = 0; i < pisos; i++) {
            this.ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            this.ctx.beginPath();
            this.ctx.arc(inicioX + (i * espaciado), inicioY, tamañoPunto, 0, 2 * Math.PI);
            this.ctx.fill();

            this.ctx.strokeStyle = 'rgba(0, 0, 0, 0.5)';
            this.ctx.lineWidth = 1;
            this.ctx.stroke();
        }
    }

    dibujarEntrada(entrada) {
        if (!entrada) return;

        // Marcador de entrada
        this.ctx.fillStyle = '#1abc9c';
        this.ctx.beginPath();
        this.ctx.arc(entrada.x, entrada.y, 6, 0, 2 * Math.PI);
        this.ctx.fill();

        this.ctx.strokeStyle = 'white';
        this.ctx.lineWidth = 2;
        this.ctx.stroke();

        // Icono de puerta
        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 8px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('E', entrada.x, entrada.y + 2);
    }

    dibujarVistaEdificio() {
        if (!this.edificioSeleccionado) return;

        const edificio = this.edificios[this.edificioSeleccionado];
        if (!edificio) return;

        // Dibujar plano del edificio específico
        this.dibujarPlanoEdificio(this.edificioSeleccionado, this.pisoActual);
    }

    dibujarPlanoEdificio(codigoEdificio, piso) {
        // Centrar la vista en el edificio
        const edificio = this.edificios[codigoEdificio];
        const centroX = this.canvas.width / 2 / this.zoom;
        const centroY = this.canvas.height / 2 / this.zoom;

        // Dibujar fondo del plano
        this.ctx.fillStyle = '#f8f9fa';
        this.ctx.fillRect(centroX - 300, centroY - 200, 600, 400);

        // Dibujar estructura del edificio
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 3;
        this.ctx.strokeRect(centroX - 280, centroY - 180, 560, 360);

        // Título del plano
        this.ctx.fillStyle = '#333';
        this.ctx.font = 'bold 20px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(
            `${edificio.nombre} - Piso ${piso}`,
            centroX,
            centroY - 220
        );

        // Dibujar aulas del piso
        this.dibujarAulasPiso(codigoEdificio, piso, centroX, centroY);

        // Dibujar escaleras y salidas
        this.dibujarEscalerasYSalidas(centroX, centroY);
    }

    dibujarAulasPiso(codigoEdificio, piso, centroX, centroY) {
        // Simulación de aulas en cuadrícula
        const aulasData = this.obtenerAulasPiso(codigoEdificio, piso);

        aulasData.forEach((aula, index) => {
            const col = index % 6;
            const row = Math.floor(index / 6);

            const aulaX = centroX - 240 + (col * 80);
            const aulaY = centroY - 140 + (row * 60);

            // Aula
            this.ctx.fillStyle = aula.tipo === 'laboratorio' ? '#3498db' : '#ecf0f1';
            this.ctx.fillRect(aulaX, aulaY, 70, 50);

            this.ctx.strokeStyle = '#333';
            this.ctx.lineWidth = 1;
            this.ctx.strokeRect(aulaX, aulaY, 70, 50);

            // Etiqueta del aula
            this.ctx.fillStyle = '#333';
            this.ctx.font = '10px Arial';
            this.ctx.textAlign = 'center';
            this.ctx.fillText(aula.codigo, aulaX + 35, aulaY + 20);
            this.ctx.fillText(aula.nombre.substring(0, 12), aulaX + 35, aulaY + 35);
        });
    }

    dibujarEscalerasYSalidas(centroX, centroY) {
        // Escaleras
        this.ctx.fillStyle = '#95a5a6';
        this.ctx.fillRect(centroX + 200, centroY - 50, 40, 40);

        this.ctx.fillStyle = '#333';
        this.ctx.font = '12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('ESC', centroX + 220, centroY - 25);

        // Salidas de emergencia
        this.ctx.fillStyle = '#e74c3c';
        this.ctx.fillRect(centroX - 270, centroY + 100, 30, 20);
        this.ctx.fillRect(centroX + 240, centroY + 100, 30, 20);

        this.ctx.fillStyle = 'white';
        this.ctx.font = '10px Arial';
        this.ctx.fillText('SALIDA', centroX - 255, centroY + 112);
        this.ctx.fillText('SALIDA', centroX + 255, centroY + 112);
    }

    obtenerAulasPiso(codigoEdificio, piso) {
        // Simulación de datos de aulas por edificio y piso
        const aulasPorEdificio = {
            'A1': [
                { codigo: 'A-100', nombre: 'Aula', tipo: 'aula' },
                { codigo: 'A-101', nombre: 'Sala Profesores', tipo: 'oficina' },
                { codigo: 'A-102', nombre: 'Aula', tipo: 'aula' },
                { codigo: 'A-103', nombre: 'Aula', tipo: 'aula' },
                { codigo: 'A-104', nombre: 'Aula', tipo: 'aula' },
                { codigo: 'A-105', nombre: 'Aula', tipo: 'aula' }
            ],
            'A2': [
                { codigo: 'A-200', nombre: 'Lab Desarrollo', tipo: 'laboratorio' },
                { codigo: 'A-203', nombre: 'Cómputo 4', tipo: 'laboratorio' },
                { codigo: 'A-204', nombre: 'Realidad Ext', tipo: 'laboratorio' },
                { codigo: 'A-205', nombre: 'Lab CIM', tipo: 'laboratorio' }
            ],
            'LC': [
                { codigo: 'LC-100', nombre: 'Lab Química', tipo: 'laboratorio' },
                { codigo: 'LC-102', nombre: 'Lab Física 1', tipo: 'laboratorio' },
                { codigo: 'LC-104', nombre: 'Biblioteca', tipo: 'biblioteca' },
                { codigo: 'LC-105', nombre: 'Red Género', tipo: 'oficina' }
            ],
            'EG': [
                { codigo: 'EG-001', nombre: 'Serv Médico', tipo: 'servicio' },
                { codigo: 'EG-007', nombre: 'Gestión Escolar', tipo: 'oficina' },
                { codigo: 'EG-015', nombre: 'Auditorio', tipo: 'auditorio' }
            ]
        };

        return aulasPorEdificio[codigoEdificio] || [];
    }

    dibujarRuta(rutaDetallada) {
        if (!rutaDetallada || rutaDetallada.length < 2) return;

        // Línea principal de la ruta
        this.ctx.strokeStyle = this.config.colores.ruta;
        this.ctx.lineWidth = this.config.efectos.anchoRuta;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        // Efecto de animación de la ruta
        this.ctx.setLineDash([10, 5]);
        this.ctx.lineDashOffset = Date.now() / 100;

        this.ctx.beginPath();
        this.ctx.moveTo(rutaDetallada[0].coordenada_x, rutaDetallada[0].coordenada_y);

        for (let i = 1; i < rutaDetallada.length; i++) {
            this.ctx.lineTo(rutaDetallada[i].coordenada_x, rutaDetallada[i].coordenada_y);
        }
        this.ctx.stroke();

        // Restaurar línea sólida
        this.ctx.setLineDash([]);

        // Marcadores de la ruta
        rutaDetallada.forEach((punto, index) => {
            if (index === 0) {
                this.dibujarMarcadorOrigen(punto.coordenada_x, punto.coordenada_y);
            } else if (index === rutaDetallada.length - 1) {
                this.dibujarMarcadorDestino(punto.coordenada_x, punto.coordenada_y);
            } else {
                this.dibujarMarcadorIntermedio(punto.coordenada_x, punto.coordenada_y, index);
            }
        });

        // Información de distancia
        this.dibujarInfoRuta(rutaDetallada);
    }

    dibujarMarcadorOrigen(x, y) {
        // Marcador verde para el origen
        this.ctx.fillStyle = this.config.colores.origen;
        this.ctx.beginPath();
        this.ctx.arc(x, y, 12, 0, 2 * Math.PI);
        this.ctx.fill();

        this.ctx.strokeStyle = 'white';
        this.ctx.lineWidth = 3;
        this.ctx.stroke();

        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText('O', x, y);
    }

    dibujarMarcadorDestino(x, y) {
        // Marcador rojo para el destino
        this.ctx.fillStyle = this.config.colores.destino;
        this.ctx.beginPath();
        this.ctx.arc(x, y, 12, 0, 2 * Math.PI);
        this.ctx.fill();

        this.ctx.strokeStyle = 'white';
        this.ctx.lineWidth = 3;
        this.ctx.stroke();

        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText('D', x, y);
    }

    dibujarMarcadorIntermedio(x, y, numero) {
        // Marcador azul para puntos intermedios
        this.ctx.fillStyle = '#17a2b8';
        this.ctx.beginPath();
        this.ctx.arc(x, y, 8, 0, 2 * Math.PI);
        this.ctx.fill();

        this.ctx.strokeStyle = 'white';
        this.ctx.lineWidth = 2;
        this.ctx.stroke();

        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 10px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText(numero, x, y);
    }

    dibujarInfoRuta(rutaDetallada) {
        if (!rutaDetallada.length) return;

        // Calcular distancia total
        let distanciaTotal = 0;
        for (let i = 1; i < rutaDetallada.length; i++) {
            const punto1 = rutaDetallada[i - 1];
            const punto2 = rutaDetallada[i];
            distanciaTotal += Math.sqrt(
                Math.pow(punto2.coordenada_x - punto1.coordenada_x, 2) +
                Math.pow(punto2.coordenada_y - punto1.coordenada_y, 2)
            );
        }

        // Convertir a metros (factor de escala)
        distanciaTotal = Math.round(distanciaTotal * 0.5);

        // Mostrar información en la esquina superior izquierda
        const infoX = 20 / this.zoom - this.offsetX;
        const infoY = 30 / this.zoom - this.offsetY;

        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
        this.ctx.fillRect(infoX - 10, infoY - 20, 200, 60);

        this.ctx.fillStyle = 'white';
        this.ctx.font = '12px Arial';
        this.ctx.textAlign = 'left';
        this.ctx.fillText(`Distancia: ${distanciaTotal}m`, infoX, infoY);
        this.ctx.fillText(`Pasos: ${rutaDetallada.length - 1}`, infoX, infoY + 15);
        this.ctx.fillText(`Tiempo est: ${Math.ceil(distanciaTotal / 80)}min`, infoX, infoY + 30);
    }

    dibujarLeyenda() {
        const legendaX = this.canvas.width / this.zoom - 220 - this.offsetX;
        const legendaY = 20 / this.zoom - this.offsetY;

        // Fondo de la leyenda
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
        this.ctx.fillRect(legendaX, legendaY, 200, 180);

        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 1;
        this.ctx.strokeRect(legendaX, legendaY, 200, 180);

        // Título de la leyenda
        this.ctx.fillStyle = '#333';
        this.ctx.font = 'bold 14px Arial';
        this.ctx.textAlign = 'left';
        this.ctx.fillText('Leyenda', legendaX + 10, legendaY + 20);

        // Elementos de la leyenda
        const elementos = [
            { color: '#3498db', texto: 'Aulas' },
            { color: '#e91e63', texto: 'Laboratorios' },
            { color: '#f1c40f', texto: 'Administración' },
            { color: '#e74c3c', texto: 'Lab. Pesados' },
            { color: '#34495e', texto: 'Lab. Central' },
            { color: '#1abc9c', texto: 'Entrada' },
            { color: '#007bff', texto: 'Ruta' }
        ];

        elementos.forEach((elemento, index) => {
            const y = legendaY + 40 + (index * 20);

            // Color del elemento
            this.ctx.fillStyle = elemento.color;
            this.ctx.fillRect(legendaX + 10, y - 8, 15, 12);

            this.ctx.strokeStyle = '#333';
            this.ctx.lineWidth = 1;
            this.ctx.strokeRect(legendaX + 10, y - 8, 15, 12);

            // Texto del elemento
            this.ctx.fillStyle = '#333';
            this.ctx.font = '11px Arial';
            this.ctx.fillText(elemento.texto, legendaX + 35, y);
        });
    }

    dibujarInfoAdicional() {
        // Información del zoom en la esquina inferior derecha
        const infoX = this.canvas.width / this.zoom - 100 - this.offsetX;
        const infoY = this.canvas.height / this.zoom - 30 - this.offsetY;

        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        this.ctx.fillRect(infoX, infoY, 90, 25);

        this.ctx.fillStyle = 'white';
        this.ctx.font = '11px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(`Zoom: ${Math.round(this.zoom * 100)}%`, infoX + 45, infoY + 15);

        // Brújula
        this.dibujarBrujula();
    }

    dibujarBrujula() {
        const brujulaX = this.canvas.width / this.zoom - 50 - this.offsetX;
        const brujulaY = 80 / this.zoom - this.offsetY;

        // Círculo de la brújula
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.arc(brujulaX, brujulaY, 20, 0, 2 * Math.PI);
        this.ctx.stroke();

        // Flecha norte
        this.ctx.fillStyle = '#e74c3c';
        this.ctx.beginPath();
        this.ctx.moveTo(brujulaX, brujulaY - 15);
        this.ctx.lineTo(brujulaX - 5, brujulaY - 5);
        this.ctx.lineTo(brujulaX + 5, brujulaY - 5);
        this.ctx.closePath();
        this.ctx.fill();

        this.ctx.fillStyle = '#333';
        this.ctx.font = 'bold 10px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('N', brujulaX, brujulaY - 25);
    }

    // Métodos de interacción
    iniciarArrastre(e) {
        this.isDragging = true;
        const rect = this.canvas.getBoundingClientRect();
        this.lastMouseX = e.clientX - rect.left;
        this.lastMouseY = e.clientY - rect.top;
        this.canvas.style.cursor = 'grabbing';
    }

    manejarMovimiento(e) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        if (this.isDragging) {
            const deltaX = (mouseX - this.lastMouseX) / this.zoom;
            const deltaY = (mouseY - this.lastMouseY) / this.zoom;

            this.offsetX += deltaX;
            this.offsetY += deltaY;

            this.lastMouseX = mouseX;
            this.lastMouseY = mouseY;

            this.dibujarMapa();
        } else {
            // Detectar hover sobre edificios
            this.manejarHover(mouseX, mouseY);
        }
    }

    terminarArrastre() {
        this.isDragging = false;
        this.canvas.style.cursor = 'grab';
    }

    manejarZoom(e) {
        e.preventDefault();

        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const factor = e.deltaY > 0 ? 0.9 : 1.1;
        const newZoom = Math.max(0.3, Math.min(3, this.zoom * factor));

        // Ajustar offset para hacer zoom hacia el cursor
        const zoomFactor = newZoom / this.zoom;
        const worldX = (mouseX / this.zoom) - this.offsetX;
        const worldY = (mouseY / this.zoom) - this.offsetY;

        this.zoom = newZoom;

        this.offsetX = (mouseX / this.zoom) - worldX;
        this.offsetY = (mouseY / this.zoom) - worldY;

        this.dibujarMapa();
    }

    manejarClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        // Convertir coordenadas del canvas a coordenadas del mundo
        const worldX = (mouseX / this.zoom) - this.offsetX;
        const worldY = (mouseY / this.zoom) - this.offsetY;

        // Verificar si se hizo clic en un edificio
        const edificioClicado = this.detectarEdificioEnPunto(worldX, worldY);

        if (edificioClicado) {
            this.seleccionarEdificio(edificioClicado);
        }
    }

    manejarHover(mouseX, mouseY) {
        const worldX = (mouseX / this.zoom) - this.offsetX;
        const worldY = (mouseY / this.zoom) - this.offsetY;

        const edificioHover = this.detectarEdificioEnPunto(worldX, worldY);

        if (edificioHover) {
            this.canvas.style.cursor = 'pointer';
            this.mostrarTooltip(edificioHover, mouseX, mouseY);
        } else {
            this.canvas.style.cursor = this.isDragging ? 'grabbing' : 'grab';
            this.ocultarTooltip();
        }
    }

    manejarTeclado(e) {
        switch (e.key) {
            case 'Escape':
                this.volverVistaGeneral();
                break;
            case '+':
            case '=':
                this.cambiarZoom(1.2);
                break;
            case '-':
                this.cambiarZoom(0.8);
                break;
            case 'r':
            case 'R':
                this.resetearVista();
                break;
        }
    }

    // Métodos de utilidad
    detectarEdificioEnPunto(x, y) {
        for (const [codigo, edificio] of Object.entries(this.edificios)) {
            if (x >= edificio.x && x <= edificio.x + edificio.width &&
                y >= edificio.y && y <= edificio.y + edificio.height) {
                return codigo;
            }
        }
        return null;
    }

    seleccionarEdificio(codigo) {
        this.edificioSeleccionado = codigo;
        this.pisoActual = 1; // Mostrar primer piso por defecto

        // Centrar vista en el edificio
        const edificio = this.edificios[codigo];
        const centroX = edificio.x + edificio.width / 2;
        const centroY = edificio.y + edificio.height / 2;

        this.offsetX = (this.canvas.width / 2 / this.zoom) - centroX;
        this.offsetY = (this.canvas.height / 2 / this.zoom) - centroY;
        this.zoom = 1.5;

        this.dibujarMapa();

        // Disparar evento personalizado
        this.canvas.dispatchEvent(new CustomEvent('edificioSeleccionado', {
            detail: { codigo, edificio }
        }));
    }

    volverVistaGeneral() {
        this.edificioSeleccionado = null;
        this.pisoActual = 0;
        this.resetearVista();
    }

    resetearVista() {
        this.zoom = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.dibujarMapa();
    }

    cambiarZoom(factor) {
        this.zoom = Math.max(0.3, Math.min(3, this.zoom * factor));
        this.dibujarMapa();
    }

    cambiarPiso(piso) {
        if (this.edificioSeleccionado && piso > 0) {
            const edificio = this.edificios[this.edificioSeleccionado];
            if (piso <= edificio.pisos) {
                this.pisoActual = piso;
                this.dibujarMapa();
            }
        }
    }

    mostrarTooltip(codigoEdificio, x, y) {
        const edificio = this.edificios[codigoEdificio];

        // Remover tooltip existente
        this.ocultarTooltip();

        // Crear nuevo tooltip
        const tooltip = document.createElement('div');
        tooltip.id = 'mapa-tooltip';
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-family: Arial, sans-serif;
            pointer-events: none;
            z-index: 1000;
            max-width: 200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        `;

        tooltip.innerHTML = `
            <strong>${edificio.nombre}</strong><br>
            Pisos: ${edificio.pisos}<br>
            <small>Clic para ver detalles</small>
        `;

        document.body.appendChild(tooltip);

        // Posicionar tooltip
        const rect = this.canvas.getBoundingClientRect();
        tooltip.style.left = (rect.left + x + 10) + 'px';
        tooltip.style.top = (rect.top + y - 10) + 'px';
    }

    ocultarTooltip() {
        const tooltip = document.getElementById('mapa-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Métodos para rutas
    establecerRuta(rutaDetallada) {
        this.rutaActual = rutaDetallada;
        this.dibujarMapa();

        // Animar la ruta
        this.animarRuta();
    }

    limpiarRuta() {
        this.rutaActual = null;
        this.dibujarMapa();
    }

    animarRuta() {
        if (!this.rutaActual) return;

        let frame = 0;
        const animate = () => {
            frame++;
            this.dibujarMapa();

            if (frame < 100) { // Animar por 100 frames
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    // Métodos para colores
    iluminarColor(color, factor) {
        const rgb = this.hexToRgb(color);
        if (!rgb) return color;

        return `rgb(${Math.min(255, Math.round(rgb.r + (255 - rgb.r) * factor))}, 
                    ${Math.min(255, Math.round(rgb.g + (255 - rgb.g) * factor))}, 
                    ${Math.min(255, Math.round(rgb.b + (255 - rgb.b) * factor))})`;
    }

    oscurecerColor(color, factor) {
        const rgb = this.hexToRgb(color);
        if (!rgb) return color;

        return `rgb(${Math.round(rgb.r * (1 - factor))}, 
                    ${Math.round(rgb.g * (1 - factor))}, 
                    ${Math.round(rgb.b * (1 - factor))})`;
    }

    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    // Métodos públicos para integración
    centrarEnAula(codigoAula) {
        // Buscar aula en los datos
        for (const [codigoEdificio, aulasEdificio] of Object.entries(this.aulas)) {
            for (const aula of aulasEdificio) {
                if (aula.codigo === codigoAula) {
                    this.offsetX = (this.canvas.width / 2 / this.zoom) - aula.x;
                    this.offsetY = (this.canvas.height / 2 / this.zoom) - aula.y;
                    this.zoom = 2;
                    this.dibujarMapa();
                    return true;
                }
            }
        }
        return false;
    }

    resaltarElemento(tipo, codigo) {
        // Resaltar un edificio o aula específica
        this.elementoResaltado = { tipo, codigo };
        this.dibujarMapa();
    }

    quitarResaltado() {
        this.elementoResaltado = null;
        this.dibujarMapa();
    }

    obtenerInformacionPunto(x, y) {
        // Obtener información de un punto específico en el mapa
        const worldX = (x / this.zoom) - this.offsetX;
        const worldY = (y / this.zoom) - this.offsetY;

        const edificio = this.detectarEdificioEnPunto(worldX, worldY);

        if (edificio) {
            return {
                tipo: 'edificio',
                codigo: edificio,
                datos: this.edificios[edificio]
            };
        }

        return null;
    }

    exportarImagen() {
        // Exportar el mapa actual como imagen
        const link = document.createElement('a');
        link.download = 'mapa_upiita.png';
        link.href = this.canvas.toDataURL();
        link.click();
    }

    // Método para redibujado automático
    iniciarRedibujadoAutomatico() {
        if (this.intervalRedibujado) {
            clearInterval(this.intervalRedibujado);
        }

        // Redibujado cada 100ms para animaciones suaves
        this.intervalRedibujado = setInterval(() => {
            if (this.rutaActual) {
                this.dibujarMapa();
            }
        }, 100);
    }

    detenerRedibujadoAutomatico() {
        if (this.intervalRedibujado) {
            clearInterval(this.intervalRedibujado);
            this.intervalRedibujado = null;
        }
    }

    // Cleanup
    destruir() {
        this.detenerRedibujadoAutomatico();
        this.ocultarTooltip();

        // Remover event listeners
        this.canvas.removeEventListener('mousedown', this.iniciarArrastre);
        this.canvas.removeEventListener('mousemove', this.manejarMovimiento);
        this.canvas.removeEventListener('mouseup', this.terminarArrastre);
        this.canvas.removeEventListener('mouseleave', this.terminarArrastre);
        this.canvas.removeEventListener('wheel', this.manejarZoom);
        this.canvas.removeEventListener('click', this.manejarClick);

        window.removeEventListener('resize', this.ajustarCanvas);
        document.removeEventListener('keydown', this.manejarTeclado);
    }
}

// Utilidades adicionales para el mapa
class MapaUtils {
    static calcularDistanciaEntrePuntos(x1, y1, x2, y2) {
        return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
    }

    static convertirCoordenadasAMetros(distanciaPixeles) {
        // Factor de conversión aproximado: 1 pixel = 0.5 metros
        return Math.round(distanciaPixeles * 0.5);
    }

    static formatearTiempo(minutos) {
        if (minutos < 60) {
            return `${minutos} min`;
        }
        const horas = Math.floor(minutos / 60);
        const mins = minutos % 60;
        return `${horas}h ${mins}min`;
    }

    static generarColorAleatorio() {
        const colores = [
            '#3498db', '#e91e63', '#f39c12', '#2ecc71',
            '#9b59b6', '#1abc9c', '#e74c3c', '#34495e'
        ];
        return colores[Math.floor(Math.random() * colores.length)];
    }
}

// Exportar para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MapaRealista, MapaUtils };
} else {
    window.MapaRealista = MapaRealista;
    window.MapaUtils = MapaUtils;
}