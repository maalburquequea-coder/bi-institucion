<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input { padding-right: 40px; width: 100%; }
        .toggle-icon {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
    <body class="auth-page">
    <main class="auth-shell register-shell">
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

            <section class="auth-card wide">
                <a class="back-link" href="<?= BASE_URL ?>login.php">Ya tengo cuenta: iniciar sesión</a>
                <p class="eyebrow">Nuevo usuario</p>
                <h2>Crear cuenta</h2>
                <p>Registro para maestros y padres de familia. Recibirás un correo para activar tu acceso.</p>

            <?php if (!empty($error)): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($ok)): ?>
                <div class="alert ok"><?= e($ok) ?></div>
            <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>registro.php" autocomplete="on" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <label>
                        Tipo de usuario
                        <select name="id_rol" id="id_rol" required>
                            <option value="">Seleccionar</option>
                        <?php if (isset($roles) && is_array($roles)): ?>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= (int) $rol['id_rol'] ?>" data-rol="<?= e($rol['nombre_rol']) ?>" <?= (int) ($data['id_rol'] ?? 0) === (int) $rol['id_rol'] ? 'selected' : '' ?>><?= e($rol['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </select>
                    </label>
                    <label>
                        DNI
                        <input type="text" name="dni" maxlength="8" pattern="[0-9]{8}" placeholder="8 digitos" inputmode="numeric" value="<?= e($data['dni'] ?? '') ?>" required>
                    </label>
                    <label>
                        Nombres
                        <input type="text" name="nombres" placeholder="Tus nombres" value="<?= e($data['nombres'] ?? '') ?>" required>
                    </label>
                    <label>
                        Apellidos
                        <input type="text" name="apellidos" placeholder="Tus apellidos" value="<?= e($data['apellidos'] ?? '') ?>" required>
                    </label>
                    <label>
                        Correo electrónico
                        <input type="email" name="correo" placeholder="usuario@correo.com" value="<?= e($data['correo'] ?? '') ?>" required>
                    </label>
                    <label>
                        WhatsApp
                        <input type="tel" name="telefono" maxlength="20" pattern="[0-9]{9,20}" placeholder="Ej. 987654321" inputmode="numeric" value="<?= e($data['telefono'] ?? '') ?>">
                    </label>
                    <label>
                        Contraseña
                        <div class="password-wrapper">
                            <input
                                type="password"
                                name="contrasena"
                                id="contrasena"
                                minlength="8"
                                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                                placeholder="Mínimo 8: Aa, número y símbolo"
                                title="Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo"
                                required
                            >
                            <i class="fas fa-eye toggle-icon" id="togglePassword"></i>
                        </div>
                    </label>
                    <label class="parent-only" id="field-dni-estudiante">
                        DNI del estudiante
                        <input type="text" id="dni_estudiante" name="dni_estudiante" maxlength="20" pattern="[0-9]{8,20}" placeholder="DNI o codigo de su hijo/a" inputmode="numeric" value="<?= e($data['dni_estudiante'] ?? '') ?>">
                    </label>
                    <button type="submit">Registrar mi cuenta</button>
            </form>
        </section>
    </main>
    <script>
        const selectRol = document.getElementById('id_rol');
        const parentField = document.querySelector('.parent-only');
        const inputDniEst = document.getElementById('dni_estudiante');
        const toggleBtn = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#contrasena');

        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        const toggleParentField = () => {
            const option = selectRol.options[selectRol.selectedIndex];
            parentField.style.display = option && option.dataset.rol === 'Padre' ? 'grid' : 'none';
            inputDniEst.required = (option && option.dataset.rol === 'Padre');
        };
        selectRol.addEventListener('change', toggleParentField);
        toggleParentField();
    </script>
</body>
</html>
