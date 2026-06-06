<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">📋 Control de Asistencias Diarias</h2>
    
    <div style="display: flex; align-items: center; gap: 10px; background: white; padding: 6px 12px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <label style="font-weight: bold; color: #555; font-size: 14px;">Fecha de Registro:</label>
        <input type="date" id="cambiar_fecha" value="<?php echo $fecha; ?>" max="<?php echo date('Y-m-d'); ?>" 
               style="border: 1px solid #ccc; padding: 6px; border-radius: 4px; font-family: inherit; font-size: 14px; cursor: pointer;">
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="padding: 12px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
        ✅ Listado de asistencia actualizado y guardado correctamente para el día <?php echo date('d/m/Y', strtotime($fecha)); ?>.
    </div>
<?php endif; ?>

<form action="<?php echo BASE_URL; ?>/asistencia/guardar" method="POST">
    <input type="hidden" name="fecha_asistencia" value="<?php echo $fecha; ?>">

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto;">
        
        <?php if (empty($pacientes)): ?>
            <div style="text-align: center; padding: 40px; color: #888;">
                <p style="margin: 0; font-size: 16px;">No se encontraron expedientes clínicos registrados en el sistema.</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; background: #fdfdfd; color: #333;">
                        <th style="padding: 12px 10px;">Código / DUI</th>
                        <th style="padding: 12px 10px;">Paciente</th>
                        <th style="padding: 12px 10px;">Tipo</th>
                        <th style="padding: 12px 10px; text-align: center;">Estado de Asistencia</th>
                        <th style="padding: 12px 10px; width: 30%;">Observación / Justificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacientes as $paciente): 
                        $id_exp = $paciente['id_expediente'];
                        $estado_actual = $paciente['estado'] ?? 'Presente'; // Por defecto marcado Presente
                    ?>
                        <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;" onmouseover="this.style.background='#fbfaff'" onmouseout="this.style.background='transparent'">
                            
                            <td style="padding: 14px 10px; font-weight: bold; color: #666;">
                                EXP-<?php echo str_pad($id_exp, 5, "0", STR_PAD_LEFT); ?><br>
                                <span style="font-size: 11px; color: #999; font-weight: normal;"><?php echo htmlspecialchars($paciente['nie_dui']); ?></span>
                            </td>
                            
                            <td style="padding: 14px 10px; font-weight: bold; color: #333;">
                                <?php echo htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombres']); ?>
                            </td>
                            
                            <td style="padding: 14px 10px;">
                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: <?php echo $paciente['tipo_paciente'] == 'Estudiante' ? '#e3f2fd; color: #0d47a1;' : '#e8f5e9; color: #1b5e20;'; ?>">
                                    <?php echo $paciente['tipo_paciente']; ?>
                                </span>
                            </td>
                            
                            <td style="padding: 14px 10px; text-align: center;">
                                <div style="display: inline-flex; gap: 4px; background: #f5f5f5; padding: 4px; border-radius: 20px; border: 1px solid #e0e0e0;">
                                    
                                    <label style="cursor: pointer; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 4px; transition: 0.2s;" class="radio-btn">
                                        <input type="radio" name="asistencias[<?php echo $id_exp; ?>][estado]" value="Presente" <?php echo $estado_actual == 'Presente' ? 'checked' : ''; ?> style="margin:0;"> 🟢 P
                                    </label>
                                    
                                    <label style="cursor: pointer; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 4px; transition: 0.2s;" class="radio-btn">
                                        <input type="radio" name="asistencias[<?php echo $id_exp; ?>][estado]" value="Ausente" <?php echo $estado_actual == 'Ausente' ? 'checked' : ''; ?> style="margin:0;"> 🔴 A
                                    </label>
                                    
                                    <label style="cursor: pointer; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 4px; transition: 0.2s;" class="radio-btn">
                                        <input type="radio" name="asistencias[<?php echo $id_exp; ?>][estado]" value="Tarde" <?php echo $estado_actual == 'Tarde' ? 'checked' : ''; ?> style="margin:0;"> 🟡 T
                                    </label>
                                    
                                    <label style="cursor: pointer; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 4px; transition: 0.2s;" class="radio-btn">
                                        <input type="radio" name="asistencias[<?php echo $id_exp; ?>][estado]" value="Justificado" <?php echo $estado_actual == 'Justificado' ? 'checked' : ''; ?> style="margin:0;"> 🔵 J
                                    </label>
                                    
                                </div>
                            </td>
                            
                            <td style="padding: 14px 10px;">
                                <input type="text" name="asistencias[<?php echo $id_exp; ?>][observaciones]" 
                                       value="<?php echo htmlspecialchars($paciente['observaciones'] ?? ''); ?>" 
                                       placeholder="Ej. Justificación médica, retraso del bus..." 
                                       style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 13px; box-sizing: border-box;">
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 24px; text-align: right;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 15px; background-color: var(--violeta-principal); border: none; border-radius: 4px; color: white; cursor: pointer; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    💾 Guardar Cambios de Asistencia
                </button>
            </div>
        <?php endif; ?>
        
    </div>
</form>

<script>
// Escucha el cambio de fecha y recarga la URL enviando el parámetro correcto
document.getElementById('cambiar_fecha').addEventListener('change', function() {
    window.location.href = "<?php echo BASE_URL; ?>/asistencia?fecha=" + this.value;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>