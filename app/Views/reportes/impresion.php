<?php
/**
 * Version imprimible del informe tecnico (exportacion a PDF).
 *
 * Documento autonomo: no carga el layout de la aplicacion para que la
 * impresion salga limpia. El navegador permite "Guardar como PDF".
 *
 * Variables esperadas: $documento, $modo ('tecnico'|'reporte'), $volver_url
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($documento['titulo']); ?> — <?php echo htmlspecialchars($documento['paciente_nombre'] ?? ''); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/reporte-impresion.css">
</head>
<body class="doc-standalone">

    <div class="doc-toolbar">
        <div class="doc-toolbar-info">
            <strong>Vista imprimible</strong> — en el diálogo de impresión seleccione
            <em>Destino: Guardar como PDF</em> para descargar el documento.
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="doc-btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
            <a href="<?php echo htmlspecialchars($volver_url); ?>" class="doc-btn doc-btn-volver">⬅ Volver al informe</a>
        </div>
    </div>

    <?php require __DIR__ . '/_documento.php'; ?>

    <script>
        // Se intenta la impresion automatica al abrir la vista (el navegador
        // puede bloquearla; en ese caso se usa el boton de la barra superior).
        window.addEventListener('load', function () {
            setTimeout(function () {
                try { window.print(); } catch (e) { /* el navegador lo bloquea */ }
            }, 400);
        });
    </script>
</body>
</html>
