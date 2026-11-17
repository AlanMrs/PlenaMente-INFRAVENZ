<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
// Colores por rol para el badge
$colorRol = [1 => 'var(--azul-claro)', 2 => 'var(--verde-menta)', 3 => '#ffc107'];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Gestión de Usuarios</h2>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo BASE_URL; ?>/rol" class="btn btn-secondary" style="background-color: #6f42c1; color: white; border: none; text-decoration: none;">🔐 Roles y Permisos</a>
        <a href="<?php echo BASE_URL; ?>/usuario/crear" class="btn btn-primary">+ Nuevo Usuario</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $tipo => $mensajes): ?>
        <?php foreach ($mensajes as $mensaje): ?>
            <div style="padding: 12px; background-color: <?php echo $tipo == 'success' ? '#d4edda' : ($tipo == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $tipo == 'success' ? '#155724' : ($tipo == 'warning' ? '#856404' : '#721c24'); ?>; border-radius: 4px; margin-bottom: 16px; font-weight: bold;">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($usuarios as $user): ?>
            <tr>
                <td><?php echo $user['id_usuario']; ?></td>
                <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                <td>
                    <span class="badge" style="background-color: <?php echo $colorRol[(int)$user['id_rol']] ?? '#e0e0e0'; ?>; color: #333;">
                        <?php echo htmlspecialchars($user['nombre_rol']); ?>
                    </span>
                </td>
                <td>
                    <span class="badge" style="background-color: <?php echo $user['estado'] == 'Activo' ? 'var(--verde-menta)' : '#e0e0e0'; ?>; color: #333;">
                        <?php echo $user['estado']; ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo BASE_URL; ?>/usuario/editar/<?php echo $user['id_usuario']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">Editar</a>
                    
                    <?php if ((int)$user['id_usuario'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                        <a href="<?php echo BASE_URL; ?>/usuario/eliminar/<?php echo $user['id_usuario']; ?>" 
                           style="background-color: #dc3545; color: white; padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block; border-radius: 4px; margin-left: 5px;"
                           onclick="return confirm('¿Estás seguro de que deseas desactivar a este usuario? Ya no podrá acceder al sistema.');">
                            Desactivar
                        </a>
                    <?php else: ?>
                        <span style="font-size: 12px; color: #999; margin-left: 5px;">(tu cuenta)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
