<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha de Refuerzo - <?= htmlspecialchars($area) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.75;
        }

        .page {
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(0,0,0,.08);
        }

        /* ── Encabezado ── */
        .doc-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .doc-header-left h1 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .doc-header-left h1 i { color: #38bdf8; margin-right: 10px; }
        .doc-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .doc-meta-chip {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 13px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .doc-meta-chip i { color: #38bdf8; font-size: 11px; }

        .doc-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn-print {
            background: #0d9488;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-print:hover { background: #0f766e; }

        /* ── Contenido ── */
        .doc-body {
            padding: 2.5rem;
        }

        .doc-body h1 {
            font-size: 1.5rem;
            color: #0d9488;
            border-bottom: 3px solid #ccfbf1;
            padding-bottom: 10px;
            margin-bottom: 1.25rem;
        }
        .doc-body h2 {
            font-size: 1.1rem;
            color: #0f172a;
            border-left: 5px solid #0d9488;
            padding-left: 12px;
            margin: 2rem 0 .75rem;
        }
        .doc-body h3 { font-size: 1rem; color: #334155; margin: 1.25rem 0 .5rem; }
        .doc-body p  { color: #475569; margin-bottom: .75rem; }
        .doc-body ul, .doc-body ol { padding-left: 1.4rem; color: #475569; margin-bottom: .75rem; }
        .doc-body li { margin-bottom: 6px; }
        .doc-body strong { color: #1e293b; }

        /* ── Footer ── */
        .doc-footer {
            margin-top: 2rem;
            padding: 1.25rem 2.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .doc-footer span i { margin-right: 4px; }

        /* ── Print ── */
        @media print {
            @page { margin: 1.5cm; }
            body { background: #fff; }
            .page { box-shadow: none; }
            .doc-header { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .doc-actions { display: none !important; }
            .doc-body h2 { border-left-color: #0d9488 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="doc-header">
        <div class="doc-header-left">
            <h1><i class="fas fa-book-open"></i>Material de Refuerzo Academico</h1>
            <div class="doc-meta">
                <span class="doc-meta-chip"><i class="fas fa-layer-group"></i><?= htmlspecialchars($area) ?></span>
                <span class="doc-meta-chip"><i class="fas fa-user-graduate"></i><?= htmlspecialchars($usuario['nombres'] . ' ' . ($usuario['apellidos'] ?? '')) ?></span>
                <span class="doc-meta-chip"><i class="fas fa-school"></i><?= htmlspecialchars($grado) ?></span>
                <span class="doc-meta-chip"><i class="fas fa-calendar"></i><?= date('d/m/Y') ?></span>
            </div>
        </div>
        <div class="doc-actions">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-file-pdf"></i>Imprimir / PDF
            </button>
        </div>
    </div>

    <div class="doc-body">
        <?= $contenido ?>
    </div>

    <div class="doc-footer">
        <span><i class="fas fa-school"></i>I.E. N 14008 "Leonor Cerna de Valdiviezo" — Piura 2026</span>
        <span><i class="fas fa-robot"></i>BI Educativo — Inteligencia Artificial para el Exito Escolar</span>
    </div>

</div>
</body>
</html>
