<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plenamente - INFRAVENZ</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <header class="header">
        <h1>🧠 Sistema Psicológico INFRAVENZ</h1>
        <div>Usuario: <?php echo $_SESSION['usuario_nombre'] ?? 'Invitado'; ?></div>
    </header>

    <div class="container">
        <?php 
        // Detectar el módulo actual desde la URL (por ejemplo: /asistencia, /paciente, etc.)
        $uri = $_SERVER['REQUEST_URI'];
        
        
        // Aseguramos que la sesión esté iniciada para poder leer el rol
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Obtenemos el rol del usuario (Ajusta 'rol' al nombre real de tu variable de sesión)
        $id_rol = $_SESSION['id_rol'] ?? 0; 
        ?>

        <nav class="sidebar">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="menu-item">📊 Dashboard</a> 

            <?php if ($id_rol == 1): ?>
                <a href="<?php echo BASE_URL; ?>/paciente" class="menu-item">👥 Pacientes</a>
                <a href="<?php echo BASE_URL; ?>/cita" class="menu-item">📅 Citas</a>
                <a href="<?php echo BASE_URL; ?>/asistencia" class="menu-item">📝 Asistencias</a>
                <a href="<?php echo BASE_URL; ?>/expediente" class="menu-item">📁 Expedientes</a>
            <?php endif; ?>

            <?php if ($id_rol == 1 || $id_rol == 2): ?>
                <a href="<?php echo BASE_URL; ?>/reportes" class="menu-item">📄 Informes</a> 
            <?php endif; ?>

            <?php if ($id_rol == 3): ?>
                <a href="<?php echo BASE_URL; ?>/usuario" class="menu-item">🔐 Usuarios</a>
            <?php endif; ?>
                
            <a href="<?php echo BASE_URL; ?>/auth/logout" class="menu-item" style="color: #d32f2f; margin-top: 20px; border-top: 1px solid var(--gris-claro);">
                🚪 Cerrar Sesión
            </a>
        </nav>
        <main class="main-content">