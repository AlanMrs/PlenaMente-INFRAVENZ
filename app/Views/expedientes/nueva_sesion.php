<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: var(--violeta-principal); margin: 0;">Nueva Sesión Clínica</h2>
        <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $expediente['id_expediente']; ?>" class="btn btn-secondary">Cancelar</a>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <p style="margin-bottom: 20px; color: #666;">
            Registrando sesión para: <strong><?php echo htmlspecialchars($expediente['nombres'] . ' ' . $expediente['apellidos']); ?></strong>
        </p>

        <form action="<?php echo BASE_URL; ?>/expediente/guardar_sesion" method="POST">
            <input type="hidden" name="id_expediente" value="<?php echo $expediente['id_expediente']; ?>">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Observaciones Generales:</label>
                <textarea name="observaciones_generales" rows="3" required placeholder="Describa el estado inicial del paciente en esta sesión..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Intervención Realizada:</label>
                <textarea name="intervencion_realizada" rows="3" required placeholder="Técnicas aplicadas o actividades desarrolladas..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"></textarea>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Notas de Evolución:</label>
                <textarea name="notas_evolucion" rows="4" required placeholder="Progreso detectado y pautas para la siguiente sesión..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; background-color: var(--violeta-principal); border: none;">
                Guardar Sesión Clínica
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>