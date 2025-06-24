<?php
require_once '../includes/conexion.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar'];

    if ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash]);
            $mensaje = "✅ Usuario registrado correctamente.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "⚠️ El correo ya está registrado.";
            } else {
                $mensaje = "Error al registrar usuario.";
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<main class="content">
    <h1 class="page-title">Registro</h1>

    <?php if ($mensaje): ?>
        <p style="color: red; font-weight: bold;"><?= $mensaje ?></p>
    <?php endif; ?>

    <form action="registro.php" method="POST" style="max-width: 400px; margin: auto;">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Confirmar contraseña:</label><br>
        <input type="password" name="confirmar" required><br><br>

        <button type="submit">Registrarse</button>
    </form>
</main>

<?php include '../includes/footer.php'; ?>
