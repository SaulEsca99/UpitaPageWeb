<?php
session_start();
session_unset();  // Borra todas las variables de sesión
session_destroy(); // Destruye la sesión

// Redirige al inicio
header("Location: index.php");
exit;
