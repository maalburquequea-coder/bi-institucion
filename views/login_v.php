<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input { 
            padding-right: 45px; 
            width: 100%; 
            transition: all 0.3s ease;
        }
        .toggle-icon {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: var(--muted);
            transition: color 0.2s;
        }
        .toggle-icon:hover { color: var(--brand); }
        .auth-card { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important; }
    </style>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand-panel">
            <img src="<?= imagenAsset('logo-colegio') ?>" alt="Logo del colegio" class="school-logo">
            <p class="eyebrow">Institucion Educativa</p>
            <h1>BI Educativo Piura 2026</h1>
            <p>Seguimiento academico, alertas tempranas y comunicacion con padres de familia.</p>
            <div class="auth-highlights">
                <span>Rendimiento</span>
                <span>Asistencia</span>
                <span>Alertas</span>
            </div>
        </section>

        <section class="auth-card">
            <p class="eyebrow">Acceso al sistema</p>
            <h2>Iniciar sesion</h2>
            <p>Ingresa con tu correo para acceder al panel de la plataforma.</p>

            <?php if (!empty($error)): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($ok)): ?>
                <div class="alert ok"><?= e($ok) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>login.php" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <label>
                    Correo electronico
                    <input type="email" name="correo" placeholder="usuario@correo.com" required>
                </label>
                <label>
                    Contrasena
                    <div class="password-wrapper">
                        <input type="password" name="contrasena" id="contrasena" placeholder="Tu contrasena" required>
                        <i class="fas fa-eye toggle-icon" id="togglePassword"></i>
                    </div>
                </label>
                <button type="submit">Ingresar al panel</button>
            </form>

            <p class="auth-note">No tengo cuenta: <a href="<?= BASE_URL ?>registro.php">Registrarme</a></p>
        </section>
    </main>

    <script>
        const toggleBtn = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#contrasena');

        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
