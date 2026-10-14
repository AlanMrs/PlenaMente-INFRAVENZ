<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: var(--violeta-principal); margin: 0;">✏️ Actualizar Datos Clínicos del Expediente</h2>
        <a href="<?php echo BASE_URL; ?>/expediente/ver/<?php echo $expediente['id_expediente']; ?>" class="btn btn-secondary">Cancelar</a>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <p style="margin-bottom: 20px; color: #666;">
            Actualizando expediente de: <strong><?php echo htmlspecialchars($expediente['nombres'] . ' ' . $expediente['apellidos']); ?></strong>
            <span style="display: inline-block; margin-left: 10px; padding: 2px 10px; border-radius: 12px; font-size: 12px; background: #e3f2fd; color: #0d47a1;">
                EXP-<?php echo str_pad($expediente['id_expediente'], 5, '0', STR_PAD_LEFT); ?>
            </span>
        </p>

        <div style="padding: 12px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 20px; font-size: 13px;">
            💡 Los valores anteriores se conservan automáticamente en el historial de cambios del expediente.
        </div>

        <form action="<?php echo BASE_URL; ?>/expediente/actualizar" method="POST">
            <input type="hidden" name="id_expediente" value="<?php echo $expediente['id_expediente']; ?>">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Antecedentes Familiares:</label>
                <textarea name="antecedentes_familiares" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;" placeholder="Historial de enfermedades o condición de la familia..."><?php echo htmlspecialchars($expediente['antecedentes_familiares'] ?? ''); ?></textarea>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Antecedentes Médicos / Personales:</label>
                <textarea name="antecedentes_medicos" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;" placeholder="Condiciones médicas, tratamientos, alergias..."><?php echo htmlspecialchars($expediente['antecedentes_medicos'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; background-color: var(--violeta-principal); border: none;">
                💾 Guardar Cambios
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>