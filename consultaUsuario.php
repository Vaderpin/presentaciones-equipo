<?php
session_start();

// Credenciales de la base de datos
//$servername = "fdb1027.biz.nf"; // El servidor
//$username = "4588094_4588094"; // Tu usuario
//$password = "Javier993"; // Tu contraseña
//$dbname = "4588094_4588094"; // Tu base de datos

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "4588094_4588094";


// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el formulario se envió
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $correo = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    // Mostrar los datos recibidos para depuración
    echo "Correo recibido: $correo <br>";
    echo "Contraseña recibida: $contrasena <br>";

    // Consultar si existe el usuario con ese correo y contraseña
    $sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $correo, $contrasena); // 'ss' significa que son dos cadenas (strings)
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si el usuario existe
    if ($result->num_rows > 0) {
        // Usuario existe, iniciar sesión
        $user = $result->fetch_assoc();
        $_SESSION['id'] = $user['id']; // ← Esta es la clave para que 'marcar_completada.php' funcione
        $_SESSION['usuario'] = $correo;
        $_SESSION['tipo'] = $user['tipo']; // Guardamos el tipo de usuario (alumno o maestro)

        // Redirigir dependiendo del tipo de usuario
        if ($_SESSION['tipo'] == 'alumno') {
            header("Location: alumno_dashboard.php"); // Página del alumno
        } else {
            header("Location: maestro_dashboard.php"); // Página del maestro
        }
        exit(); // Asegúrate de salir para evitar que el código posterior se ejecute
    } else {
        // Si no existe el usuario, redirigir al login con mensaje de error
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header("Location: index.php");
        exit(); // Asegúrate de salir después de redirigir
            // Asegúrate de salir para evitar que el código posterior se ejecute
    }
}

$conn->close();