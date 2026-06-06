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
        ?>

        <nav class="sidebar">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="menu-item <?php echo (strpos($uri, '/dashboard') !== false) ? 'active' : ''; ?>">📊 Dashboard</a> 
            <a href="<?php echo BASE_URL; ?>/paciente" class="menu-item <?php echo (strpos($uri, '/paciente') !== false) ? 'active' : ''; ?>">👥 Pacientes</a>
            <a href="<?php echo BASE_URL; ?>/cita" class="menu-item <?php echo (strpos($uri, '/cita') !== false) ? 'active' : ''; ?>">📅 Citas</a>
                      
            <a href="<?php echo BASE_URL; ?>/asistencia" class="menu-item <?php echo (strpos($uri, '/asistencia') !== false) ? 'active' : ''; ?>">📝 Asistencias</a>
            
            <a href="<?php echo BASE_URL; ?>/expediente" class="menu-item <?php echo (strpos($uri, '/expediente') !== false) ? 'active' : ''; ?>">📁 Expedientes</a>
            <a href="<?php echo BASE_URL; ?>/reportes" class="menu-item <?php echo (strpos($uri, '/reportes') !== false) ? 'active' : ''; ?>">📄 Informes</a> 
            <a href="<?php echo BASE_URL; ?>/usuario" class="menu-item <?php echo (strpos($uri, '/usuario') !== false) ? 'active' : ''; ?>">🔐 Usuarios</a>
            
            <a href="<?php echo BASE_URL; ?>/auth/logout" class="menu-item" style="color: #d32f2f; margin-top: 20px; border-top: 1px solid var(--gris-claro);">
                🚪 Cerrar Sesión
            </a>
        </nav>

        <main class="main-content">