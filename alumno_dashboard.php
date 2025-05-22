<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'alumno') {
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

// Obtener información del alumno
$correo = $_SESSION['usuario'];
$sql = "SELECT id, nombre FROM usuarios WHERE correo = ? AND tipo = 'alumno'";
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

// Obtener las clases en las que está inscrito el alumno
$sqlClases = "SELECT c.id, c.nombre, c.codigo_clase FROM alumnos_clases ac 
              JOIN clases c ON ac.id_clase = c.id WHERE ac.id_alumno = ?";
$stmtClases = $conn->prepare($sqlClases);
$stmtClases->bind_param("i", $usuario['id']);
$stmtClases->execute();
$resultClases = $stmtClases->get_result();
$clases = $resultClases->fetch_all(MYSQLI_ASSOC);

// Obtener tareas asignadas al alumno
$sqlTareas = "SELECT 
                t.id, 
                t.titulo, 
                t.descripcion, 
                t.fecha_entrega, 
                c.nombre AS clase_nombre,
                (SELECT COUNT(*) FROM entregas e WHERE e.id_tarea = t.id AND e.id_alumno = ?) AS entregada
              FROM tareas t 
              JOIN clases c ON t.id_clase = c.id 
              JOIN alumnos_clases ac ON ac.id_clase = c.id 
              WHERE ac.id_alumno = ?";

$stmtTareas = $conn->prepare($sqlTareas);
$stmtTareas->bind_param("ii", $usuario['id'], $usuario['id']);
$stmtTareas->execute();
$resultTareas = $stmtTareas->get_result();
$tareas = $resultTareas->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<script>
    const tareas = <?php echo json_encode($tareas); ?>;
</script>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumno - EducaMente</title>
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
        
        /* Tarjetas de clases */
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
        
        /* Botón para inscribirse */
        .btn-crear {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            margin: 20px 0;
            background: var(--primary-color);
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
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        /* Formulario de inscripción */
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
        }
        
        .form-container h4:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            bottom: 0;
            left: 0;
            border-radius: 10px;
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
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .form-container button {
            margin-top: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .form-container button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Separador */
        hr {
            margin: 40px 0;
            border: none;
            height: 1px;
            background: rgba(0,0,0,0.1);
        }
        
        /* Notificaciones toast */
        #toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            background: #333;
            color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
        }
        
        #toast.show {
            display: block;
            animation: fadeInRight 0.5s ease forwards;
        }
        
        #toast.error {
            background: var(--error-color);
        }
        
        #toast.success {
            background: var(--success-color);
        }
        
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Estilos específicos para gráficas */
        .stats-card {
            position: relative;
            padding-left: 20px; /* Espacio para el indicador de color */
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
        
        /* Estilos para las secciones */
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
        // Mostrar estadísticas directamente al cargar el dashboard
        window.onload = function () {
            mostrarInicio();
        };
    </script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Alumno</span>
        </div>
        <button onclick="mostrarInicio()" id="btnInicio"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="mostrarGrupos()" id="btnGrupos"><i class="fas fa-users"></i> Mis Clases</button>
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
                    <?php echo htmlspecialchars($usuario['nombre']); ?>
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
            toast.textContent = message;
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
                <h3><i class="fas fa-chart-line"></i> Resumen de Progreso</h3>
                <p class="mb-20">Bienvenido de vuelta. Aquí puedes ver tu progreso en cada una de tus clases.</p>
                <div class="grupo-container">`;

            const clases = <?php echo json_encode($clases); ?>;
            const tareas = <?php echo json_encode($tareas); ?>;

            clases.forEach((clase, index) => {
                let total = 0;
                let entregadas = 0;
                tareas.forEach(t => {
                    if (t.clase_nombre === clase.nombre) {
                        total++;
                        if (t.entregada > 0) entregadas++;
                    }
                });

                let pendientes = total - entregadas;
                let chartId = `grafica_${index}`;
                let porcentaje = total > 0 ? Math.round((entregadas / total) * 100) : 0;
                
                content += `
                <div class='grupo-item stats-card' style="cursor:pointer;" onclick="window.location.href='ver_claseAlumno.php?id=${clase.id}'">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <canvas id='${chartId}' width='150' height='150'></canvas>
                        <div style="flex: 1;">
                            <h4 style="margin-bottom: 10px; color: var(--dark-color);">${clase.nombre}</h4>
                            <p style="font-size: 14px; margin-bottom: 8px;">
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i> 
                                <strong>${entregadas}</strong> de <strong>${total}</strong> tareas entregadas
                            </p>
                            <p style="font-size: 14px; margin-bottom: 15px;">
                                <i class="fas fa-percentage" style="color: var(--primary-color);"></i> 
                                Progreso: <strong>${porcentaje}%</strong>
                            </p>
                            <div class="progress-info" id="opinion_${index}">
                                <em><i class="fas fa-spinner fa-spin"></i> Consultando opinión de la IA...</em>
                            </div>
                        </div>
                    </div>
                </div>`;
            });

            content += `</div>`;
            
            if (clases.length === 0) {
                content += `
                <div class="text-center mt-20">
                    <p>Aún no estás inscrito en ninguna clase.</p>
                    <button class='btn-crear' onclick='mostrarGrupos()'>
                        <i class="fas fa-plus-circle"></i> Inscribirme a una clase
                    </button>
                </div>`;
            }
            
            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";

            // Generar las gráficas después de renderizar el contenido
            clases.forEach((clase, index) => {
                let total = 0;
                let entregadas = 0;
                tareas.forEach(t => {
                    if (t.clase_nombre === clase.nombre) {
                        total++;
                        if (t.entregada > 0) entregadas++;
                    }
                });
                let pendientes = total - entregadas;
                let ctx = document.getElementById(`grafica_${index}`).getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Entregadas', 'Pendientes'],
                        datasets: [{
                            data: [entregadas, pendientes],
                            backgroundColor: ['#2ecc71', '#e74c3c']
                        }]
                    },
                    options: {
                        responsive: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });

                // Consultar opinión de la IA
                fetch('consulta_de_opinionIA.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `subject=${encodeURIComponent(clase.nombre)}&completed=${entregadas}&missing=${pendientes}`
                })
                .then(res => res.text())
                .then(data => {
                    document.getElementById(`opinion_${index}`).innerHTML = data;
                })
                .catch(err => {
                    document.getElementById(`opinion_${index}`).innerHTML = `
                        <span style='color:var(--error-color);'>
                            <i class="fas fa-exclamation-circle"></i> Error al obtener opinión de IA.
                        </span>`;
                });
            });
        }

        function mostrarGrupos() {
            setActiveButton(btnGrupos);
            let content = `
                <h3><i class="fas fa-users"></i> Mis Clases</h3>
                <p class="mb-20">Estas son las clases en las que estás inscrito actualmente.</p>
                
                <button class='btn-crear' onclick='mostrarFormularioInscripcion()'>
                    <i class="fas fa-plus-circle"></i> Inscribirme a nueva clase
                </button>
                
                <div class='grupo-container'>`;

            <?php if (count($clases) > 0): ?>
                <?php foreach ($clases as $clase): ?>
                    content += `
                        <div class='grupo-item'>
                            <a href='ver_claseAlumno.php?id=<?php echo $clase['id']; ?>'>
                                <i class="fas fa-book-open" style="margin-right: 10px;"></i>
                                <?php echo htmlspecialchars($clase['nombre']); ?>
                            </a>
                            <p style="text-align: center; margin-top: 10px; font-size: 14px; color: #666;">
                                <i class="fas fa-key" style="margin-right: 5px;"></i>
                                Código: <?php echo htmlspecialchars($clase['codigo_clase']); ?>
                            </p>
                        </div>`;
                <?php endforeach; ?>
            <?php else: ?>
                content += `
                    <div class="text-center" style="grid-column: 1/-1; padding: 30px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <p>No estás inscrito en ninguna clase.</p>
                        <p style="margin-top: 10px;">Utiliza el botón "Inscribirme a nueva clase" para comenzar.</p>
                    </div>`;
            <?php endif; ?>
            
            content += `</div>`;

            // Agregar formulario oculto con estilo mejorado
            content += `
                <div class='form-container' id='formInscripcion'>
                    <h4><i class="fas fa-plus-circle"></i> Inscribirme a una nueva clase</h4>
                    <p style="margin-bottom: 15px; color: #666;">
                        Ingresa el código de la clase que te proporcionó tu profesor.
                    </p>
                    <div style="position: relative;">
                        <input type='text' id='codigoClase' placeholder='Ingresa el código de clase'>
                    </div>
                    <button onclick='verificarClase()'>
                        <i class="fas fa-search"></i> Verificar código
                    </button>
                    <div id='infoClase' style="margin-top: 15px;"></div>
                </div>`;

            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";
        }

        function mostrarTareas() {
            setActiveButton(btnTareas);
            const tareas = <?php echo json_encode($tareas); ?>;
            
            // Contar tareas pendientes y entregadas
            const pendientes = tareas.filter(t => t.entregada == 0).length;
            const entregadas = tareas.filter(t => t.entregada > 0).length;
            
            let content = `
                <h3><i class="fas fa-tasks"></i> Mis Tareas</h3>
                <p class="mb-20">Administra todas tus tareas en un solo lugar. Tienes ${pendientes} tareas pendientes y ${entregadas} entregadas.</p>
                
                <div class="section-header">
                    <i class="fas fa-hourglass-half"></i> Tareas Pendientes
                </div>
                <div class='tareas-container'>`;

            // Tareas pendientes
            const tareasPendientes = tareas.filter(t => t.entregada == 0);
            
            if (tareasPendientes.length > 0) {
                tareasPendientes.forEach(t => {
                    content += generarCardTarea(t);
                });
            } else {
                content += `
                    <div class="text-center" style="grid-column: 1/-1; padding: 30px;">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 15px;"></i>
                        <p>¡No tienes tareas pendientes!</p>
                        <p style="margin-top: 10px;">Mantén el buen trabajo.</p>
                    </div>`;
            }
            
            content += `
                </div>
                
                <div class="section-header">
                    <i class="fas fa-check-circle"></i> Tareas Entregadas
                </div>
                <div class='tareas-container'>`;

            // Tareas entregadas
            const tareasEntregadas = tareas.filter(t => t.entregada > 0);
            
            if (tareasEntregadas.length > 0) {
                tareasEntregadas.forEach(t => {
                    content += generarCardTarea(t);
                });
            } else {
                content += `
                    <div class="text-center" style="grid-column: 1/-1; padding: 30px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <p>Aún no has entregado ninguna tarea.</p>
                    </div>`;
            }
            
            content += `</div>`;
            
            document.getElementById("mainContent").innerHTML = content;
            document.getElementById("mainContent").className = "fade-in";
        }

        function generarCardTarea(t) {
            // Formatear la fecha
            const fecha = new Date(t.fecha_entrega);
            const formattedDate = fecha.toLocaleDateString('es-MX', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Determinar si la tarea está pendiente o entregada
            const isEntregada = t.entregada > 0;
            const statusIcon = isEntregada 
                ? `<i class="fas fa-check-circle" style="color: var(--success-color);"></i> Entregada` 
                : `<i class="fas fa-clock" style="color: var(--warning-color);"></i> Pendiente`;
            
            // Determinar si la fecha está próxima (menos de 2 días)
            const hoy = new Date();
            const diasRestantes = Math.ceil((fecha - hoy) / (1000 * 60 * 60 * 24));
            let fechaEstilo = '';
            let fechaTexto = formattedDate;
            
            if (!isEntregada) {
                if (diasRestantes < 0) {
                    fechaEstilo = 'color: var(--error-color); font-weight: bold;';
                    fechaTexto = `<i class="fas fa-exclamation-circle"></i> ¡Vencida! ${formattedDate}`;
                } else if (diasRestantes <= 2) {
                    fechaEstilo = 'color: var(--warning-color); font-weight: bold;';
                    fechaTexto = `<i class="fas fa-exclamation-triangle"></i> ¡Próxima a vencer! ${formattedDate}`;
                }
            }
            
            return `
                <div class='tarea-card' style="${isEntregada ? 'border-left: 5px solid var(--success-color);' : ''}">
                    <div class='tarea-titulo'>${t.titulo}</div>
                    <div class='tarea-descripcion'>${t.descripcion}</div>
                    <div class='tarea-clase'>
                        <i class="fas fa-book"></i> Clase: ${t.clase_nombre}
                    </div>
                    <div class='tarea-fecha' style="${fechaEstilo}">
                        <i class="fas fa-calendar-alt"></i> ${fechaTexto}
                    </div>
                    <div style="margin: 10px 0; font-size: 14px;">${statusIcon}</div>
                    <button class='btn-tarea' onclick="window.location.href='ver_tareaAlumno.php?id=${t.id}'">
                        ${isEntregada ? '<i class="fas fa-eye"></i> Ver Entrega' : '<i class="fas fa-upload"></i> Entregar Tarea'}
                    </button>
                </div>`;
        }

        function mostrarFormularioInscripcion() {
            document.getElementById('formInscripcion').style.display = 'block';
            // Animar suavemente hasta el formulario
            document.getElementById('formInscripcion').scrollIntoView({ behavior: 'smooth' });
            // Enfocar en el campo de entrada
            document.getElementById('codigoClase').focus();
        }

        function verificarClase() {
            let codigo = document.getElementById('codigoClase').value;
            if (codigo === '') {
                showToast('Por favor, ingresa un código de clase.', 'error');
                return;
            }

            const infoClase = document.getElementById('infoClase');
            infoClase.innerHTML = `<div style="text-align: center; padding: 15px;"><i class="fas fa-spinner fa-spin"></i> Verificando código...</div>`;

            fetch('verificar_clase.php?codigo=' + codigo)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        infoClase.innerHTML = `
                            <div style="background: var(--light-color); border-radius: 8px; padding: 15px; animation: fadeIn 0.5s ease;">
                                <h4 style="color: var(--dark-color); margin-bottom: 10px;">Información de la clase:</h4>
                                <p><strong>Nombre:</strong> ${data.nombre}</p>
                                <p><strong>Profesor:</strong> ${data.maestro}</p>
                                <button onclick="inscribirseClase('${codigo}')" style="margin-top: 15px; background: var(--success-color);">
                                    <i class="fas fa-check-circle"></i> Confirmar inscripción
                                </button>
                            </div>`;
                    } else {
                        infoClase.innerHTML = `
                            <div style="background: rgba(231, 76, 60, 0.1); border-radius: 8px; padding: 15px; border-left: 4px solid var(--error-color); animation: fadeIn 0.5s ease;">
                                <p style="color: var(--error-color);">
                                    <i class="fas fa-times-circle"></i> ${data.message}
                                </p>
                            </div>`;
                    }
                })
                .catch(error => {
                    infoClase.innerHTML = `
                        <div style="background: rgba(231, 76, 60, 0.1); border-radius: 8px; padding: 15px; border-left: 4px solid var(--error-color); animation: fadeIn 0.5s ease;">
                            <p style="color: var(--error-color);">
                                <i class="fas fa-exclamation-triangle"></i> Error de conexión. Inténtalo nuevamente.
                            </p>
                        </div>`;
                    console.error("Error en la solicitud:", error);
                });
        }

        function inscribirseClase(codigo) {
            let idAlumno = '<?php echo $usuario["id"]; ?>'; // Tomar ID del alumno desde PHP
            const infoClase = document.getElementById('infoClase');
            
            infoClase.innerHTML = `<div style="text-align: center; padding: 15px;"><i class="fas fa-spinner fa-spin"></i> Procesando inscripción...</div>`;

            fetch('inscribirse_clase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'codigo=' + encodeURIComponent(codigo) + '&id_alumno=' + encodeURIComponent(idAlumno)
            })
            .then(response => response.text())
            .then(text => {
                console.log("Respuesta del servidor:", text); // Depuración
                try {
                    return JSON.parse(text); // Convertir a JSON manualmente
                } catch (e) {
                    throw new Error('Respuesta no válida del servidor');
                }
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        location.reload(); // Recargar para actualizar los datos
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                    infoClase.innerHTML = `
                        <div style="background: rgba(231, 76, 60, 0.1); border-radius: 8px; padding: 15px; border-left: 4px solid var(--error-color); animation: fadeIn 0.5s ease;">
                            <p style="color: var(--error-color);">
                                <i class="fas fa-times-circle"></i> ${data.message}
                            </p>
                        </div>`;
                }
            })
            .catch(error => {
                showToast('Error al procesar la inscripción', 'error');
                infoClase.innerHTML = `
                    <div style="background: rgba(231, 76, 60, 0.1); border-radius: 8px; padding: 15px; border-left: 4px solid var(--error-color); animation: fadeIn 0.5s ease;">
                        <p style="color: var(--error-color);">
                            <i class="fas fa-exclamation-triangle"></i> Error de conexión. Inténtalo nuevamente.
                        </p>
                    </div>`;
                console.error("Error en la solicitud:", error);
            });
        }
    </script>
</body>
</html>