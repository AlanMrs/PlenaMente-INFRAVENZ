<?php
/**
 * Documento del informe tecnico psicologico.
 *
 * Partial reutilizable: lo consumen tanto la vista en pantalla (reportes/tecnico.php,
 * reportes/ver.php) como la version imprimible (reportes/impresion.php).
 *
 * Requiere la variable $documento ya normalizada por el controlador:
 *   titulo, subtitulo, fecha_emision, autor, expediente_n, reporte_n, urgencia,
 *   identificacion[], antecedentes[], resumen[], observaciones, intervencion,
 *   evolucion, sesiones[], citas[]
 */

$colorUrgencia = ['Bajo' => '#28a745', 'Medio' => '#ffc107', 'Alto' => '#dc3545'];
$colorUrgenciaTexto = ['Bajo' => '#ffffff', 'Medio' => '#333333', 'Alto' => '#ffffff'];
?>

<div class="documento">

    <!-- Encabezado institucional -->
    <header class="doc-encabezado">
        <div class="doc-encabezado-institucion">
            <strong>Instituto Nacional Profesor Francisco Ventura Zelaya</strong><br>
            <span>Departamento de Psicología</span>
        </div>
        <div class="doc-encabezado-sistema">
            Sistema Institucional de Atención Psicológica<br>
            <strong>PlenaMente — INFRAVENZ</strong>
        </div>
    </header>

    <h1 class="doc-titulo"><?php echo htmlspecialchars($documento['titulo']); ?></h1>
    <p class="doc-subtitulo"><?php echo htmlspecialchars($documento['subtitulo']); ?></p>

    <div class="doc-meta">
        <span><strong>Fecha de emisión:</strong> <?php echo htmlspecialchars($documento['fecha_emision']); ?></span>
        <span><strong>Elaborado por:</strong> <?php echo htmlspecialchars($documento['autor']); ?></span>
        <span><strong>Expediente N°:</strong> #<?php echo htmlspecialchars($documento['expediente_n']); ?></span>
        <?php if (!empty($documento['reporte_n'])): ?>
            <span><strong>Reporte N°:</strong> #<?php echo htmlspecialchars($documento['reporte_n']); ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($documento['urgencia'])): ?>
        <div class="doc-urgencia" style="background-color: <?php echo $colorUrgencia[$documento['urgencia']] ?? '#6c757d'; ?>; color: <?php echo $colorUrgenciaTexto[$documento['urgencia']] ?? '#ffffff'; ?>;">
            Nivel de urgencia: <strong><?php echo htmlspecialchars($documento['urgencia']); ?></strong>
        </div>
    <?php endif; ?>

    <!-- I. Datos de identificacion -->
    <h2 class="doc-seccion">I. Datos de identificación</h2>
    <table class="doc-tabla doc-tabla-datos">
        <tbody>
            <?php foreach ($documento['identificacion'] as $dato): ?>
                <tr>
                    <th><?php echo htmlspecialchars($dato[0]); ?></th>
                    <td><?php echo htmlspecialchars($dato[1]); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- II. Antecedentes clinicos -->
    <h2 class="doc-seccion">II. Antecedentes clínicos</h2>
    <table class="doc-tabla doc-tabla-datos">
        <tbody>
            <tr>
                <th>Antecedentes médicos</th>
                <td><?php echo nl2br(htmlspecialchars($documento['antecedentes']['medicos'])); ?></td>
            </tr>
            <tr>
                <th>Antecedentes familiares</th>
                <td><?php echo nl2br(htmlspecialchars($documento['antecedentes']['familiares'])); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- III. Resumen de atencion -->
    <h2 class="doc-seccion">III. Resumen de atención</h2>
    <table class="doc-tabla doc-tabla-datos">
        <tbody>
            <?php foreach ($documento['resumen'] as $dato): ?>
                <tr>
                    <th><?php echo htmlspecialchars($dato[0]); ?></th>
                    <td><?php echo htmlspecialchars($dato[1]); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- IV. Observaciones clinicas -->
    <h2 class="doc-seccion">IV. Observaciones clínicas</h2>
    <div class="doc-bloque">
        <?php if (!empty($documento['observaciones'])): ?>
            <?php echo nl2br(htmlspecialchars($documento['observaciones'])); ?>
        <?php else: ?>
            <span class="doc-vacio">No hay observaciones clínicas registradas.</span>
        <?php endif; ?>
    </div>

    <!-- V. Intervencion realizada -->
    <h2 class="doc-seccion">V. Intervención realizada</h2>
    <div class="doc-bloque">
        <?php if (!empty($documento['intervencion'])): ?>
            <?php echo nl2br(htmlspecialchars($documento['intervencion'])); ?>
        <?php else: ?>
            <span class="doc-vacio">No se registró intervención en la última sesión.</span>
        <?php endif; ?>
    </div>

    <!-- VI. Notas de evolucion -->
    <h2 class="doc-seccion">VI. Notas de evolución y recomendaciones</h2>
    <div class="doc-bloque">
        <?php if (!empty($documento['evolucion'])): ?>
            <?php echo nl2br(htmlspecialchars($documento['evolucion'])); ?>
        <?php else: ?>
            <span class="doc-vacio">No hay notas de evolución registradas.</span>
        <?php endif; ?>
    </div>

    <!-- VII. Historial de sesiones -->
    <?php if (!empty($documento['sesiones'])): ?>
        <h2 class="doc-seccion">VII. Historial de sesiones clínicas</h2>
        <table class="doc-tabla doc-tabla-historial">
            <thead>
                <tr>
                    <th style="width: 18%;">Fecha</th>
                    <th style="width: 18%;">Tipo de consulta</th>
                    <th>Observaciones generales</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documento['sesiones'] as $sesion): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($sesion['fecha_registro'])); ?></td>
                        <td><?php echo htmlspecialchars($sesion['tipo_consulta']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($sesion['observaciones_generales'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Firmas -->
    <div class="doc-firmas">
        <div class="doc-firma">
            <div class="doc-firma-linea"></div>
            <strong><?php echo htmlspecialchars($documento['autor']); ?></strong>
            <p>Psicólogo(a) responsable del informe</p>
        </div>
        <div class="doc-firma">
            <div class="doc-firma-linea"></div>
            <strong>Dirección del Departamento de Psicología</strong>
            <p>INFRAVENZ</p>
        </div>
    </div>

    <footer class="doc-pie">
        Documento de carácter confidencial y de uso exclusivo institucional.
        Generado por el sistema PlenaMente el <?php echo date('d/m/Y h:i A'); ?>.
    </footer>
</div>
