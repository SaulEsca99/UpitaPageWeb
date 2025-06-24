<?php
session_start();
require_once '../includes/conexion.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];

        // Redirigir al inicio o a un panel
        header("Location: index.php");
        exit;
    } else {
        $mensaje = "❌ Email o contraseña incorrectos.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<main class="content">
    <h1 class="page-title">Iniciar Sesión</h1>

    <?php if ($mensaje): ?>
        <p style="color: red; font-weight: bold;"><?= $mensaje ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST" style="max-width: 400px; margin: auto;">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Entrar</button>
    </form>
</main>

<?php include '../includes/footer.php'; ?>
