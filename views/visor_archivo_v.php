<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visor de archivo - <?= e($archivo) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #334155; padding: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / .1); max-width: 1300px; margin: auto; overflow: hidden; }
        .card-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px; }
        .card-header h1 { font-size: 1rem; color: #0f172a; font-weight: 600; }
        .card-header small { display: block; color: #94a3b8; font-size: .78rem; margin-top: 2px; }
        .btn-print { background: #0ea5e9; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: .875rem; }
        .btn-print:hover { background: #0284c7; }
        .table-wrap { overflow-x: auto; padding: 0 4px 20px; }
        table { border-collapse: collapse; width: 100%; font-size: .82rem; min-width: 400px; }
        thead tr { background: #f8fafc; }
        th { padding: 10px 12px; text-align: left; font-weight: 600; border: 1px solid #e2e8f0; color: #475569; white-space: nowrap; }
        td { padding: 9px 12px; border: 1px solid #e2e8f0; color: #334155; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr:hover td { background: #e0f2fe; }
        .empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty i { font-size: 2.5rem; display: block; margin-bottom: 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: .72rem; font-weight: 600; }
        .badge-csv  { background: #dcfce7; color: #166534; }
        .badge-xlsx { background: #dbeafe; color: #1e40af; }
        @media print {
            body { background: white; padding: 0; }
            .btn-print, .card-header small { display: none; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div>
                <h1>
                    <?= e($archivo) ?>
                    <span class="badge badge-<?= e(strtolower(pathinfo($archivo, PATHINFO_EXTENSION))) ?>">
                        <?= strtoupper(pathinfo($archivo, PATHINFO_EXTENSION)) ?>
                    </span>
                </h1>
                <small><?= !empty($filas) ? (count($filas) - 1) . ' registro(s) encontrado(s)' : 'Sin registros' ?></small>
            </div>
            <button onclick="window.print()" class="btn-print">Imprimir Reporte</button>
        </div>

        <div class="table-wrap">
            <?php if (!empty($filas)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($filas[0] as $encabezado): ?>
                            <th><?= e((string) $encabezado) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i < count($filas); $i++):
                        $fila = $filas[$i];
                        // Omitir filas completamente vacías
                        if (count(array_filter(array_map('strval', $fila))) === 0) continue;
                    ?>
                    <tr>
                        <?php foreach ($fila as $celda): ?>
                            <td><?= e((string) $celda) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i>📄</i>
                No se pudo leer el contenido del archivo, o el archivo está vacío.<br>
                <small style="margin-top:8px;display:block;">Formatos soportados: CSV, XLSX</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
