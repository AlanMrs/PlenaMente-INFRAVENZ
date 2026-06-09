<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php 
// LÓGICA DE ROLES Y CONTADORES
$id_rol = $_SESSION['id_rol'] ?? 0; 
$total_presentes = 0; $total_ausentes = 0; $total_tardes = 0; $total_justificados = 0;

if (!empty($asistencias)) {
    foreach ($asistencias as $a) {
        if ($a['estado'] == 'Presente') $total_presentes++;
        if ($a['estado'] == 'Ausente') $total_ausentes++;
        if ($a['estado'] == 'Tarde') $total_tardes++;
        if ($a['estado'] == 'Justificado') $total_justificados++;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">📋 Bitácora de Asistencias</h2>
    
    <div style="display: flex; align-items: center; gap: 10px; background: white; padding: 6px 12px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <label style="font-weight: bold; color: #555; font-size: 14px;">Fecha Consultada:</label>
        <input type="date" id="cambiar_fecha" value="<?php echo htmlspecialchars($fecha); ?>" max="<?php echo date('Y-m-d'); ?>" 
               style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-family: inherit; font-size: 14px; cursor: pointer;">
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="padding: 12px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
        ✅ Registro de asistencia guardado exitosamente.
    </div>
<?php endif; ?>

<div style="display: flex; gap: 15px; margin-bottom: 24px;">
    <div style="flex: 1; background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #1b5e20;">
        <div style="font-size: 12px; color: #555; font-weight: bold; text-transform: uppercase;">Presentes (P)</div>
        <div style="font-size: 24px; font-weight: bold; color: #1b5e20; margin-top: 5px;"><?php echo $total_presentes; ?></div>
    </div>
    <div style="flex: 1; background: #ffebee; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #b71c1c;">
        <div style="font-size: 12px; color: #555; font-weight: bold; text-transform: uppercase;">Ausentes (A)</div>
        <div style="font-size: 24px; font-weight: bold; color: #b71c1c; margin-top: 5px;"><?php echo $total_ausentes; ?></div>
    </div>
    <div style="flex: 1; background: #fff8e1; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #f57f17;">
        <div style="font-size: 12px; color: #555; font-weight: bold; text-transform: uppercase;">Tardes (T)</div>
        <div style="font-size: 24px; font-weight: bold; color: #f57f17; margin-top: 5px;"><?php echo $total_tardes; ?></div>
    </div>
    <div style="flex: 1; background: #f3e5f5; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #4a148c;">
        <div style="font-size: 12px; color: #555; font-weight: bold; text-transform: uppercase;">Justificados (J)</div>
        <div style="font-size: 24px; font-weight: bold; color: #4a148c; margin-top: 5px;"><?php echo $total_justificados; ?></div>
    </div>
</div>

<?php if ($id_rol == 1): ?>
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 24px; border-top: 4px solid var(--violeta-principal);">
    <h4 style="margin-top: 0; color: #333; margin-bottom: 15px;">➕ Añadir Registro a la Bitácora de Hoy</h4>
    
    <form action="<?php echo BASE_URL; ?>/asistencia/guardar" method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="fecha_asistencia" value="<?php echo htmlspecialchars($fecha); ?>">
        
        <div style="flex: 2; min-width: 250px;">
            <label style="font-size: 13px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Seleccionar Paciente *</label>
            <select name="id_expediente" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                <option value="">-- Buscar paciente... --</option>
                <?php foreach($expedientes as $exp): ?>
                    <option value="<?php echo $exp['id_expediente']; ?>">
                        EXP-<?php echo str_pad($exp['id_expediente'], 4, '0', STR_PAD_LEFT); ?> | <?php echo htmlspecialchars($exp['apellidos'] . ', ' . $exp['nombres']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 150px;">
            <label style="font-size: 13px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Estado *</label>
            <select name="estado" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                <option value="Presente">🟢 Presente</option>
                <option value="Ausente">🔴 Ausente</option>
                <option value="Tarde">🟡 Tarde</option>
                <option value="Justificado">🔵 Justificado</option>
            </select>
        </div>

        <div style="flex: 2; min-width: 200px;">
            <label style="font-size: 13px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Observaciones (Opcional)</label>
            <input type="text" name="observaciones" placeholder="Ej. Retraso del bus, dolor de cabeza..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-weight: bold; border: none; border-radius: 4px; background-color: var(--violeta-principal); color: white; cursor: pointer;">
                Guardar
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto;">
    <h4 style="margin-top: 0; color: #333; margin-bottom: 15px;">📜 Registros del día: <?php echo date('d/m/Y', strtotime($fecha)); ?></h4>
    
    <?php if (empty($asistencias)): ?>
        <div style="text-align: center; padding: 40px; color: #888;">
            <p style="margin: 0; font-size: 16px;">No se han registrado asistencias para esta fecha.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; background: #fdfdfd; color: #333;">
                    <th style="padding: 12px 10px;">Código</th>
                    <th style="padding: 12px 10px;">Paciente</th>
                    <th style="padding: 12px 10px;">Tipo</th>
                    <th style="padding: 12px 10px; text-align: center;">Estado</th>
                    <th style="padding: 12px 10px;">Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asistencias as $registro): ?>
                    <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;" onmouseover="this.style.background='#fbfaff'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 14px 10px; font-weight: bold; color: #666;">
                            EXP-<?php echo str_pad($registro['id_expediente'], 5, "0", STR_PAD_LEFT); ?>
                        </td>
                        <td style="padding: 14px 10px; font-weight: bold; color: #333;">
                            <?php echo htmlspecialchars($registro['apellidos'] . ', ' . $registro['nombres']); ?>
                        </td>
                        <td style="padding: 14px 10px;">
                            <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: <?php echo $registro['tipo_paciente'] == 'Estudiante' ? '#e3f2fd; color: #0d47a1;' : '#e8f5e9; color: #1b5e20;'; ?>">
                                <?php echo htmlspecialchars($registro['tipo_paciente']); ?>
                            </span>
                        </td>
                        <td style="padding: 14px 10px; text-align: center;">
                            <?php 
                                $color = ''; $icono = '';
                                if($registro['estado'] == 'Presente') { $color = '#28a745'; $icono = '🟢'; }
                                elseif($registro['estado'] == 'Ausente') { $color = '#dc3545'; $icono = '🔴'; }
                                elseif($registro['estado'] == 'Tarde') { $color = '#ffc107'; $icono = '🟡'; }
                                else { $color = '#17a2b8'; $icono = '🔵'; }
                            ?>
                            <span style="background-color: <?php echo $color; ?>; color: white; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                <?php echo $icono . ' ' . $registro['estado']; ?>
                            </span>
                        </td>
                        <td style="padding: 14px 10px; color: #555;">
                            <?php echo empty($registro['observaciones']) ? '<span style="color:#aaa; font-style:italic;">Sin observaciones</span>' : htmlspecialchars($registro['observaciones']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
document.getElementById('cambiar_fecha').addEventListener('change', function() {
    window.location.href = "<?php echo BASE_URL; ?>/asistencia?fecha=" + this.value;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>