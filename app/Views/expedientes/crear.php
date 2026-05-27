<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="color: var(--violeta-principal); margin: 0;">Apertura de Expediente Clínico</h2>
    <a href="<?php echo BASE_URL; ?>/paciente" class="btn btn-secondary">Cancelar</a>
</div>

<div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    
    <div style="background-color: #f4f6f9; padding: 15px; border-radius: 6px; margin-bottom: 25px; border-left: 4px solid var(--violeta-principal);">
        <h4 style="margin: 0 0 8px 0; color: #333;">Datos del Paciente</h4>
        <p style="margin: 0; font-size: 15px; color: #555;">
            <strong>Nombre:</strong> <?php echo htmlspecialchars(($paciente['nombres'] ?? '') . ' ' . ($paciente['apellidos'] ?? '')); ?> &nbsp;|&nbsp;
            <strong>NIE/DUI:</strong> <?php echo htmlspecialchars($paciente['nie_dui'] ?? ''); ?> &nbsp;|&nbsp;
            <strong>Tipo:</strong> <?php echo htmlspecialchars($paciente['tipo_paciente'] ?? ''); ?>
        </p>
    </div>

    <form action="<?php echo BASE_URL; ?>/expediente/guardar" method="POST">
        <input type="hidden" name="id_paciente" value="<?php echo $paciente['id_paciente'] ?? ''; ?>">

        <div style="margin-bottom: 20px; max-width: 250px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Fecha de Apertura:</label>
            <input type="date" name="fecha_apertura" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Antecedentes Familiares (Opcional):</label>
            <textarea name="antecedentes_familiares" rows="4" placeholder="Escriba antecedentes psicológicos, trastornos conocidos o patologías relevantes en la familia..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif; resize: vertical;"></textarea>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Antecedentes Médicos / Personales (Opcional):</label>
            <textarea name="antecedentes_medicos" rows="4" placeholder="Escriba diagnósticos médicos previos, medicamentos actuales, traumas u observaciones importantes..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif; resize: vertical;"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer;">Generar Expediente Clínico</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>