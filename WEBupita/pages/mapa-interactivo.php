<?php include '../includes/header.php'; ?>

<main class="content">
    <h1 class="page-title">Mapa Interactivo</h1>

    <div style="text-align: center;">
        <img src="../images/MapaInteractivo.jpeg" alt="Mapa UPIITA" style="width: 100%; max-width: 1000px; height: auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); margin-bottom: 30px;">
    </div>

    <section>
        <h2 class="section-title">Acceso directo a los edificios</h2>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; max-width: 1000px; margin: auto;">
            <!-- A1 -->
            <a href="aulas/A1.php" style="flex: 1 1 200px; background-color: #3498db; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>A1 - Aulas 1</strong><br>
                Edificio de clases generales.
            </a>

            <!-- A2 -->
            <a href="aulas/A2.php" style="flex: 1 1 200px; background-color: #e91e63; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>A2 - Aulas 2</strong><br>
                Salones intermedios y laboratorios básicos.
            </a>

            <!-- A3 -->
            <a href="aulas/A3.php" style="flex: 1 1 200px; background-color: #f39c12; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>A3 - Aulas 3</strong><br>
                Talleres prácticos y proyectos.
            </a>

            <!-- A4 -->
            <a href="aulas/A4.php" style="flex: 1 1 200px; background-color: #2ecc71; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>A4 - Aulas 4</strong><br>
                Salones adicionales y espacios de consulta.
            </a>

            <!-- LC -->
            <a href="laboratorios/LC.php" style="flex: 1 1 200px; background-color: #34495e; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>LC - Laboratorio Central</strong><br>
                Área técnica para prácticas de ingeniería.
            </a>

            <!-- EG -->
            <a href="gobierno/EG.php" style="flex: 1 1 200px; background-color: #f1c40f; color: black; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>EG - Edificio de Gobierno</strong><br>
                Administración y gestión académica.
            </a>

            <!-- LP -->
            <a href="laboratorios/LP.php" style="flex: 1 1 200px; background-color: #e74c3c; color: white; padding: 15px; text-decoration: none; border-radius: 10px; text-align: center;">
                <strong>LP - Laboratorios Pesados</strong><br>
                Laboratorios de alta capacidad técnica.
            </a>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
