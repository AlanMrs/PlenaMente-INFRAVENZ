<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/reporte-impresion.css">

<div style="max-width: 860px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;" class="no-print">
    <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-secondary">⬅ Volver a Informes</a>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <form action="<?php echo BASE_URL; ?>/reportes/guardar" method="POST" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="id_expediente" value="<?php echo $paciente['id_expediente']; ?>">
            <input type="hidden" name="observaciones_generales" value="<?php echo htmlspecialchars($ultima_sesion['observaciones_generales'] ?? '', ENT_QUOTES); ?>">
            <input type="hidden" name="intervencion_realizada" value="<?php echo htmlspecialchars($ultima_sesion['intervencion_realizada'] ?? '', ENT_QUOTES); ?>">
            <input type="hidden" name="notas_evolucion" value="<?php echo htmlspecialchars($ultima_sesion['notas_evolucion'] ?? '', ENT_QUOTES); ?>">
            <select name="nivel_urgencia" class="form-control" style="width: auto; padding: 8px;">
                <option value="Bajo">Nivel: Bajo</option>
                <option value="Medio">Nivel: Medio</option>
                <option value="Alto">Nivel: Alto</option>
            </select>
            <button type="submit" class="btn btn-primary" style="background-color: var(--violeta-principal); border: none;">💾 Guardar Informe</button>
        </form>
        <a href="<?php echo BASE_URL; ?>/reportes/imprimir/<?php echo $paciente['id_expediente']; ?>" target="_blank" rel="noopener"
           class="btn btn-primary" style="background-color: #28a745; border: none; color: white; text-decoration: none; display: inline-flex; align-items: center;">
            📄 Descargar PDF
        </a>
    </div>
</div>

<div style="max-width: 860px; margin: 0 auto;">
    <?php require __DIR__ . '/_documento.php'; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
