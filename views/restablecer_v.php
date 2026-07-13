<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contrasena - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 45px; width: 100%; }
        .toggle-icon { position: absolute; right: 12px; cursor: pointer; color: var(--muted); }
        .toggle-icon:hover { color: var(--brand); }
    </style>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand-panel">
            <img src="<?= imagenAsset('logo-colegio') ?>" alt="Logo del colegio" class="school-logo">
            <p class="eyebrow">Institucion Educativa</p>
            <h1>BI Educativo Piura 2026</h1>
            <p>Crea una nueva contrasena segura para tu cuenta.</p>
            <div class="auth-highlights">
                <span>Seguro</span>
                <span>Rapido</span>
                <span>Simple</span>
            </div>
        </section>

        <section class="auth-card">
            <p class="eyebrow">Acceso al sistema</p>
            <h2>Nueva contrasena</h2>
            <p>Hola <strong><?= e($usuario['nombres'] ?? '') ?></strong>, elige una contrasena nueva.</p>

            <?php if (!empty($error)): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>restablecer.php?token=<?= e($token) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <label>
                    Nueva contrasena
                    <div class="password-wrapper">
                        <input type="password" name="contrasena" id="pass1" placeholder="Min. 8 caracteres" required autofocus>
                        <i class="fas fa-eye toggle-icon" data-target="pass1"></i>
                    </div>
                </label>
                <label>
                    Confirmar contrasena
                    <div class="password-wrapper">
                        <input type="password" name="confirma" id="pass2" placeholder="Repite la contrasena" required>
                        <i class="fas fa-eye toggle-icon" data-target="pass2"></i>
                    </div>
                </label>
                <button type="submit">Guardar nueva contrasena</button>
            </form>

            <p class="auth-note"><a href="<?= BASE_URL ?>login.php">← Volver al inicio de sesion</a></p>
        </section>
    </main>
    <script>
        document.querySelectorAll('.toggle-icon').forEach(icon => {
            icon.addEventListener('click', () => {
                const input = document.getElementById(icon.dataset.target);
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
