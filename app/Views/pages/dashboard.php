<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resumen general | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?>
<div class="ml-page-heading"><div><span class="ml-eyebrow">CENTRO DE FACTURACIÓN</span><h1>Resumen general</h1><p>Una vista clara del estado de tus facturas electrónicas.</p></div><a href="<?= base_url('facturas') ?>" class="btn btn-primary ml-primary-action"><i class="far fa-file-alt mr-2" aria-hidden="true"></i> Ir a facturas <i class="fas fa-arrow-right ml-3" aria-hidden="true"></i></a></div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$processed = max(0, (int) $facturasProcesadasOK);
$errors = max(0, (int) $facturasProcesadasError);
$pending = max(0, (int) $facturasProcesadasPendientes);
$needsAttention = $errors + $pending;
$metrics = [
    ['value' => $processed, 'label' => 'Facturas archivadas', 'description' => 'Documentos recibidos por Hacienda sin error.', 'icon' => 'fa-check', 'tone' => 'success', 'url' => 'procesadas-archivadas', 'action' => 'Ver archivo'],
    ['value' => $pending, 'label' => 'Pendientes de enviar', 'description' => 'Documentos por procesar y enviar.', 'icon' => 'fa-clock', 'tone' => 'pending', 'url' => 'facturas', 'action' => 'Revisar pendientes'],
    ['value' => $errors, 'label' => 'Facturas con error', 'description' => 'Revisa la respuesta de cada documento.', 'icon' => 'fa-exclamation-triangle', 'tone' => 'error', 'url' => 'facturas-procesadas', 'action' => 'Revisar documentos'],
];
?>
<div class="ml-section-heading"><h2>Estado de facturación</h2><span>Resumen acumulado</span></div>
<div class="ml-metrics">
<?php foreach ($metrics as $metric): ?>
    <article class="ml-metric ml-tone-<?= $metric['tone'] ?>"><div class="ml-metric-top"><h3><?= $metric['label'] ?></h3><span class="ml-metric-icon"><i class="fas <?= $metric['icon'] ?>" aria-hidden="true"></i></span></div><div class="ml-metric-value"><?= number_format($metric['value']) ?></div><p><?= $metric['description'] ?></p><a href="<?= base_url($metric['url']) ?>"><?= $metric['action'] ?><i class="fas fa-arrow-right" aria-hidden="true"></i></a></article>
<?php endforeach; ?>
</div>
<div class="ml-dashboard-grid">
    <section class="ml-panel"><div class="ml-panel-heading"><div><span class="ml-eyebrow">TU SIGUIENTE PASO</span><h2>Atención a documentos</h2></div><i class="fas fa-stream text-muted" aria-hidden="true"></i></div>
        <div class="ml-attention <?= $needsAttention ? 'has-pending' : '' ?>"><span class="ml-attention-icon"><i class="fas <?= $needsAttention ? 'fa-inbox' : 'fa-check' ?>" aria-hidden="true"></i></span><div><h3><?= $needsAttention ? 'Hay documentos por revisar' : 'Todo al día' ?></h3><p><?= $needsAttention ? 'Revisa los pendientes y los documentos con error para continuar con tu operación.' : 'No hay facturas pendientes de enviar ni documentos con error en este resumen.' ?></p></div></div>
        <a class="ml-task-row" href="<?= base_url('facturas') ?>"><span><i class="far fa-clock" aria-hidden="true"></i> Pendientes de enviar</span><span class="ml-task-count"><?= number_format($pending) ?></span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
        <a class="ml-task-row" href="<?= base_url('facturas-procesadas') ?>"><span><i class="far fa-file-alt" aria-hidden="true"></i> Documentos con error</span><span class="ml-task-count"><?= number_format($errors) ?></span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </section>
    <section class="ml-panel ml-shortcuts"><div class="ml-panel-heading"><div><span class="ml-eyebrow">A UN CLIC</span><h2>Accesos rápidos</h2></div></div>
        <a href="<?= base_url('procesadas-archivadas') ?>"><span class="ml-shortcut-icon"><i class="fas fa-archive" aria-hidden="true"></i></span><span><strong>Archivo de facturas</strong><small>Consulta tus documentos aceptados por Hacienda</small></span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        <a href="<?= base_url('reportes/declaraciones') ?>"><span class="ml-shortcut-icon"><i class="fas fa-chart-bar" aria-hidden="true"></i></span><span><strong>Reportes y declaraciones</strong><small>Accede a la información para tus reportes</small></span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        <div class="ml-shortcut-note"><i class="far fa-lightbulb" aria-hidden="true"></i><p>Consulta el detalle de cada factura para conocer su estado y las acciones disponibles.</p></div>
    </section>
</div>
<?= $this->endSection() ?>
