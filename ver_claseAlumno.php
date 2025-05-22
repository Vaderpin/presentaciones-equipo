<?php
session_start();
require 'db.php'; // Conexión a la base de datos

// Verificamos si se recibe un ID de clase
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Clase no especificada.";
    header("Location: alumno_dashboard.php");
    exit();
}

$id_clase = $_GET['id'];

// Obtener información de la clase
$stmt = $conn->prepare("SELECT nombre, codigo_clase FROM clases WHERE id = ?");
$stmt->bind_param("i", $id_clase);
$stmt->execute();
$result = $stmt->get_result();
$clase = $result->fetch_assoc();

// Si la clase no existe, redirigir
if (!$clase) {
    $_SESSION['error'] = "La clase no existe.";
    header("Location: alumno_dashboard.php");
    exit();
}

// Obtener tareas de la clase
$stmt = $conn->prepare("SELECT t.id, t.titulo, t.descripcion, t.fecha_entrega, 
                        (SELECT COUNT(*) FROM entregas e WHERE e.id_tarea = t.id AND e.id_alumno = ?) AS entregada
                        FROM tareas t WHERE t.id_clase = ?");
$stmt->bind_param("ii", $_SESSION['id'], $id_clase);
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Contar tareas entregadas y pendientes
$tareas_entregadas = 0;
$tareas_pendientes = 0;

foreach ($tareas as $tarea) {
    if ($tarea['entregada'] > 0) {
        $tareas_entregadas++;
    } else {
        $tareas_pendientes++;
    }
}

$total_tareas = count($tareas);
$porcentaje_completado = ($total_tareas > 0) ? round(($tareas_entregadas / $total_tareas) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clase['nombre']); ?> - EducaMente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .header h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .header .clase-codigo {
            background: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .header .clase-codigo i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        /* Contenido principal */
        .main-content {
            padding: 30px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        /* Navegación */
        .navigation-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn-nav {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            background: white;
            color: var(--dark-color);
            border: 1px solid #eee;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .btn-nav:hover {
            background: var(--light-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-nav i {
            margin-right: 8px;
        }
        
        /* Tarjeta de progreso */
        .progress-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        .progress-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .progress-header i {
            font-size: 24px;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .progress-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .progress-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            padding: 15px;
            background: var(--light-color);
            border-radius: 8px;
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .stat-entregadas .stat-value {
            color: var(--success-color);
        }
        
        .stat-pendientes .stat-value {
            color: var(--warning-color);
        }
        
        .progress-bar-container {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--primary-color);
            width: <?php echo $porcentaje_completado; ?>%;
            transition: width 1s ease;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }
        
        .progress-percentage {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        /* Sección de tareas */
        .section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header i {
            font-size: 24px;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .section-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Tareas grid */
        .tareas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .tarea-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
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
        
        .tarea-card.entregada:after {
            background: var(--success-color);
        }
        
        .tarea-card.pendiente:after {
            background: var(--warning-color);
        }
        
        .tarea-estado {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
        }
        
        .tarea-estado.entregada {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .tarea-estado.pendiente {
            background: rgba(230, 126, 34, 0.1);
            color: var(--warning-color);
        }
        
        .tarea-estado i {
            margin-right: 4px;
            font-size: 11px;
        }
        
        .tarea-titulo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
            padding-right: 70px; /* Espacio para el estado */
        }
        
        .tarea-descripcion {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
            display: flex;
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
            justify-content: center;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-tarea i {
            margin-right: 8px;
        }
        
        .btn-tarea:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-tarea.entregada {
            background: var(--success-color);
        }
        
        .btn-tarea.entregada:hover {
            background: #219653;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 30px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: var(--radius);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 10px;
        }
        
        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            
            .progress-stats {
                flex-direction: column;
            }
            
            .tareas-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Alumno</span>
        </div>
        <button onclick="window.location.href='alumno_dashboard.php'"><i class="fas fa-arrow-left"></i> Volver al Dashboard</button>
        <button class="active"><i class="fas fa-book"></i> Ver Tareas</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='index.php'"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>
    
    <div class="content">
        <div class="header">
            <h3><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($clase['nombre']); ?></h3>
            <div class="clase-codigo">
                <i class="fas fa-key"></i> Código: <?php echo htmlspecialchars($clase['codigo_clase']); ?>
            </div>
        </div>

        <div class="main-content fade-in">
            <div class="navigation-buttons">
                <a href="alumno_dashboard.php" class="btn-nav">
                    <i class="fas fa-arrow-left"></i> Volver al dashboard
                </a>
            </div>

            <div class="progress-card">
                <div class="progress-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Tu Progreso en la Clase</h3>
                </div>
                <div class="progress-stats">
                    <div class="stat-card stat-entregadas">
                        <div class="stat-value"><?php echo $tareas_entregadas; ?></div>
                        <div class="stat-label">Tareas Entregadas</div>
                    </div>
                    <div class="stat-card stat-pendientes">
                        <div class="stat-value"><?php echo $tareas_pendientes; ?></div>
                        <div class="stat-label">Tareas Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $total_tareas; ?></div>
                        <div class="stat-label">Total de Tareas</div>
                    </div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="progress-info">
                    <span>Progreso general</span>
                    <span class="progress-percentage"><?php echo $porcentaje_completado; ?>% completado</span>
                </div>
            </div>

            <!-- Sección de Tareas -->
            <div class="section">
                <div class="section-header">
                    <i class="fas fa-tasks"></i>
                    <h3>Tareas de la Clase</h3>
                </div>
                
                <?php if (count($tareas) > 0): ?>
                    <div class="tareas-container">
                        <?php foreach ($tareas as $tarea): 
                            // Verificar si la tarea ya fue entregada
                            $entregada = $tarea['entregada'] > 0;
                            
                            // Verificar si la fecha de entrega ya pasó
                            $fecha_entrega = new DateTime($tarea['fecha_entrega']);
                            $hoy = new DateTime();
                            $vencida = $fecha_entrega < $hoy && !$entregada;
                            
                            // Formatear la fecha para mostrarla
                            $fecha_formateada = $fecha_entrega->format('d/m/Y h:i A');
                        ?>
                            <div class="tarea-card <?php echo $entregada ? 'entregada' : 'pendiente'; ?>">
                                <div class="tarea-estado <?php echo $entregada ? 'entregada' : 'pendiente'; ?>">
                                    <?php if ($entregada): ?>
                                        <i class="fas fa-check-circle"></i> Entregada
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i> Pendiente
                                    <?php endif; ?>
                                </div>
                                <div class="tarea-titulo"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                                <div class="tarea-descripcion"><?php echo htmlspecialchars($tarea['descripcion']); ?></div>
                                <div class="tarea-fecha" <?php echo $vencida ? 'style="color: var(--error-color);"' : ''; ?>>
                                    <i class="fas fa-calendar-alt" <?php echo $vencida ? 'style="color: var(--error-color);"' : ''; ?>></i> 
                                    <?php if ($vencida): ?>
                                        ¡Vencida! Fecha límite: <?php echo $fecha_formateada; ?>
                                    <?php else: ?>
                                        Fecha límite: <?php echo $fecha_formateada; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="ver_tareaAlumno.php?id=<?php echo $tarea['id']; ?>" class="btn-tarea <?php echo $entregada ? 'entregada' : ''; ?>">
                                    <?php if ($entregada): ?>
                                        <i class="fas fa-eye"></i> Ver mi entrega
                                    <?php else: ?>
                                        <i class="fas fa-upload"></i> Entregar tarea
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No hay tareas asignadas en esta clase.</p>
                        <p>Una vez que tu profesor asigne tareas, aparecerán aquí.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div id="toast"></div>

    <script>
        // Función para mostrar mensajes toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
            toast.className = 'show ' + type;
            
            setTimeout(function() {
                toast.className = toast.className.replace('show', '');
            }, 5000);
        }
        
        // Mostrar mensajes de éxito o error si existen
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['success'])): ?>
                showToast("<?php echo htmlspecialchars($_SESSION['success']); ?>", "success");
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                showToast("<?php echo htmlspecialchars($_SESSION['error']); ?>", "error");
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>