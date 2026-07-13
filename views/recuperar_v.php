<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contrasena - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand-panel">
            <img src="<?= imagenAsset('logo-colegio') ?>" alt="Logo del colegio" class="school-logo">
            <p class="eyebrow">Institucion Educativa</p>
            <h1>BI Educativo Piura 2026</h1>
            <p>Recupera el acceso a tu cuenta ingresando tu correo registrado.</p>
            <div class="auth-highlights">
                <span>Seguro</span>
                <span>Rapido</span>
                <span>Simple</span>
            </div>
        </section>

        <section class="auth-card">
            <p class="eyebrow">Acceso al sistema</p>
            <h2>Recuperar contrasena</h2>
            <p>Ingresa tu correo y te enviaremos un enlace para restablecer tu contrasena.</p>

            <?php if (!empty($error)): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($ok)): ?>
                <div class="alert ok"><?= e($ok) ?></div>
            <?php endif; ?>

            <?php if (empty($ok)): ?>
            <form method="post" action="<?= BASE_URL ?>recuperar.php">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <label>
                    Correo electronico
                    <input type="email" name="correo" placeholder="usuario@correo.com" required autofocus>
                </label>
                <button type="submit">Enviar enlace de recuperacion</button>
            </form>
            <?php endif; ?>

            <p class="auth-note"><a href="<?= BASE_URL ?>login.php">← Volver al inicio de sesion</a></p>
        </section>
    </main>
</body>
</html>
