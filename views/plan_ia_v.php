<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Mejora IA - <?= htmlspecialchars($nombreEstudiante) ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 40px; color: #1e293b; line-height: 1.6; max-width: 850px; margin: auto; background: #fff; }
        .header { border-bottom: 3px solid #0d9488; margin-bottom: 30px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        h1 { color: #0d9488; margin: 0; font-size: 24px; }
        .info-box { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px; }
        h2 { color: #111827; font-size: 18px; margin-top: 30px; border-left: 5px solid #0d9488; padding-left: 12px; }
        .plan-list { list-style: none; padding: 0; }
        .plan-list li { margin-bottom: 15px; padding: 12px; background: #f1f5f9; border-radius: 6px; }
        .btn-print { background: #0d9488; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; }
        @media print { .btn-print { display: none; } body { padding: 0; } .header { border-bottom-color: #000; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Plan de Intervención Académica (IA)</h1>
        <button onclick="window.print()" class="btn-print">🖨️ Imprimir / Guardar PDF</button>
    </div>
    <div class="info-box">
        <div class="info-grid">
            <div><strong>Estudiante:</strong> <?= htmlspecialchars($nombreEstudiante) ?></div>
            <div><strong>Riesgo:</strong> <?= htmlspecialchars($riesgo) ?></div>
            <div><strong>Área Crítica:</strong> <?= htmlspecialchars($areaCritica) ?></div>
            <div><strong>Grado/Sección:</strong> <?= htmlspecialchars($details['grado'] . ' ' . $details['seccion']) ?></div>
        </div>
    </div>
    <h2>Recomendaciones de Gemini AI</h2>
    <ul class="plan-list">
        <?php foreach ($acciones as $index => $accion): ?>
            <li><strong><?= $index + 1 ?>.</strong> <?= htmlspecialchars($accion) ?></li>
        <?php endforeach; ?>
    </ul>
    <footer style="margin-top: 50px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 20px;">
        I.E. N 14008 "Leonor Cerna de Valdiviezo" - Plataforma BI Educativo 2026
    </footer>
</body>
</html>
