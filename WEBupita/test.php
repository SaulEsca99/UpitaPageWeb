<?php
// Ruta: WEBupita/test.php
// Archivo de prueba para verificar que PHP funciona

echo "<h1>✅ PHP está funcionando</h1>";
echo "<p>Fecha y hora: " . date('Y-m-d H:i:s') . "</p>";

// Verificar extensiones
echo "<h2>Extensiones PHP:</h2>";
echo "<p>PDO: " . (extension_loaded('pdo') ? '✅ Disponible' : '❌ No disponible') . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Disponible' : '❌ No disponible') . "</p>";

// Probar conexión a base de datos
echo "<h2>Prueba de conexión a base de datos:</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=upiita", "root", "tired2019");
    echo "<p>✅ Conexión a base de datos exitosa</p>";

    // Verificar tabla
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas encontradas: " . count($tables) . "</p>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

phpinfo();
?>