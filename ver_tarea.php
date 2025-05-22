<?php
session_start();
require 'db.php'; // Asegúrate de que este archivo conecta correctamente a la base de datos

// Verificamos si se recibe un ID de tarea
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Tarea no especificada.";
    header("Location: maestro_dashboard.php");
    exit();
}

$id_tarea = intval($_GET['id']); // Convertimos el ID a número entero

// Obtener información de la tarea
$stmt = $conn->prepare("SELECT t.titulo, t.descripcion, t.fecha_entrega, c.nombre AS nombre_clase, c.id AS id_clase 
                        FROM tareas t 
                        JOIN clases c ON t.id_clase = c.id 
                        WHERE t.id = ?");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$result = $stmt->get_result();
$tarea = $result->fetch_assoc();

if (!$tarea) {
    $_SESSION['error'] = "La tarea no existe.";
    header("Location: maestro_dashboard.php");
    exit();
}

// Obtener los alumnos inscritos en la clase
$stmt = $conn->prepare("SELECT u.id, u.nombre, u.correo 
                        FROM usuarios u 
                        JOIN alumnos_clases ac ON u.id = ac.id_alumno 
                        JOIN clases c ON ac.id_clase = c.id 
                        WHERE c.id = (SELECT id_clase FROM tareas WHERE id = ?)");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener entregas con calificaciones
$stmt = $conn->prepare("SELECT id_alumno, calificacion FROM entregas WHERE id_tarea = ?");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$res = $stmt->get_result();
$entregas_info = $res->fetch_all(MYSQLI_ASSOC);

// Mapear entregas
$entregados = [];
$calificaciones = [];
foreach ($entregas_info as $e) {
    $entregados[] = $e['id_alumno'];
    $calificaciones[$e['id_alumno']] = $e['calificacion'];
}

// Separar alumnos entregados y no entregados
$alumnos_entregados = [];
$alumnos_no_entregados = [];

foreach ($alumnos as $alumno) {
    if (in_array($alumno['id'], $entregados)) {
        $alumno['calificacion'] = $calificaciones[$alumno['id']] ?? null;
        $alumnos_entregados[] = $alumno;
    } else {
        $alumnos_no_entregados[] = $alumno;
    }
}

// Formatear fecha de entrega
$fecha_entrega = new DateTime($tarea['fecha_entrega']);
$fecha_formateada = $fecha_entrega->format('d/m/Y h:i A');

// Verificar si la fecha de entrega ya pasó
$hoy = new DateTime();
$es_pasada = $fecha_entrega < $hoy;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tarea['titulo']); ?> - EducaMente</title>
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
            margin-left: 0;
            position: relative;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            z-index: 5;
        }
        
        .header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .header .clase-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #666;
        }
        
        .header .clase-info i {
            color: var(--primary-color);
        }
        
        /* Contenido principal */
        .main-content {
            padding: 30px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        /* Secciones */
        .section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        .section h3 {
            color: var(--dark-color);
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .section h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .section p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .section strong {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        /* Info de tarea */
        .tarea-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tarea-info-item {
            flex: 1;
            background: var(--light-color);
            padding: 15px;
            border-radius: var(--radius);
            display: flex;
            flex-direction: column;
        }
        
        .tarea-info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .tarea-info-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .tarea-info-value i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .tarea-descripcion {
            background: rgba(52, 152, 219, 0.05);
            padding: 20px;
            border-radius: var(--radius);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 30px;
        }
        
        .tarea-descripcion h4 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .tarea-descripcion h4 i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .tarea-descripcion p {
            color: #555;
            line-height: 1.6;
        }
        
        /* Lista de alumnos */
        .list-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .list-item {
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            border: 1px solid #eee;
        }
        
        .list-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .alumno-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alumno-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }
        
        .alumno-detalles {
            flex-grow: 1;
        }
        
        .alumno-nombre {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .alumno-correo {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .alumno-correo i {
            margin-right: 5px;
            color: var(--primary-color);
            font-size: 12px;
        }
        
        .alumno-calificacion {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: var(--success-color);
            font-weight: 500;
        }
        
        .alumno-calificacion i {
            margin-right: 5px;
        }
        
        .alumno-entrega {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn-calificar {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-calificar:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-calificar i {
            margin-right: 5px;
        }
        
        .btn-cambiar {
            background: var(--light-color);
            color: var(--dark-color);
        }
        
        .btn-cambiar:hover {
            background: #ddd;
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
        
        /* Botones de navegación */
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
        
        /* Estado de la entrega */
        .entrega-estado {
            margin-top: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        
        .entrega-estado.on-time {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .entrega-estado.late {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
        }
        
        .entrega-estado i {
            margin-right: 4px;
            font-size: 11px;
        }
        
        /* Progress stats */
        .progress-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .stat-entregados .stat-icon {
            color: var(--success-color);
        }
        
        .stat-pendientes .stat-icon {
            color: var(--warning-color);
        }
        
        .stat-porcentaje .stat-icon {
            color: var(--primary-color);
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
            
            .tarea-info {
                flex-direction: column;
            }
            
            .progress-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Profesor</span>
        </div>
        <button onclick="window.location.href='maestro_dashboard.php'"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="window.location.href='ver_clase.php?id=<?php echo $tarea['id_clase']; ?>'"><i class="fas fa-chalkboard-teacher"></i> Volver a la Clase</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='index.php'"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>

    <div class="content">
        <div class="header">
            <h2><?php echo htmlspecialchars($tarea['titulo']); ?></h2>
            <div class="clase-info">
                <i class="fas fa-book"></i>
                <span>Clase: <?php echo htmlspecialchars($tarea['nombre_clase']); ?></span>
            </div>
        </div>

        <div class="main-content fade-in">
            <div class="navigation-buttons">
                <a href="ver_clase.php?id=<?php echo $tarea['id_clase']; ?>" class="btn-nav">
                    <i class="fas fa-arrow-left"></i> Volver a la clase
                </a>
            </div>

            <?php 
                $total_alumnos = count($alumnos);
                $entregados_count = count($alumnos_entregados);
                $pendientes_count = count($alumnos_no_entregados);
                $porcentaje = $total_alumnos > 0 ? round(($entregados_count / $total_alumnos) * 100) : 0;
            ?>

            <div class="progress-stats">
                <div class="stat-card stat-entregados">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $entregados_count; ?></div>
                    <div class="stat-label">Entregas Recibidas</div>
                </div>
                <div class="stat-card stat-pendientes">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $pendientes_count; ?></div>
                    <div class="stat-label">Entregas Pendientes</div>
                </div>
                <div class="stat-card stat-porcentaje">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-value"><?php echo $porcentaje; ?>%</div>
                    <div class="stat-label">Porcentaje de Entrega</div>
                </div>
            </div>

            <div class="section">
                <div class="tarea-descripcion">
                    <h4><i class="fas fa-file-alt"></i> Descripción de la tarea</h4>
                    <p><?php echo nl2br(htmlspecialchars($tarea['descripcion'])); ?></p>
                </div>

                <div class="tarea-info">
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Fecha de entrega</div>
                        <div class="tarea-info-value" style="<?php echo $es_pasada ? 'color: var(--error-color);' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo $fecha_formateada; ?>
                            <?php if ($es_pasada): ?>
                                <span style="margin-left: 5px; font-size: 12px;">(Vencida)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Estado</div>
                        <div class="tarea-info-value">
                            <?php if ($es_pasada): ?>
                                <i class="fas fa-clock" style="color: var(--error-color);"></i> Plazo finalizado
                            <?php else: ?>
                                <i class="fas fa-hourglass-half" style="color: var(--warning-color);"></i> Plazo vigente
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Participación</div>
                        <div class="tarea-info-value">
                            <i class="fas fa-users"></i> <?php echo $entregados_count; ?> de <?php echo $total_alumnos; ?> alumnos han entregado
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3><i class="fas fa-check-circle"></i> Alumnos que han entregado (<?php echo count($alumnos_entregados); ?>)</h3>
                
                <?php if (!empty($alumnos_entregados)): ?>
                    <div class="list-container">
                        <?php foreach ($alumnos_entregados as $alumno): ?>
                            <?php
                                // Eliminamos la verificación de fecha que usaba campos que no existen
                            ?>
                            <div class="list-item">
                                <div class="alumno-info">
                                    <div class="alumno-avatar">
                                        <?php echo substr(htmlspecialchars($alumno['nombre']), 0, 1); ?>
                                    </div>
                                    <div class="alumno-detalles">
                                        <div class="alumno-nombre"><?php echo htmlspecialchars($alumno['nombre']); ?></div>
                                        <div class="alumno-correo">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alumno['correo']); ?>
                                        </div>
                                        <?php if (!is_null($alumno['calificacion'])): ?>
                                            <div class="alumno-calificacion">
                                                <i class="fas fa-star"></i> Calificación: <?php echo $alumno['calificacion']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="calificar_tarea.php?id_tarea=<?php echo $id_tarea; ?>&id_alumno=<?php echo $alumno['id']; ?>" class="btn-calificar <?php echo !is_null($alumno['calificacion']) ? 'btn-cambiar' : ''; ?>">
                                    <?php if (!is_null($alumno['calificacion'])): ?>
                                        <i class="fas fa-edit"></i> Cambiar calificación
                                    <?php else: ?>
                                        <i class="fas fa-star"></i> Calificar
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Ningún alumno ha entregado esta tarea aún.</p>
                        <p>Cuando los alumnos entreguen sus tareas, aparecerán en esta sección.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3><i class="fas fa-clock"></i> Alumnos que no han entregado (<?php echo count($alumnos_no_entregados); ?>)</h3>
                
                <?php if (!empty($alumnos_no_entregados)): ?>
                    <div class="list-container">
                        <?php foreach ($alumnos_no_entregados as $alumno): ?>
                            <div class="list-item">
                                <div class="alumno-info">
                                    <div class="alumno-avatar" style="background: var(--warning-color);">
                                        <?php echo substr(htmlspecialchars($alumno['nombre']), 0, 1); ?>
                                    </div>
                                    <div class="alumno-detalles">
                                        <div class="alumno-nombre"><?php echo htmlspecialchars($alumno['nombre']); ?></div>
                                        <div class="alumno-correo">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alumno['correo']); ?>
                                        </div>
                                        <div class="alumno-entrega" style="color: var(--warning-color);">
                                            <i class="fas fa-exclamation-triangle"></i> Tarea pendiente de entrega
                                        </div>
                                    </div>
                                </div>
                                <?php if (function_exists('recordarEnviarTarea')): ?>
                                <a href="recordar_tarea.php?id_alumno=<?php echo $alumno['id']; ?>&id_tarea=<?php echo $id_tarea; ?>" class="btn-calificar" style="background-color: var(--warning-color);">
                                    <i class="fas fa-bell"></i> Recordar entrega
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="background: rgba(46, 204, 113, 0.05);">
                        <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                        <p style="color: var(--success-color); font-weight: 500;">¡Todos los alumnos han entregado la tarea!</p>
                        <p>Todos los estudiantes inscritos en este curso han completado esta tarea.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($alumnos_entregados)): ?>
            <div class="section">
                <h3><i class="fas fa-chart-bar"></i> Resumen de Calificaciones</h3>
                
                <?php
                    // Calcular estadísticas de calificaciones
                    $calificaciones_array = array_filter(array_column($alumnos_entregados, 'calificacion'), function($val) {
                        return !is_null($val);
                    });
                    
                    $calificados = count($calificaciones_array);
                    $pendientes_calificar = count($alumnos_entregados) - $calificados;
                    
                    $promedio = $calificados > 0 ? array_sum($calificaciones_array) / $calificados : 0;
                    $max = $calificados > 0 ? max($calificaciones_array) : 0;
                    $min = $calificados > 0 ? min($calificaciones_array) : 0;
                ?>
                
                <div class="tarea-info">
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Promedio de calificaciones</div>
                        <div class="tarea-info-value">
                            <i class="fas fa-calculator"></i> <?php echo number_format($promedio, 1); ?>
                        </div>
                    </div>
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Calificación más alta</div>
                        <div class="tarea-info-value">
                            <i class="fas fa-arrow-up"></i> <?php echo number_format($max, 1); ?>
                        </div>
                    </div>
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Calificación más baja</div>
                        <div class="tarea-info-value">
                            <i class="fas fa-arrow-down"></i> <?php echo number_format($min, 1); ?>
                        </div>
                    </div>
                    <div class="tarea-info-item">
                        <div class="tarea-info-label">Estado de calificación</div>
                        <div class="tarea-info-value">
                            <i class="fas fa-tasks"></i> <?php echo $calificados; ?> calificados, <?php echo $pendientes_calificar; ?> pendientes
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Función para mostrar mensajes toast si se implementa posteriormente
        function showToast(message, type = 'success') {
            // Implementación futura del sistema de notificaciones toast
            alert(message);
        }
        
        // Se puede añadir código adicional para mejorar la interactividad
        document.addEventListener('DOMContentLoaded', function() {
            // Código que se ejecutará cuando la página esté completamente cargada
            console.log('Página de tareas cargada completamente');
        });
    </script>
</body>
</html>