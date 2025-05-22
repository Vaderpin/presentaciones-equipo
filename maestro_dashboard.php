<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'maestro') {
    header("Location: index.php");
    exit();
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "4588094_4588094";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener información del maestro
$correo = $_SESSION['usuario'];
$sql = "SELECT id, nombre FROM usuarios WHERE correo = ? AND tipo = 'maestro'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Si se envió un nuevo grupo, guardarlo en la base de datos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre_clase'])) {
    $nombre_clase = trim($_POST['nombre_clase']);
    $codigo_clase = strtoupper(substr(md5(uniqid()), 0, 6)); // Genera un código único

    if (!empty($nombre_clase)) {
        $sqlInsert = "INSERT INTO clases (codigo_clase, nombre, id_maestro) VALUES (?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("ssi", $codigo_clase, $nombre_clase, $usuario['id']);
        if ($stmtInsert->execute()) {
            $_SESSION['success'] = "Clase creada con éxito.";
        } else {
            $_SESSION['error'] = "Error al crear la clase.";
        }
        $stmtInsert->close();
        header("Location: maestro_dashboard.php"); // Recargar la página
        exit();
    }
}

// Obtener las clases del maestro
$sqlClases = "SELECT id, nombre, codigo_clase FROM clases WHERE id_maestro = ?";
$stmtClases = $conn->prepare($sqlClases);
$stmtClases->bind_param("i", $usuario['id']);
$stmtClases->execute();
$resultClases = $stmtClases->get_result();
$clases = $resultClases->fetch_all(MYSQLI_ASSOC);

// Obtener tareas del maestro
$sqlTareas = "SELECT t.id, t.titulo, t.descripcion, t.fecha_entrega, c.nombre AS clase_nombre 
              FROM tareas t 
              JOIN clases c ON t.id_clase = c.id 
              WHERE c.id_maestro = ?";
$stmtTareas = $conn->prepare($sqlTareas);
$stmtTareas->bind_param("i", $usuario['id']);
$stmtTareas->execute();
$resultTareas = $stmtTareas->get_result();
$tareas = $resultTareas->fetch_all(MYSQLI_ASSOC);

// Inicializamos los arrays de completos e incompletos
$alumnos_completos = [];
$alumnos_incompletos = [];

foreach ($clases as $clase) {
    // Contar alumnos inscritos en la clase
    $sqlInscritos = "SELECT COUNT(DISTINCT ac.id_alumno) AS inscritos
                     FROM alumnos_clases ac
                     WHERE ac.id_clase = ?";
    $stmtInscritos = $conn->prepare($sqlInscritos);
    $stmtInscritos->bind_param("i", $clase['id']);
    $stmtInscritos->execute();
    $resultInscritos = $stmtInscritos->get_result();
    $inscritos = $resultInscritos->fetch_assoc()['inscritos'];

    // Contar alumnos que han entregado todas las tareas
    $sqlCompletos = "SELECT COUNT(DISTINCT e.id_alumno) AS completos
                     FROM entregas e
                     JOIN tareas t ON e.id_tarea = t.id
                     WHERE t.id_clase = ? 
                     AND NOT EXISTS (
                         SELECT 1
                         FROM tareas t2
                         WHERE t2.id_clase = t.id_clase
                         AND NOT EXISTS (
                             SELECT 1
                             FROM entregas e2
                             WHERE e2.id_tarea = t2.id
                             AND e2.id_alumno = e.id_alumno
                         )
                     )";
    $stmtCompletos = $conn->prepare($sqlCompletos);
    $stmtCompletos->bind_param("i", $clase['id']);
    $stmtCompletos->execute();
    $resultCompletos = $stmtCompletos->get_result();
    $completos = $resultCompletos->fetch_assoc()['completos'];

    // Calcular los incompletos
    $incompletos = $inscritos - $completos;

    // Guardamos las estadísticas de los alumnos completos e incompletos
    $alumnos_completos[$clase['id']] = $completos;
    $alumnos_incompletos[$clase['id']] = $incompletos;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Maestro - EducaMente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #e67e22;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        }
        
        /* Sidebar mejorada */
        .sidebar {
            width: 280px;
            background: var(--dark-color);
            padding: 30px 20px;
            color: white;
            transition: var(--transition);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }
        
        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .sidebar h2:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 10px;
        }
        
        .sidebar button {
            width: 100%;
            padding: 12px 20px;
            margin: 8px 0;
            background: rgba(255, 255, 255, 0.08);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-align: left;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .sidebar button i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar button:hover {
            background: var(--primary-color);
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }
        
        .sidebar button.active {
            background: var(--primary-color);
            color: white;
        }
        
        .sidebar .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .sidebar .logo h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .sidebar .logo span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .spacer {
            flex-grow: 1;
        }
        
        /* Content area */
        .content {
            flex-grow: 1;
            padding: 0;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 5;
        }
        
        .header h3 {
            font-size: 22px;
            font-weight: 500;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .user-info .name {
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Main content */
        #mainContent {
            padding: 30px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        #mainContent > h3 {
            font-size: 24px;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        #mainContent > h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        /* Tarjetas de clases y alumnos */
        .grupo-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .grupo-item {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .grupo-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .grupo-item:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .grupo-item a {
            text-decoration: none;
            font-size: 18px;
            color: var(--dark-color);
            font-weight: 600;
            display: block;
            text-align: center;
            padding: 10px 0;
            transition: var(--transition);
        }
        
        .grupo-item a:hover {
            color: var(--primary-color);
        }
        
        /* Tarjetas de tareas */
        .tareas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .tarea-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .tarea-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .tarea-card:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .tarea-titulo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .tarea-descripcion {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .tarea-clase {
            font-size: 14px;
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .tarea-clase i {
            margin-right: 8px;
        }
        
        .tarea-fecha {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .tarea-fecha i {
            margin-right: 8px;
            color: var(--warning-color);
        }
        
        .btn-tarea {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            margin-top: 15px;
            transition: var(--transition);
        }
        
        .btn-tarea:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Botón para crear nuevo grupo */
        .btn-crear {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            margin: 20px 0;
            background: var(--secondary-color);
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }
        
        .btn-crear i {
            margin-right: 10px;
        }
        
        .btn-crear:hover {
            background: var(--success-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        /* Formulario para crear grupo */
        .form-container {
            background: white;
            padding: 25px;
            margin-top: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: none;
            animation: slideDown 0.3s ease forwards;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container h4 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-size: 18px;
            position: relative;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .form-container h4 i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .form-container h4:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 3px;
            background: var(--secondary-color);
            bottom: 0;
            left: 0;
            border-radius: 10px;
        }
        
        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-container input {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .form-container input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.2);
            outline: none;
        }
        
        .form-container button {
            margin-top: 15px;
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container button i {
            margin-right: 8px;
        }
        
        .form-container button:hover {
            background: var(--success-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Estadísticas y visualización de datos */
        .stats-card {
            position: relative;
            padding-left: 20px;
        }
        
        .progress-info {
            padding: 15px;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 8px;
            background: rgba(52, 152, 219, 0.05);
            border-left: 3px solid var(--primary-color);
            margin-top: 15px;
        }
        
        /* Tabla de información de alumno */
        .alumno-info {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-top: 20px;
        }
        
        .alumno-info table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .alumno-info th {
            background: var(--light-color);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border: 1px solid #ddd;
        }
        
        .alumno-info td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        
        .alumno-info tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }
        
        /* Toast para notificaciones */
        #toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            background: var(--success-color);
            color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            min-width: 280px;
        }
        
        #toast.show {
            display: block;
            animation: fadeInRight 0.5s ease forwards;
        }
        
        #toast.error {
            background: var(--error-color);
        }
        
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Sección para los encabezados */
        .section-header {
            color: var(--dark-color);
            font-size: 22px;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
        }
        
        .section-header i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 24px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            
            .content {
                width: 100%;
            }
            
            .grupo-container, .tareas-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Clases de utilidad */
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
    <script>
        window.onload = function () {
            mostrarInicio();
        };
    </script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Profesor</span>
        </div>
        <button onclick="mostrarInicio()" id="btnInicio"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="mostrarGrupos()" id="btnGrupos"><i class="fas fa-users"></i> Mis Grupos</button>
        <button onclick="mostrarTareas()" id="btnTareas"><i class="fas fa-tasks"></i> Mis Tareas</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='index.php'" id="btnLogout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>
    
    <div class="content">
        <div class="header">
            <h3>Dashboard Educativo</h3>
            <div class="user-info">
                <div class="avatar">
                    <?php echo substr(htmlspecialchars($usuario['nombre']), 0, 1); ?>
                </div>
                <div class="name">
                    Mtro. <?php echo htmlspecialchars($usuario['nombre']); ?>
                </div>
            </div>
        </div>
        
        <div id="mainContent" class="fade-in">
            <!-- Contenido dinámico se cargará aquí -->
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div id="toast"></div>

    <script>
        // Variables globales para los botones del menú
        const btnInicio = document.getElementById('btnInicio');
        const btnGrupos = document.getElementById('btnGrupos');
        const btnTareas = document.getElementById('btnTareas');
        const btnLogout = document.getElementById('btnLogout');
        
        // Función para mostrar mensajes toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
            toast.className = 'show ' + type;
            
            setTimeout(function() {
                toast.className = toast.className.replace('show', '');
            }, 5000);
        }
        
        // Función para marcar activo el botón del menú
        function setActiveButton(button) {
            // Quitar clase activa de todos los botones
            [btnInicio, btnGrupos, btnTareas, btnLogout].forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Agregar clase activa al botón actual
            button.classList.add('active');
        }

        function mostrarInicio() {
            setActiveButton(btnInicio);
            let content = `
                <h3><i class="fas fa-chart-line"></i> Progreso de Clases</h3>
                <p class="mb-20">Bienvenido de vuelta. Aquí puedes ver el progreso de los alumnos en cada una de tus clases.</p>
                <div class="grupo-container">`;

            const clases = <?php echo json_encode($clases); ?>;
            const alumnosCompletos = <?php echo json_encode($alumnos_completos); ?>;
            const alumnosIncompletos = <?php echo json_encode($alumnos_incompletos); ?>;

            if (clases.length > 0) {
                clases.forEach((clase, index) => {
                    let completos = alumnosCompletos[clase.id] || 0;
                    let incompletos = alumnosIncompletos[clase.id] || 0;
                    let total = completos + incompletos;
                    let chartId = `grafica_${index}`;
                    let porcentaje = total > 0 ? Math.round((completos / total) * 100) : 0;
                    
                    content += `
                    <div class='grupo-item stats-card' style="cursor:pointer;" onclick="progresoAlumno(${clase.id})">
                        <h4 style="text-align: center; margin-bottom: 15px; color: var(--dark-color); font-size: 18px;">${clase.nombre}</h4>
                        <p style="text-align: center; margin-bottom: 10px; font-size: 14px; color: #666;">
                            <i class="fas fa-key" style="margin-right: 5px;"></i> Código: ${clase.codigo_clase}
                        </p>
                        <div style="display: flex; gap: 20px; align-items: center; margin-top: 20px; justify-content: center;">
                            <canvas id='${chartId}' width='150' height='150'></canvas>
                            <div>
                                <p style="font-size: 14px; margin-bottom: 8px;">
                                    <i class="fas fa-user-check" style="color: var(--success-color);"></i> 
                                    <strong>${completos}</strong> alumnos completos
                                </p>
                                <p style="font-size: 14px; margin-bottom: 8px;">
                                    <i class="fas fa-user-clock" style="color: var(--error-color);"></i> 
                                    <strong>${incompletos}</strong> alumnos incompletos
                                </p>
                                <p style="font-size: 14px; font-weight: 500;">
                                    <i class="fas fa-users" style="color: var(--primary-color);"></i> 
                                    <strong>${total}</strong> alumnos inscritos
                                </p>
                                <div class="progress-info" style="margin-top: 15px; background-color: rgba(46, 204, 113, 0.1); border-left-color: var(--secondary-color);">
                                    <strong>${porcentaje}%</strong> de alumnos han completado todas las tareas
                                </div>
                            </div>
                        </div>
                        <button class="btn-tarea" style="margin-top: 20px;" onclick="progresoAlumno(${clase.id})">
                            <i class="fas fa-chart-bar"></i> Ver Progreso Detallado
                        </button>
                    </div>`;
                });
            } else {
                content += `
                <div class="text-center" style="grid-column: 1/-1; padding: 30px;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <p>No tienes clases registradas aún.</p>
                    <button class='btn-crear mt-20' onclick='mostrarGrupos()'>
                        <i class="fas fa-plus-circle"></i> Crear tu primera clase
                    </button>
                </div>`;
            }

            content += `</div>`;
            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";

            // Generar las gráficas después de renderizar el contenido
            clases.forEach((clase, index) => {
                let completos = alumnosCompletos[clase.id] || 0;
                let incompletos = alumnosIncompletos[clase.id] || 0;
                
                let ctx = document.getElementById(`grafica_${index}`);
                if (ctx) {
                    ctx = ctx.getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Completos', 'Incompletos'],
                            datasets: [{
                                data: [completos, incompletos],
                                backgroundColor: ['#2ecc71', '#e74c3c'],
                                borderColor: ['#fff', '#fff'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        }

        function progresoAlumno(claseId) {
            setActiveButton(btnInicio);
            
            // Mostrar indicador de carga
            document.getElementById("mainContent").innerHTML = `
                <div class="text-center" style="padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color); margin-bottom: 20px;"></i>
                    <p>Cargando progreso de los alumnos...</p>
                </div>
            `;
            
            // Realizar una solicitud AJAX para obtener la información de los alumnos en esta clase
            fetch('obtener_progresoAlumno.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `clase_id=${claseId}`
            })
            .then(response => response.json())
            .then(data => {
                // Buscar información de la clase
                const clases = <?php echo json_encode($clases); ?>;
                const claseActual = clases.find(c => c.id == claseId);
                
                let content = `
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <h3><i class="fas fa-users"></i> Alumnos de ${claseActual ? claseActual.nombre : 'la clase'}</h3>
                        <button class="btn-crear" onclick="mostrarInicio()">
                            <i class="fas fa-arrow-left"></i> Volver a todas las clases
                        </button>
                    </div>
                    <p class="mb-20">Aquí puedes ver el progreso individual de cada alumno en esta clase. Haz clic en el nombre del alumno para ver más detalles.</p>
                `;
                
                if (data.alumnos && data.alumnos.length > 0) {
                    content += `<div class="grupo-container">`;
                    
                    // Iterar sobre los alumnos y crear una tarjeta para cada uno
                    data.alumnos.forEach((alumno) => {
                        let totalTareas = alumno.entregadas + alumno.noEntregadas;
                        let porcentaje = totalTareas > 0 ? Math.round((alumno.entregadas / totalTareas) * 100) : 0;
                        let chartId = `grafica_${alumno.id}`;
                        
                        content += `
                            <div class="grupo-item" onclick="infoAlumno(${alumno.id}, ${claseId})">
                                <h4 style="text-align: center; margin-bottom: 15px; color: var(--dark-color); font-size: 18px; cursor: pointer;">
                                    <i class="fas fa-user-graduate"></i> ${alumno.nombre}
                                </h4>
                                <div style="display: flex; flex-direction: column; align-items: center; margin: 15px 0;">
                                    <canvas id='${chartId}' width='150' height='150'></canvas>
                                    <div class="progress-info" style="width: 100%; margin-top: 15px; text-align: center;">
                                        <strong>${porcentaje}%</strong> completado
                                        <p style="margin-top: 5px; font-size: 13px;">
                                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i> 
                                            ${alumno.entregadas} de ${totalTareas} tareas entregadas
                                        </p>
                                    </div>
                                </div>
                                <button class="btn-tarea" onclick="infoAlumno(${alumno.id}, ${claseId})">
                                    <i class="fas fa-info-circle"></i> Ver Detalles
                                </button>
                            </div>
                        `;
                    });
                    
                    content += `</div>`;
                } else {
                    content += `
                        <div class="text-center" style="padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                            <i class="fas fa-user-slash" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                            <p>No hay alumnos inscritos en esta clase.</p>
                            <p style="margin-top: 10px; color: #666;">Comparte el código de clase <strong>${claseActual ? claseActual.codigo_clase : ''}</strong> con tus alumnos para que puedan inscribirse.</p>
                        </div>
                    `;
                }
                
                document.getElementById("mainContent").innerHTML = content;
                document.getElementById("mainContent").className = "fade-in";
                
                // Generar gráficos de pastel para cada alumno
                if (data.alumnos && data.alumnos.length > 0) {
                    data.alumnos.forEach((alumno) => {
                        let ctx = document.getElementById(`grafica_${alumno.id}`);
                        if (ctx) {
                            ctx = ctx.getContext('2d');
                            new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: ['Entregadas', 'Pendientes'],
                                    datasets: [{
                                        data: [alumno.entregadas, alumno.noEntregadas],
                                        backgroundColor: ['#2ecc71', '#e74c3c'],
                                        borderColor: ['#fff', '#fff'],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                font: {
                                                    size: 12
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error al obtener los datos del progreso:', error);
                document.getElementById("mainContent").innerHTML = `
                    <div class="text-center" style="padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--error-color); margin-bottom: 15px;"></i>
                        <p>Ha ocurrido un error al cargar la información del progreso.</p>
                        <button class="btn-crear mt-20" onclick="mostrarInicio()">
                            <i class="fas fa-arrow-left"></i> Volver al inicio
                        </button>
                    </div>
                `;
            });
        }

        function infoAlumno(alumnoId, claseId) {
            setActiveButton(btnInicio);
            
            // Mostrar indicador de carga
            document.getElementById("mainContent").innerHTML = `
                <div class="text-center" style="padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: var(--primary-color); margin-bottom: 20px;"></i>
                    <p>Cargando información del alumno...</p>
                </div>
            `;

            fetch('obtener_infoAlumno.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `alumno_id=${alumnoId}&clase_id=${claseId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    // Mostrar mensaje de error
                    document.getElementById("mainContent").innerHTML = `
                        <div class="text-center" style="padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--error-color); margin-bottom: 15px;"></i>
                            <p>${data.error}</p>
                            <button class="btn-crear mt-20" onclick="progresoAlumno(${claseId})">
                                <i class="fas fa-arrow-left"></i> Volver a la lista de alumnos
                            </button>
                        </div>
                    `;
                } else {
                    // Crear el contenido con un estilo mejorado
                    let content = `
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <h3><i class="fas fa-user-graduate"></i> Detalle del Alumno</h3>
                            <button class="btn-crear" onclick="progresoAlumno(${claseId})">
                                <i class="fas fa-arrow-left"></i> Volver a la lista de alumnos
                            </button>
                        </div>
                        
                        <div class="alumno-info">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <h2 style="color: var(--dark-color); font-size: 24px; margin-bottom: 5px;">${data.nombre}</h2>
                                    <p style="color: var(--primary-color);"><i class="fas fa-envelope"></i> ${data.correo}</p>
                                </div>
                                <div style="background: var(--light-color); padding: 15px; border-radius: var(--radius);">
                                    <p><strong>Tareas Entregadas:</strong> ${data.tareas_entregadas} de ${data.tareas_entregadas + data.tareas_pendientes}</p>
                                    <div style="margin-top: 10px; height: 10px; background: #eee; border-radius: 5px; overflow: hidden;">
                                        <div style="height: 100%; width: ${data.tareas_entregadas/(data.tareas_entregadas + data.tareas_pendientes) * 100}%; background: var(--success-color);"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-header">
                                <i class="fas fa-check-circle"></i> Tareas Entregadas
                            </div>
                            
                            ${data.tareas_entregadas_detalle.length === 0 ? 
                                `<p class="text-center" style="padding: 20px; background: rgba(52, 152, 219, 0.05); border-radius: var(--radius);">No hay tareas entregadas.</p>` 
                                : 
                                `<div style="overflow-x: auto;">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-book"></i> Título</th>
                                                <th><i class="fas fa-comment"></i> Comentarios</th>
                                                <th><i class="fas fa-star"></i> Calificación</th>
                                                <th><i class="fas fa-calendar-alt"></i> Fecha de Entrega</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.tareas_entregadas_detalle.map(tarea => `
                                                <tr>
                                                    <td>${tarea.titulo}</td>
                                                    <td>${tarea.comentarios || '<em style="color: #999;">Sin comentarios</em>'}</td>
                                                    <td>
                                                        ${tarea.calificacion === "No calificada" ? 
                                                            `<a href="calificar_tarea.php?id_tarea=${tarea.id_tarea}&id_alumno=${alumnoId}" class="btn-tarea" style="margin: 0; padding: 8px 12px; font-size: 14px;">
                                                                <i class="fas fa-edit"></i> Calificar
                                                            </a>` 
                                                            : 
                                                            `<span style="background: var(--success-color); color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold;">${tarea.calificacion}</span>`
                                                        }
                                                    </td>
                                                    <td>${tarea.fecha_entrega}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>`
                            }
                            
                            <div class="section-header" style="margin-top: 30px;">
                                <i class="fas fa-exclamation-circle"></i> Tareas Pendientes
                            </div>
                            
                            ${data.tareas_no_entregadas.length === 0 ? 
                                `<p class="text-center" style="padding: 20px; background: rgba(46, 204, 113, 0.05); border-radius: var(--radius);">¡No hay tareas pendientes! El alumno ha entregado todas las tareas.</p>` 
                                : 
                                `<div style="overflow-x: auto;">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-book"></i> Título</th>
                                                <th><i class="fas fa-exclamation-triangle"></i> Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.tareas_no_entregadas.map(tarea => `
                                                <tr>
                                                    <td>${tarea.titulo}</td>
                                                    <td><span style="color: var(--error-color);"><i class="fas fa-times-circle"></i> No entregada</span></td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>`
                            }
                        </div>
                    `;

                    document.getElementById("mainContent").innerHTML = content;
                    document.getElementById("mainContent").className = "fade-in";
                }
            })
            .catch(error => {
                console.error('Error al obtener los datos del alumno:', error);
                document.getElementById("mainContent").innerHTML = `
                    <div class="text-center" style="padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--error-color); margin-bottom: 15px;"></i>
                        <p>Ha ocurrido un error al cargar la información del alumno.</p>
                        <button class="btn-crear mt-20" onclick="progresoAlumno(${claseId})">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de alumnos
                        </button>
                    </div>
                `;
            });
        }

        function mostrarGrupos() {
            setActiveButton(btnGrupos);
            let content = `
                <h3><i class="fas fa-users"></i> Mis Grupos</h3>
                <p class="mb-20">Administra tus clases, crea nuevos grupos y gestiona a tus alumnos.</p>
                
                <button class="btn-crear" onclick="mostrarFormularioGrupo()">
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Grupo
                </button>
                
                <div id="formNuevoGrupo" class="form-container" style="display:none;">
                    <h4><i class="fas fa-plus-circle"></i> Crear Nuevo Grupo</h4>
                    <p style="margin-bottom: 15px; color: #666;">
                        Completa la información para crear un nuevo grupo. Se generará automáticamente un código único que podrás compartir con tus alumnos.
                    </p>
                    <form method="POST">
                        <label for="nombre_clase">Nombre del Grupo:</label>
                        <input type="text" name="nombre_clase" id="nombre_clase" placeholder="Ej: Matemáticas Avanzadas" required>
                        <button type="submit">
                            <i class="fas fa-check-circle"></i> Crear Grupo
                        </button>
                    </form>
                </div>
                
                <div class="grupo-container">`;

            <?php if (count($clases) > 0): ?>
                <?php foreach ($clases as $clase): ?>
                    content += `
                        <div class="grupo-item">
                            <a href="ver_clase.php?id=<?php echo $clase['id']; ?>">
                                <i class="fas fa-users" style="margin-right: 10px;"></i>
                                <?php echo htmlspecialchars($clase['nombre']); ?>
                            </a>
                            <div style="text-align: center; margin-top: 10px; padding: 10px; background: rgba(52, 152, 219, 0.05); border-radius: 8px;">
                                <p style="font-size: 14px; color: #666; margin-bottom: 5px;">Código de clase:</p>
                                <p style="font-size: 16px; font-weight: bold; color: var(--primary-color);"><?php echo htmlspecialchars($clase['codigo_clase']); ?></p>
                            </div>
                            <button class="btn-tarea" onclick="window.location.href='ver_clase.php?id=<?php echo $clase['id']; ?>'">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                        </div>`;
                <?php endforeach; ?>
            <?php else: ?>
                content += `
                    <div class="text-center" style="grid-column: 1/-1; padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <p>No tienes clases registradas.</p>
                        <p style="margin-top: 10px; color: #666;">Crea tu primera clase utilizando el botón "Crear Nuevo Grupo".</p>
                    </div>`;
            <?php endif; ?>

            content += `</div>`;
            
            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";
        }
        
        function mostrarFormularioGrupo() {
            document.getElementById('formNuevoGrupo').style.display = 'block';
            // Animar suavemente hasta el formulario
            document.getElementById('formNuevoGrupo').scrollIntoView({ behavior: 'smooth' });
            // Enfocar en el campo de entrada
            document.getElementById('nombre_clase').focus();
        }

        function mostrarTareas() {
            setActiveButton(btnTareas);
            
            let content = `
                <h3><i class="fas fa-tasks"></i> Mis Tareas</h3>
                <p class="mb-20">Administra todas las tareas que has asignado a tus alumnos.</p>`;
                
            // Agrupar tareas por clase para una mejor organización
            const tareas = <?php echo json_encode($tareas); ?>;
            const clases = {};
            
            tareas.forEach(tarea => {
                if (!clases[tarea.clase_nombre]) {
                    clases[tarea.clase_nombre] = [];
                }
                clases[tarea.clase_nombre].push(tarea);
            });
            
            if (Object.keys(clases).length > 0) {
                Object.keys(clases).forEach(clase => {
                    content += `
                        <div class="section-header">
                            <i class="fas fa-book"></i> ${clase}
                        </div>
                        <div class="tareas-container">`;
                    
                    clases[clase].forEach(tarea => {
                        // Formatear fecha
                        const fecha = new Date(tarea.fecha_entrega);
                        const opciones = { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        };
                        const fechaFormateada = fecha.toLocaleDateString('es-MX', opciones);
                        
                        content += `
                            <div class="tarea-card">
                                <div class="tarea-titulo">${tarea.titulo}</div>
                                <div class="tarea-descripcion">${tarea.descripcion}</div>
                                <div class="tarea-clase">
                                    <i class="fas fa-book"></i> Clase: ${tarea.clase_nombre}
                                </div>
                                <div class="tarea-fecha">
                                    <i class="fas fa-calendar-alt"></i> Fecha límite: ${fechaFormateada}
                                </div>
                                <button class="btn-tarea" onclick="window.location.href='ver_tarea.php?id=${tarea.id}'">
                                    <i class="fas fa-eye"></i> Ver Entregas
                                </button>
                            </div>`;
                    });
                    
                    content += `</div>`;
                });
            } else {
                content += `
                    <div class="text-center" style="padding: 30px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);">
                        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <p>No hay tareas asignadas.</p>
                        <p style="margin-top: 10px; color: #666;">Crea tareas para tus alumnos desde la página de detalles de una clase.</p>
                    </div>`;
            }
            
            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";
        }

        // Mostrar mensajes emergentes si hay errores o éxitos
        <?php if (isset($_SESSION['success'])): ?>
            showToast("<?php echo $_SESSION['success']; ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            showToast("<?php echo $_SESSION['error']; ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>