<?php
// Ruta: WEBupita/includes/MapaReal.php
// Sistema de coordenadas reales basado en las imágenes de UPIITA

class MapaReal {
    private $pdo;

    // Coordenadas reales basadas en la disposición física de UPIITA
    private $edificios_coordenadas = [
        'A1' => [
            'x' => 300, 'y' => 400, 'width' => 120, 'height' => 80,
            'color' => '#3498db', 'nombre' => 'Edificio A1 - Aulas 1',
            'pisos' => 3, 'entrada' => ['x' => 360, 'y' => 480]
        ],
        'A2' => [
            'x' => 450, 'y' => 350, 'width' => 120, 'height' => 80,
            'color' => '#e91e63', 'nombre' => 'Edificio A2 - Aulas 2',
            'pisos' => 3, 'entrada' => ['x' => 510, 'y' => 430]
        ],
        'A3' => [
            'x' => 200, 'y' => 250, 'width' => 120, 'height' => 80,
            'color' => '#f39c12', 'nombre' => 'Edificio A3 - Aulas 3',
            'pisos' => 3, 'entrada' => ['x' => 260, 'y' => 330]
        ],
        'A4' => [
            'x' => 350, 'y' => 200, 'width' => 120, 'height' => 80,
            'color' => '#2ecc71', 'nombre' => 'Edificio A4 - Aulas 4',
            'pisos' => 3, 'entrada' => ['x' => 410, 'y' => 280]
        ],
        'LC' => [
            'x' => 500, 'y' => 500, 'width' => 180, 'height' => 100,
            'color' => '#34495e', 'nombre' => 'LC - Laboratorio Central',
            'pisos' => 3, 'entrada' => ['x' => 590, 'y' => 600]
        ],
        'EG' => [
            'x' => 700, 'y' => 300, 'width' => 150, 'height' => 120,
            'color' => '#f1c40f', 'nombre' => 'EG - Edificio de Gobierno',
            'pisos' => 2, 'entrada' => ['x' => 775, 'y' => 420]
        ],
        'EP' => [
            'x' => 150, 'y' => 500, 'width' => 140, 'height' => 100,
            'color' => '#e74c3c', 'nombre' => 'EP - Laboratorios Pesados',
            'pisos' => 2, 'entrada' => ['x' => 220, 'y' => 600]
        ]
    ];

    // Definición de aulas por edificio basada en el PDF
    private $aulas_por_edificio = [
        'A1' => [
            0 => [ // Planta Baja
                'A-100' => ['x' => 320, 'y' => 420, 'nombre' => 'Aula'],
                'A-101' => ['x' => 340, 'y' => 420, 'nombre' => 'Sala de profesores'],
                'A-102' => ['x' => 360, 'y' => 420, 'nombre' => 'Aula'],
                'A-103' => ['x' => 380, 'y' => 420, 'nombre' => 'Aula'],
                'A-104' => ['x' => 400, 'y' => 420, 'nombre' => 'Aula'],
                'A-105' => ['x' => 320, 'y' => 440, 'nombre' => 'Aula'],
                'A-106' => ['x' => 340, 'y' => 440, 'nombre' => 'Aula']
            ],
            1 => [ // Primer Piso
                'A-110' => ['x' => 320, 'y' => 420, 'nombre' => 'Aula Magna Posgrado'],
                'A-111' => ['x' => 340, 'y' => 420, 'nombre' => 'Sala de profesores'],
                'A-112' => ['x' => 360, 'y' => 420, 'nombre' => 'Sala de profesores'],
                'A-113' => ['x' => 380, 'y' => 420, 'nombre' => 'Sala de profesores'],
                'A-114' => ['x' => 400, 'y' => 420, 'nombre' => 'UTE y CV'],
                'A-115' => ['x' => 320, 'y' => 440, 'nombre' => 'Sala de profesores'],
                'A-116' => ['x' => 340, 'y' => 440, 'nombre' => 'Sala de profesores']
            ],
            2 => [ // Segundo Piso
                'A-120' => ['x' => 320, 'y' => 420, 'nombre' => 'Aula Posgrado'],
                'A-121' => ['x' => 340, 'y' => 420, 'nombre' => 'Aula'],
                'A-122' => ['x' => 360, 'y' => 420, 'nombre' => 'Aula'],
                'A-123' => ['x' => 380, 'y' => 420, 'nombre' => 'Aula'],
                'A-124' => ['x' => 400, 'y' => 420, 'nombre' => 'Aula'],
                'A-125' => ['x' => 320, 'y' => 440, 'nombre' => 'Aula'],
                'A-126' => ['x' => 340, 'y' => 440, 'nombre' => 'Aula']
            ]
        ],
        'A2' => [
            0 => [ // Planta Baja
                'A-200' => ['x' => 470, 'y' => 370, 'nombre' => 'Lab. de Desarrollo de Proyectos'],
                'A-201' => ['x' => 490, 'y' => 370, 'nombre' => 'Aula'],
                'A-202' => ['x' => 510, 'y' => 370, 'nombre' => 'Aula'],
                'A-203' => ['x' => 530, 'y' => 370, 'nombre' => 'Sala de Cómputo 4'],
                'A-204' => ['x' => 550, 'y' => 370, 'nombre' => 'Lab. de Realidad Extendida'],
                'A-205' => ['x' => 470, 'y' => 390, 'nombre' => 'Lab. CIM'],
                'A-206' => ['x' => 490, 'y' => 390, 'nombre' => 'Lab. CIM']
            ],
            1 => [ // Primer Piso
                'A-210' => ['x' => 470, 'y' => 370, 'nombre' => 'Sala de préstamo'],
                'A-211' => ['x' => 490, 'y' => 370, 'nombre' => 'Aula'],
                'A-212' => ['x' => 510, 'y' => 370, 'nombre' => 'Sala de Cómputo 1'],
                'A-213' => ['x' => 530, 'y' => 370, 'nombre' => 'Sala de Cómputo 2'],
                'A-214' => ['x' => 550, 'y' => 370, 'nombre' => 'Sala multimedia'],
                'A-215' => ['x' => 470, 'y' => 390, 'nombre' => 'Sala de Cómputo 3']
            ],
            2 => [ // Segundo Piso
                'A-220' => ['x' => 470, 'y' => 370, 'nombre' => 'Aula'],
                'A-221' => ['x' => 490, 'y' => 370, 'nombre' => 'Aula'],
                'A-222' => ['x' => 510, 'y' => 370, 'nombre' => 'Aula'],
                'A-223' => ['x' => 530, 'y' => 370, 'nombre' => 'Aula'],
                'A-224' => ['x' => 550, 'y' => 370, 'nombre' => 'Aula'],
                'A-225' => ['x' => 470, 'y' => 390, 'nombre' => 'Aula'],
                'A-226' => ['x' => 490, 'y' => 390, 'nombre' => 'Aula']
            ]
        ],
        'A3' => [
            0 => [ // Planta Baja
                'A-300' => ['x' => 220, 'y' => 270, 'nombre' => 'Laboratorio de electrónica 3'],
                'A-303' => ['x' => 240, 'y' => 270, 'nombre' => 'Lab. Robótica Avanzada y TV Interactiva'],
                'A-304' => ['x' => 260, 'y' => 270, 'nombre' => 'Red de Expertos Posgrado'],
                'A-305' => ['x' => 280, 'y' => 270, 'nombre' => 'Red de Expertos Posgrado'],
                'A-306' => ['x' => 300, 'y' => 270, 'nombre' => 'Lab. Síntesis Química Posgrado']
            ],
            1 => [ // Primer Piso
                'A-310' => ['x' => 220, 'y' => 270, 'nombre' => 'Sala de préstamo'],
                'A-311' => ['x' => 240, 'y' => 270, 'nombre' => 'Sala de cómputo 5'],
                'A-312' => ['x' => 260, 'y' => 270, 'nombre' => 'Sala de cómputo 6'],
                'A-313' => ['x' => 280, 'y' => 270, 'nombre' => 'Sala de cómputo 7'],
                'A-314' => ['x' => 300, 'y' => 270, 'nombre' => 'Sala de cómputo 9'],
                'A-315' => ['x' => 220, 'y' => 290, 'nombre' => 'Aula'],
                'A-316' => ['x' => 240, 'y' => 290, 'nombre' => 'Sala de cómputo 8']
            ],
            2 => [ // Segundo Piso
                'A-320' => ['x' => 220, 'y' => 270, 'nombre' => 'Sala de profesores'],
                'A-321' => ['x' => 240, 'y' => 270, 'nombre' => 'Sala de profesores'],
                'A-322' => ['x' => 260, 'y' => 270, 'nombre' => 'Aula'],
                'A-323' => ['x' => 280, 'y' => 270, 'nombre' => 'Aula'],
                'A-324' => ['x' => 300, 'y' => 270, 'nombre' => 'Aula'],
                'A-325' => ['x' => 220, 'y' => 290, 'nombre' => 'Aula'],
                'A-326' => ['x' => 240, 'y' => 290, 'nombre' => 'Aula']
            ]
        ],
        'A4' => [
            0 => [ // Planta Baja
                'A-400' => ['x' => 370, 'y' => 220, 'nombre' => 'Lab. de Imagen y Procesamiento de Señales (Posgrado)'],
                'A-401' => ['x' => 390, 'y' => 220, 'nombre' => 'Lab. de Fenómenos Cuánticos (Posgrado)'],
                'A-402' => ['x' => 410, 'y' => 220, 'nombre' => 'Lab. de Fototérmicas (Posgrado)'],
                'A-403' => ['x' => 430, 'y' => 220, 'nombre' => 'Lab. de Nanomateriales y Nanotecnología (Posgrado)'],
                'A-404' => ['x' => 450, 'y' => 220, 'nombre' => 'Sala de profesores'],
                'A-405' => ['x' => 370, 'y' => 240, 'nombre' => 'Trabajo Terminal Mecatrónica'],
                'A-406' => ['x' => 390, 'y' => 240, 'nombre' => 'Trabajo Terminal Mecatrónica']
            ],
            1 => [ // Primer Piso
                'A-410' => ['x' => 370, 'y' => 220, 'nombre' => 'Sala de alumnos (Posgrado)'],
                'A-411' => ['x' => 390, 'y' => 220, 'nombre' => 'Sala de profesores 1 (Posgrado)'],
                'A-412' => ['x' => 410, 'y' => 220, 'nombre' => 'Sala de alumnos (Posgrado)'],
                'A-413' => ['x' => 430, 'y' => 220, 'nombre' => 'Lab. de Sistemas Complejos (Posgrado)'],
                'A-414' => ['x' => 450, 'y' => 220, 'nombre' => 'Sala de profesores de 2 (Posgrado)'],
                'A-415' => ['x' => 370, 'y' => 240, 'nombre' => 'Sala de alumnos (Posgrado)'],
                'A-416' => ['x' => 390, 'y' => 240, 'nombre' => 'Sala de alumnos (Posgrado)']
            ],
            2 => [ // Segundo Piso
                'A-420' => ['x' => 370, 'y' => 220, 'nombre' => 'Sala de profesores'],
                'A-421' => ['x' => 390, 'y' => 220, 'nombre' => 'Sala de profesores'],
                'A-422' => ['x' => 410, 'y' => 220, 'nombre' => 'Sala de alumnos (Posgrado)'],
                'A-423' => ['x' => 430, 'y' => 220, 'nombre' => 'Aula'],
                'A-424' => ['x' => 450, 'y' => 220, 'nombre' => 'Aula'],
                'A-425' => ['x' => 370, 'y' => 240, 'nombre' => 'Aula'],
                'A-426' => ['x' => 390, 'y' => 240, 'nombre' => 'Aula']
            ]
        ],
        'LC' => [
            0 => [ // Planta Baja (Centro Sur)
                'LC-100' => ['x' => 520, 'y' => 520, 'nombre' => 'Lab. de Química y Biología 1'],
                'LC-101' => ['x' => 540, 'y' => 520, 'nombre' => 'Lab. de Química y Biología 2'],
                'LC-102' => ['x' => 560, 'y' => 520, 'nombre' => 'Lab. de Física 1'],
                'LC-103' => ['x' => 580, 'y' => 520, 'nombre' => 'Lab. de Física 2'],
                'LC-104' => ['x' => 600, 'y' => 520, 'nombre' => 'Biblioteca'],
                'LC-105' => ['x' => 620, 'y' => 520, 'nombre' => 'Red de Género'],
                // Centro Norte
                'LC-106' => ['x' => 520, 'y' => 580, 'nombre' => 'Biblioteca'],
                'LC-107' => ['x' => 540, 'y' => 580, 'nombre' => 'Biblioteca'],
                'LC-108' => ['x' => 560, 'y' => 580, 'nombre' => 'Biblioteca'],
                'LC-109' => ['x' => 580, 'y' => 580, 'nombre' => 'Biblioteca'],
                'LC-110' => ['x' => 600, 'y' => 580, 'nombre' => 'Taller de Máquinas y Herramientas'],
                'LC-111' => ['x' => 620, 'y' => 580, 'nombre' => 'Taller de Máquinas y Herramientas']
            ],
            1 => [ // Primer Piso
                'LC-110' => ['x' => 520, 'y' => 540, 'nombre' => 'Lab. de Cómputo Móvil'],
                'LC-111' => ['x' => 540, 'y' => 540, 'nombre' => 'Sala de Profesores Telemática'],
                'LC-112' => ['x' => 560, 'y' => 540, 'nombre' => 'Lab. Telemática II'],
                'LC-113' => ['x' => 580, 'y' => 540, 'nombre' => 'Lab. Telemática I'],
                'LC-114' => ['x' => 600, 'y' => 540, 'nombre' => 'Lab. Electrónica II'],
                'LC-115' => ['x' => 620, 'y' => 540, 'nombre' => 'Lab. Electrónica II'],
                'LC-116' => ['x' => 520, 'y' => 560, 'nombre' => 'Coordinación de Proyectos Vinculados'],
                'LC-117' => ['x' => 540, 'y' => 560, 'nombre' => 'Lab. de Electrónica 1'],
                'LC-118' => ['x' => 560, 'y' => 560, 'nombre' => 'Lab. de Sistemas Digitales 1'],
                'LC-119' => ['x' => 580, 'y' => 560, 'nombre' => 'Lab. de Telecomunicaciones']
            ],
            2 => [ // Segundo Piso
                'LC-120' => ['x' => 520, 'y' => 540, 'nombre' => 'Aula'],
                'LC-121' => ['x' => 540, 'y' => 540, 'nombre' => 'Lab. de Sistemas Digitales II'],
                'LC-122' => ['x' => 560, 'y' => 540, 'nombre' => 'Lab. de Bioelectrónica'],
                'LC-123' => ['x' => 580, 'y' => 540, 'nombre' => 'Lab. de Bioelectrónica'],
                'LC-124' => ['x' => 600, 'y' => 540, 'nombre' => 'Lab. de Robótica de Competencia'],
                'LC-125' => ['x' => 620, 'y' => 540, 'nombre' => 'Lab. de Neumática y Control de Procesos'],
                'LC-126' => ['x' => 520, 'y' => 560, 'nombre' => 'Sindicato docente'],
                'LC-127' => ['x' => 540, 'y' => 560, 'nombre' => 'Personal de apoyo y asistencia'],
                'LC-128' => ['x' => 560, 'y' => 560, 'nombre' => 'Lab. de Neumática y Control de Procesos'],
                'LC-130' => ['x' => 580, 'y' => 560, 'nombre' => 'Aula'],
                'LC-131' => ['x' => 600, 'y' => 560, 'nombre' => 'Aula'],
                'LC-132' => ['x' => 620, 'y' => 560, 'nombre' => 'Lab. de Trabajo Terminal Telemática'],
                'LC-133' => ['x' => 640, 'y' => 560, 'nombre' => 'Lab. de Trabajo Terminal Telemática']
            ]
        ],
        'EG' => [
            0 => [ // Planta Baja
                'EG-001' => ['x' => 720, 'y' => 340, 'nombre' => 'Servicio Médico, Psicológico y Dental'],
                'EG-002' => ['x' => 740, 'y' => 340, 'nombre' => 'Subdirección de Servicios Educativos'],
                'EG-003' => ['x' => 760, 'y' => 340, 'nombre' => 'Actividades Culturales y Deportivas'],
                'EG-004' => ['x' => 780, 'y' => 340, 'nombre' => 'Servicios Estudiantiles'],
                'EG-005' => ['x' => 800, 'y' => 340, 'nombre' => 'Bolsa de trabajo'],
                'EG-006' => ['x' => 720, 'y' => 360, 'nombre' => 'Extensión y Apoyos Educativos'],
                'EG-007' => ['x' => 740, 'y' => 360, 'nombre' => 'Gestión Escolar'],
                'EG-008' => ['x' => 760, 'y' => 360, 'nombre' => 'Decanato'],
                'EG-009' => ['x' => 780, 'y' => 360, 'nombre' => 'Asistente del Decanato'],
                'EG-010' => ['x' => 800, 'y' => 360, 'nombre' => 'Subdirección Administrativa'],
                'EG-011' => ['x' => 720, 'y' => 380, 'nombre' => 'Recursos Materiales y Servicios'],
                'EG-012' => ['x' => 740, 'y' => 380, 'nombre' => 'Capital Humano'],
                'EG-013' => ['x' => 760, 'y' => 380, 'nombre' => 'Recursos Financieros'],
                'EG-014' => ['x' => 780, 'y' => 380, 'nombre' => 'Archivo'],
                'EG-015' => ['x' => 800, 'y' => 380, 'nombre' => 'Auditorio']
            ],
            1 => [ // Primer Piso
                'EG-100' => ['x' => 720, 'y' => 320, 'nombre' => 'Unidad de Informática'],
                'EG-101' => ['x' => 740, 'y' => 320, 'nombre' => 'Coordinación de Gestión Técnica'],
                'EG-102' => ['x' => 760, 'y' => 320, 'nombre' => 'Unidad Politécnica de Integración Social'],
                'EG-103' => ['x' => 780, 'y' => 320, 'nombre' => 'Sala de Consejo'],
                'EG-104' => ['x' => 800, 'y' => 320, 'nombre' => 'Fotocopiado'],
                'EG-105' => ['x' => 720, 'y' => 300, 'nombre' => 'Jefatura de Investigación'],
                'EG-106' => ['x' => 740, 'y' => 300, 'nombre' => 'Jefatura de Posgrado e Investigación'],
                'EG-107' => ['x' => 760, 'y' => 300, 'nombre' => 'Jefatura de Posgrado'],
                'EG-108' => ['x' => 780, 'y' => 300, 'nombre' => 'Dirección'],
                'EG-109' => ['x' => 800, 'y' => 300, 'nombre' => 'Subdirección Académica'],
                'EG-110' => ['x' => 720, 'y' => 280, 'nombre' => 'Departamento de Ciencias Básicas'],
                'EG-111' => ['x' => 740, 'y' => 280, 'nombre' => 'Departamento de Ingeniería'],
                'EG-112' => ['x' => 760, 'y' => 280, 'nombre' => 'Formación Integral e Institucional'],
                'EG-113' => ['x' => 780, 'y' => 280, 'nombre' => 'Tecnologías Avanzadas'],
                'EG-114' => ['x' => 800, 'y' => 280, 'nombre' => 'Evaluación y Seguimiento Académico'],
                'EG-115' => ['x' => 720, 'y' => 260, 'nombre' => 'Innovación Educativa'],
                'EG-116' => ['x' => 740, 'y' => 260, 'nombre' => 'Tecnología Educativa y Campus Virtual'],
                'EG-117' => ['x' => 760, 'y' => 260, 'nombre' => 'Estímulos y Protección Civil'],
                'EG-118' => ['x' => 780, 'y' => 260, 'nombre' => 'Plan de Acción Tutorial'],
                'EG-119' => ['x' => 800, 'y' => 260, 'nombre' => 'Sala de Juntas Subdirección Académica']
            ]
        ],
        'EP' => [
            0 => [ // Planta Baja
                'EP-01' => ['x' => 170, 'y' => 520, 'nombre' => 'Robótica Industrial'],
                'EP-02' => ['x' => 190, 'y' => 520, 'nombre' => 'Manufactura Básica'],
                'EP-03' => ['x' => 210, 'y' => 520, 'nombre' => 'Manufactura Avanzada'],
                'EP-04' => ['x' => 230, 'y' => 520, 'nombre' => 'Laboratorio de Metrología'],
                'EP-05' => ['x' => 250, 'y' => 520, 'nombre' => 'Laboratorio de Red de Expertos'],
                'EP-06' => ['x' => 170, 'y' => 540, 'nombre' => 'Trabajo Terminal'],
                'EP-07' => ['x' => 190, 'y' => 540, 'nombre' => 'Área de Lockers'],
                'EP-08' => ['x' => 210, 'y' => 540, 'nombre' => 'Lab. de Manufactura Asistida por Computadora'],
                'EP-09' => ['x' => 230, 'y' => 540, 'nombre' => 'Consultorio Médico']
            ],
            1 => [ // Primer Piso
                'EP-101' => ['x' => 170, 'y' => 500, 'nombre' => 'Laboratorio de cálculo y simulación 2'],
                'EP-102' => ['x' => 190, 'y' => 500, 'nombre' => 'Laboratorio de cálculo y simulación 1'],
                'EP-103' => ['x' => 210, 'y' => 500, 'nombre' => 'Laboratorio de biomecánica'],
                'EP-104' => ['x' => 230, 'y' => 500, 'nombre' => 'Sala de Cómputo 10'],
                'EP-105' => ['x' => 250, 'y' => 500, 'nombre' => 'Usos múltiples']
            ]
        ]
    ];

    // Definición de conexiones entre edificios (pasillos principales)
    private $conexiones_edificios = [
        // Conexiones principales del campus
        ['A1', 'A2', 80],  // 80 metros entre A1 y A2
        ['A2', 'A3', 90],  // 90 metros entre A2 y A3
        ['A3', 'A4', 70],  // 70 metros entre A3 y A4
        ['A1', 'LC', 120], // 120 metros entre A1 y LC
        ['A2', 'LC', 100], // 100 metros entre A2 y LC
        ['LC', 'EG', 150], // 150 metros entre LC y EG
        ['A1', 'EP', 110], // 110 metros entre A1 y EP
        ['EP', 'LC', 130], // 130 metros entre EP y LC
        ['A4', 'EG', 160], // 160 metros entre A4 y EG
        ['A3', 'EP', 85],  // 85 metros entre A3 y EP
        // Conexiones secundarias
        ['A1', 'A3', 95],  // Conexión diagonal A1-A3
        ['A2', 'A4', 85],  // Conexión diagonal A2-A4
        ['A4', 'LC', 140], // Conexión A4-LC
        ['EP', 'EG', 200]  // Conexión EP-EG (más larga)
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Actualiza la base de datos con las coordenadas reales
     */
    public function actualizarCoordenadasReales() {
        try {
            // Limpiar datos existentes
            $this->pdo->exec("DELETE FROM Rutas");
            $this->pdo->exec("DELETE FROM PuntosConexion");
            $this->pdo->exec("UPDATE Aulas SET coordenada_x = NULL, coordenada_y = NULL");

            // Insertar coordenadas de aulas
            foreach ($this->aulas_por_edificio as $edificio_codigo => $pisos) {
                // Obtener ID del edificio
                $stmt = $this->pdo->prepare("SELECT idEdificio FROM Edificios WHERE nombre LIKE ?");
                $stmt->execute(["%$edificio_codigo%"]);
                $edificio_id = $stmt->fetchColumn();

                if (!$edificio_id) continue;

                foreach ($pisos as $piso => $aulas) {
                    foreach ($aulas as $codigo_aula => $datos) {
                        $stmt = $this->pdo->prepare("
                            UPDATE Aulas 
                            SET coordenada_x = ?, coordenada_y = ? 
                            WHERE numeroAula = ? AND piso = ? AND idEdificio = ?
                        ");
                        $stmt->execute([
                            $datos['x'],
                            $datos['y'],
                            $codigo_aula,
                            $piso + 1, // Los pisos en BD empiezan en 1
                            $edificio_id
                        ]);
                    }
                }
            }

            // Insertar puntos de conexión (entradas de edificios)
            foreach ($this->edificios_coordenadas as $codigo => $datos) {
                $stmt = $this->pdo->prepare("SELECT idEdificio FROM Edificios WHERE nombre LIKE ?");
                $stmt->execute(["%$codigo%"]);
                $edificio_id = $stmt->fetchColumn();

                if ($edificio_id) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO PuntosConexion (nombre, tipo, piso, idEdificio, coordenada_x, coordenada_y)
                        VALUES (?, 'entrada', 1, ?, ?, ?)
                    ");
                    $stmt->execute([
                        "Entrada-$codigo",
                        $edificio_id,
                        $datos['entrada']['x'],
                        $datos['entrada']['y']
                    ]);
                }
            }

            // Insertar rutas entre edificios
            foreach ($this->conexiones_edificios as $conexion) {
                [$edificio1, $edificio2, $distancia] = $conexion;

                // Obtener IDs de puntos de entrada
                $stmt = $this->pdo->prepare("
                    SELECT pc.id FROM PuntosConexion pc
                    JOIN Edificios e ON pc.idEdificio = e.idEdificio
                    WHERE e.nombre LIKE ? AND pc.tipo = 'entrada'
                ");

                $stmt->execute(["%$edificio1%"]);
                $punto1_id = $stmt->fetchColumn();

                $stmt->execute(["%$edificio2%"]);
                $punto2_id = $stmt->fetchColumn();

                if ($punto1_id && $punto2_id) {
                    // Insertar ruta bidireccional
                    $stmt = $this->pdo->prepare("
                        INSERT INTO Rutas (origen_tipo, origen_id, destino_tipo, destino_id, distancia, es_bidireccional)
                        VALUES ('punto', ?, 'punto', ?, ?, 1)
                    ");
                    $stmt->execute([$punto1_id, $punto2_id, $distancia]);
                }
            }

            // Insertar rutas internas de cada edificio
            $this->insertarRutasInternas();

            return true;

        } catch (Exception $e) {
            error_log('Error actualizando coordenadas reales: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta rutas internas dentro de cada edificio
     */
    private function insertarRutasInternas() {
        foreach ($this->aulas_por_edificio as $edificio_codigo => $pisos) {
            // Obtener ID del edificio
            $stmt = $this->pdo->prepare("SELECT idEdificio FROM Edificios WHERE nombre LIKE ?");
            $stmt->execute(["%$edificio_codigo%"]);
            $edificio_id = $stmt->fetchColumn();

            if (!$edificio_id) continue;

            // Obtener punto de entrada del edificio
            $stmt = $this->pdo->prepare("
                SELECT id FROM PuntosConexion 
                WHERE idEdificio = ? AND tipo = 'entrada'
            ");
            $stmt->execute([$edificio_id]);
            $entrada_id = $stmt->fetchColumn();

            if (!$entrada_id) continue;

            // Conectar entrada con aulas de planta baja
            if (isset($pisos[0])) { // Planta baja
                foreach ($pisos[0] as $codigo_aula => $datos) {
                    $stmt = $this->pdo->prepare("
                        SELECT idAula FROM Aulas 
                        WHERE numeroAula = ? AND piso = 1 AND idEdificio = ?
                    ");
                    $stmt->execute([$codigo_aula, $edificio_id]);
                    $aula_id = $stmt->fetchColumn();

                    if ($aula_id) {
                        // Calcular distancia desde entrada hasta aula
                        $entrada_coords = $this->edificios_coordenadas[$edificio_codigo]['entrada'];
                        $distancia = sqrt(
                            pow($datos['x'] - $entrada_coords['x'], 2) +
                            pow($datos['y'] - $entrada_coords['y'], 2)
                        );

                        // Insertar ruta desde entrada a aula
                        $stmt = $this->pdo->prepare("
                            INSERT INTO Rutas (origen_tipo, origen_id, destino_tipo, destino_id, distancia, es_bidireccional)
                            VALUES ('punto', ?, 'aula', ?, ?, 1)
                        ");
                        $stmt->execute([$entrada_id, $aula_id, $distancia]);
                    }
                }
            }

            // Conectar aulas dentro del mismo piso
            foreach ($pisos as $piso => $aulas) {
                $aulas_array = array_keys($aulas);

                for ($i = 0; $i < count($aulas_array); $i++) {
                    for ($j = $i + 1; $j < count($aulas_array); $j++) {
                        $aula1 = $aulas_array[$i];
                        $aula2 = $aulas_array[$j];

                        // Obtener IDs de aulas
                        $stmt = $this->pdo->prepare("
                            SELECT idAula FROM Aulas 
                            WHERE numeroAula = ? AND piso = ? AND idEdificio = ?
                        ");

                        $stmt->execute([$aula1, $piso + 1, $edificio_id]);
                        $aula1_id = $stmt->fetchColumn();

                        $stmt->execute([$aula2, $piso + 1, $edificio_id]);
                        $aula2_id = $stmt->fetchColumn();

                        if ($aula1_id && $aula2_id) {
                            // Calcular distancia entre aulas del mismo piso
                            $coords1 = $aulas[$aula1];
                            $coords2 = $aulas[$aula2];
                            $distancia = sqrt(
                                pow($coords2['x'] - $coords1['x'], 2) +
                                pow($coords2['y'] - $coords1['y'], 2)
                            );

                            // Solo conectar aulas cercanas (menos de 50 metros)
                            if ($distancia < 50) {
                                $stmt = $this->pdo->prepare("
                                    INSERT INTO Rutas (origen_tipo, origen_id, destino_tipo, destino_id, distancia, es_bidireccional)
                                    VALUES ('aula', ?, 'aula', ?, ?, 1)
                                ");
                                $stmt->execute([$aula1_id, $aula2_id, $distancia]);
                            }
                        }
                    }
                }
            }

            // Insertar escaleras y conexiones entre pisos
            $this->insertarConexionesPisos($edificio_id, $edificio_codigo);
        }
    }

    /**
     * Inserta puntos de escaleras y conexiones entre pisos
     */
    private function insertarConexionesPisos($edificio_id, $edificio_codigo) {
        $datos_edificio = $this->edificios_coordenadas[$edificio_codigo];

        // Crear punto de escalera (centro del edificio)
        $escalera_x = $datos_edificio['x'] + $datos_edificio['width'] / 2;
        $escalera_y = $datos_edificio['y'] + $datos_edificio['height'] / 2;

        for ($piso = 1; $piso <= $datos_edificio['pisos']; $piso++) {
            $stmt = $this->pdo->prepare("
                INSERT INTO PuntosConexion (nombre, tipo, piso, idEdificio, coordenada_x, coordenada_y)
                VALUES (?, 'escalera', ?, ?, ?, ?)
            ");
            $stmt->execute([
                "Escalera-$edificio_codigo-P$piso",
                $piso,
                $edificio_id,
                $escalera_x,
                $escalera_y
            ]);
        }

        // Conectar escaleras entre pisos
        for ($piso = 1; $piso < $datos_edificio['pisos']; $piso++) {
            $stmt = $this->pdo->prepare("
                SELECT id FROM PuntosConexion 
                WHERE nombre = ? AND idEdificio = ?
            ");

            $stmt->execute(["Escalera-$edificio_codigo-P$piso", $edificio_id]);
            $escalera_actual = $stmt->fetchColumn();

            $stmt->execute(["Escalera-$edificio_codigo-P" . ($piso + 1), $edificio_id]);
            $escalera_siguiente = $stmt->fetchColumn();

            if ($escalera_actual && $escalera_siguiente) {
                // Tiempo estimado para subir un piso: 3 metros equivalentes
                $stmt = $this->pdo->prepare("
                    INSERT INTO Rutas (origen_tipo, origen_id, destino_tipo, destino_id, distancia, es_bidireccional, tipo_conexion)
                    VALUES ('punto', ?, 'punto', ?, 3, 1, 'escalera')
                ");
                $stmt->execute([$escalera_actual, $escalera_siguiente]);
            }
        }
    }

    /**
     * Obtiene la información completa de un edificio
     */
    public function obtenerInfoEdificio($codigo_edificio) {
        if (!isset($this->edificios_coordenadas[$codigo_edificio])) {
            return null;
        }

        $datos = $this->edificios_coordenadas[$codigo_edificio];
        $aulas = $this->aulas_por_edificio[$codigo_edificio] ?? [];

        return [
            'codigo' => $codigo_edificio,
            'coordenadas' => $datos,
            'aulas_por_piso' => $aulas,
            'total_aulas' => array_sum(array_map('count', $aulas)),
            'conexiones' => $this->obtenerConexionesEdificio($codigo_edificio)
        ];
    }

    /**
     * Obtiene las conexiones de un edificio específico
     */
    private function obtenerConexionesEdificio($codigo_edificio) {
        $conexiones = [];

        foreach ($this->conexiones_edificios as $conexion) {
            [$edificio1, $edificio2, $distancia] = $conexion;

            if ($edificio1 === $codigo_edificio) {
                $conexiones[] = ['destino' => $edificio2, 'distancia' => $distancia];
            } elseif ($edificio2 === $codigo_edificio) {
                $conexiones[] = ['destino' => $edificio1, 'distancia' => $distancia];
            }
        }

        return $conexiones;
    }

    /**
     * Obtiene todas las coordenadas de edificios
     */
    public function obtenerCoordenadasEdificios() {
        return $this->edificios_coordenadas;
    }

    /**
     * Obtiene las aulas de un edificio específico
     */
    public function obtenerAulasEdificio($codigo_edificio, $piso = null) {
        if (!isset($this->aulas_por_edificio[$codigo_edificio])) {
            return [];
        }

        if ($piso !== null) {
            return $this->aulas_por_edificio[$codigo_edificio][$piso] ?? [];
        }

        return $this->aulas_por_edificio[$codigo_edificio];
    }

    /**
     * Busca un aula por su código
     */
    public function buscarAula($codigo_aula) {
        foreach ($this->aulas_por_edificio as $edificio => $pisos) {
            foreach ($pisos as $piso => $aulas) {
                if (isset($aulas[$codigo_aula])) {
                    return [
                        'edificio' => $edificio,
                        'piso' => $piso,
                        'coordenadas' => $aulas[$codigo_aula],
                        'codigo' => $codigo_aula
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Calcula la distancia real entre dos puntos
     */
    public function calcularDistanciaReal($x1, $y1, $x2, $y2) {
        // Distancia euclidiana con factor de conversión a metros
        // 1 unidad del mapa = ~0.5 metros
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2)) * 0.5;
    }

    /**
     * Obtiene estadísticas del campus
     */
    public function obtenerEstadisticasCampus() {
        $total_aulas = 0;
        $aulas_por_edificio = [];

        foreach ($this->aulas_por_edificio as $edificio => $pisos) {
            $count = array_sum(array_map('count', $pisos));
            $total_aulas += $count;
            $aulas_por_edificio[$edificio] = $count;
        }

        return [
            'total_edificios' => count($this->edificios_coordenadas),
            'total_aulas' => $total_aulas,
            'aulas_por_edificio' => $aulas_por_edificio,
            'total_conexiones' => count($this->conexiones_edificios),
            'area_campus' => $this->calcularAreaCampus()
        ];
    }

    /**
     * Calcula el área aproximada del campus
     */
    private function calcularAreaCampus() {
        $min_x = $min_y = PHP_INT_MAX;
        $max_x = $max_y = PHP_INT_MIN;

        foreach ($this->edificios_coordenadas as $datos) {
            $min_x = min($min_x, $datos['x']);
            $min_y = min($min_y, $datos['y']);
            $max_x = max($max_x, $datos['x'] + $datos['width']);
            $max_y = max($max_y, $datos['y'] + $datos['height']);
        }

        // Convertir a metros cuadrados (factor de conversión)
        return ($max_x - $min_x) * ($max_y - $min_y) * 0.25; // m²
    }

    /**
     * Valida la integridad de los datos del mapa
     */
    public function validarIntegridad() {
        $errores = [];

        // Validar que todos los edificios tengan coordenadas
        foreach ($this->edificios_coordenadas as $codigo => $datos) {
            if (!isset($datos['x'], $datos['y'], $datos['width'], $datos['height'])) {
                $errores[] = "Edificio $codigo tiene coordenadas incompletas";
            }
        }

        // Validar que todas las aulas tengan coordenadas
        foreach ($this->aulas_por_edificio as $edificio => $pisos) {
            foreach ($pisos as $piso => $aulas) {
                foreach ($aulas as $codigo => $datos) {
                    if (!isset($datos['x'], $datos['y'], $datos['nombre'])) {
                        $errores[] = "Aula $codigo en $edificio piso $piso tiene datos incompletos";
                    }
                }
            }
        }

        // Validar conexiones
        foreach ($this->conexiones_edificios as $conexion) {
            [$edificio1, $edificio2, $distancia] = $conexion;

            if (!isset($this->edificios_coordenadas[$edificio1])) {
                $errores[] = "Conexión referencia edificio inexistente: $edificio1";
            }

            if (!isset($this->edificios_coordenadas[$edificio2])) {
                $errores[] = "Conexión referencia edificio inexistente: $edificio2";
            }

            if ($distancia <= 0) {
                $errores[] = "Distancia inválida entre $edificio1 y $edificio2: $distancia";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'total_edificios' => count($this->edificios_coordenadas),
            'total_aulas' => array_sum(array_map(function($pisos) {
                return array_sum(array_map('count', $pisos));
            }, $this->aulas_por_edificio))
        ];
    }
}