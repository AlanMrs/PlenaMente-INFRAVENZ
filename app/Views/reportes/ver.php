<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/reporte-impresion.css">

<div style="max-width: 860px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;" class="no-print">
    <a href="<?php echo BASE_URL; ?>/reportes" class="btn btn-secondary">⬅ Volver a Informes</a>
    <a href="<?php echo BASE_URL; ?>/reportes/imprimir_reporte/<?php echo $reporte['id_reporte']; ?>" target="_blank" rel="noopener"
       class="btn btn-primary" style="background-color: #28a745; border: none; color: white; text-decoration: none; display: inline-flex; align-items: center;">
        📄 Descargar PDF
    </a>
</div>

<div style="max-width: 860px; margin: 0 auto;">
    <?php require __DIR__ . '/_documento.php'; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
