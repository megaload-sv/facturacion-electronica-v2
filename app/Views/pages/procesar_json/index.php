<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Procesar JSON
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Procesar JSON</h1>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Cargar y procesar archivos JSON</h3></div>
    <div class="card-body">
        <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>

        <form action="<?= base_url('procesar-json/procesar') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="group_name">Nombre del grupo</label>
                <input type="text" class="form-control" name="group_name" id="group_name" required>
            </div>
            <div class="form-group">
                <label for="json_files">Archivos JSON</label>
                <input type="file" class="form-control" name="json_files[]" id="json_files" accept=".json" multiple required>
                <small class="form-text text-muted">Puedes seleccionar varios o arrastrar y soltar en el selector.</small>
            </div>
            <button type="submit" class="btn btn-primary">Procesar</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Últimos grupos procesados</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
            <tr><th>Grupo</th><th># Archivos</th><th>Carpeta</th><th>Excel/CSV</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?= esc($group['group_name']) ?></td>
                    <td><?= esc($group['processed_files_count']) ?></td>
                    <td><small><?= esc($group['folder_path']) ?></small></td>
                    <td><small><?= esc($group['excel_file_path']) ?></small></td>
                    <td>
                        <a href="<?= base_url('procesar-json/descargar-csv/' . $group['id']) ?>"
                           class="btn btn-sm btn-success">
                            Descargar CSV
                        </a>

                        <a href="<?= base_url('procesar-json/descargar-xls/' . $group['id']) ?>"
                           class="btn btn-sm btn-primary">
                            Descargar Excel
                        </a>
                        <form action="<?= base_url('procesar-json/eliminar/' . $group['id']) ?>" method="post" style="display:inline-block;" onsubmit="return confirm('¿Seguro que deseas eliminar este grupo y su carpeta?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
