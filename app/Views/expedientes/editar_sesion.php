<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: var(--violeta-principal); margin: 0;">✏️ Editar Sesión Clínica</h2>
        <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $sesion['id_expediente']; ?>" class="btn btn-secondary">Cancelar</a>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <p style="margin-bottom: 20px; color: #666;">
            Editando sesión de: <strong><?php echo htmlspecialchars($sesion['nombres'] . ' ' . $sesion['apellidos']); ?></strong>
            <span style="display: inline-block; margin-left: 10px; padding: 2px 10px; border-radius: 12px; font-size: 12px; background: #e3f2fd; color: #0d47a1;">
                Registrada el <?php echo date('d/m/Y h:i A', strtotime($sesion['fecha_registro'])); ?>
            </span>
        </p>

        <div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 20px; font-size: 13px;">
            💡 Puede agregar información faltante. Los valores anteriores de cada campo se conservan en el historial de cambios.
        </div>

        <form action="<?php echo BASE_URL; ?>/expediente/actualizar_sesion" method="POST">
            <input type="hidden" name="id_sesion" value="<?php echo $sesion['id_sesion']; ?>">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Clasificación de la Consulta:</label>
                <select name="tipo_consulta" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background-color: #f9f9f9;">
                    <option value="">-- Seleccione el tipo de atención --</option>
                    <?php
                        $tipos = [
                            'Evaluación Inicial' => '📋 Evaluación Inicial / Primera Vez',
                            'Seguimiento' => '🔄 Seguimiento Psicológico',
                            'Intervención en Crisis' => '🚨 Intervención en Crisis',
                            'Orientación' => '🗣️ Orientación / Consejería',
                            'Terapia Individual' => '🛋️ Terapia Individual',
                            'Derivación' => '➡️ Derivación a otra especialidad'
                        ];
                        foreach ($tipos as $valor => $etiqueta):
                    ?>
                        <option value="<?php echo $valor; ?>" <?php echo ($sesion['tipo_consulta'] === $valor) ? 'selected' : ''; ?>><?php echo $etiqueta; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Observaciones Generales:</label>
                <textarea name="observaciones_generales" rows="3" required placeholder="Describa el estado inicial del paciente en esta sesión..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"><?php echo htmlspecialchars($sesion['observaciones_generales']); ?></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Intervención Realizada:</label>
                <textarea name="intervencion_realizada" rows="3" required placeholder="Técnicas aplicadas o actividades desarrolladas..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"><?php echo htmlspecialchars($sesion['intervencion_realizada']); ?></textarea>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Notas de Evolución:</label>
                <textarea name="notas_evolucion" rows="4" required placeholder="Progreso detectado y pautas para la siguiente sesión..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"><?php echo htmlspecialchars($sesion['notas_evolucion']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; background-color: var(--violeta-principal); border: none;">
                💾 Guardar Cambios
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>