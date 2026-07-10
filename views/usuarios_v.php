<?php
$usuario = $usuario ?? $_SESSION['usuario'] ?? [];
$adminNombre = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$adminRol    = $usuario['rol'] ?? 'Administrador';

$menuAdmin = [
    'admin.php'          => ['icono' => 'fa-gauge',          'texto' => 'Panel principal'],
    'usuarios.php'       => ['icono' => 'fa-users',          'texto' => 'Usuarios'],
    'asistencia.php'     => ['icono' => 'fa-calendar-check', 'texto' => 'Asistencia'],
    'notificaciones.php' => ['icono' => 'fa-bell',           'texto' => 'Notificaciones'],
    'configuracion.php'  => ['icono' => 'fa-sliders',        'texto' => 'Configuracion'],
    'auditoria.php'      => ['icono' => 'fa-shield-halved',  'texto' => 'Auditoria'],
    'dashboard.php'      => ['icono' => 'fa-chart-bar',      'texto' => 'Dashboard BI'],
];

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'usuarios.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            color: var(--muted);
            transition: color 0.2s;
        }
        .toggle-icon:hover { color: var(--brand); }
        .table-input {
            border: 1px solid transparent;
            background: transparent;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.875rem;
            width: 100%;
            transition: all 0.2s;
        }
        .table-input:focus, .table-input:hover {
            border-color: var(--line);
            background: #fff;
            outline: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="teacher-page">

    <aside class="teacher-sidebar">
        <div class="teacher-brand">
            <span>A</span>
            <div>
                <strong><?= e($adminNombre ?: 'Administrador') ?></strong>
                <small><?= e($adminRol) ?></small>
            </div>
        </div>
        <nav>
            <?php foreach ($menuAdmin as $url => $item): ?>
                <a class="<?= $paginaActual === $url ? 'active' : '' ?>"
                   href="<?= BASE_URL . e($url) ?>">
                    <i class="fas <?= e($item['icono']) ?>" style="width:18px; margin-right:8px; opacity:.75;"></i>
                    <?= e($item['texto']) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>logout.php">
                <i class="fas fa-right-from-bracket" style="width:18px; margin-right:8px; opacity:.75;"></i>
                Cerrar sesion
            </a>
        </nav>
    </aside>

    <main class="teacher-main">
        <div class="container-fluid px-4 py-3">
            <div class="row g-4">
                <!-- Columna 4: Tarjeta Informativa -->
                <div class="col-lg-4">
                    <section class="hero h-100 m-0" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 2rem; border-radius: 1.5rem; display: flex; flex-direction: column; justify-content: center;">
                        <p class="eyebrow" style="color: #38bdf8;">Administracion de Accesos</p>
                        <h1 style="color: white; font-size: 1.8rem; margin: 0.5rem 0;">Usuarios Registrados</h1>
                        <p style="color: #cbd5e1; font-size: 0.9rem;">Control de roles, estados de cuenta y credenciales institucionales para el periodo 2026.</p>
                        <p style="color: #94a3b8; font-size: 0.8rem; margin-top: 1rem;">El administrador puede corregir datos, cambiar el rol y gestionar el estado de las cuentas.</p>
                    </section>
                </div>

                <!-- Columna 8: Formulario de Creacion -->
                <div class="col-lg-8">
                    <section class="panel m-0 h-100" style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">Formulario</p>
                                <h2>Registrar nuevo usuario</h2>
                            </div>
                        </div>
                        <form method="post" action="<?= BASE_URL ?>usuarios.php" class="form-grid">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="accion" value="crear">
                            <label>DNI<input type="text" name="dni" maxlength="8" pattern="[0-9]{8}" required></label>
                            <label>Nombres<input type="text" name="nombres" required></label>
                            <label>Apellidos<input type="text" name="apellidos" required></label>
                            <label>Correo<input type="email" name="correo" required></label>
                            <label>WhatsApp<input type="tel" name="telefono" maxlength="20" pattern="[0-9]{9,20}" placeholder="Ej. 987654321" inputmode="numeric"></label>
                            <label>Contrasena
                                <div class="password-wrapper">
                                    <input type="password" name="contrasena" id="contrasena" placeholder="Minimo 8: Aa, numero y simbolo" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" required>
                                    <i class="fas fa-eye toggle-icon" id="togglePassword"></i>
                                </div>
                            </label>
                            <label>Rol
                                <select name="id_rol" required>
                                    <?php if (isset($roles) && is_array($roles)): foreach ($roles as $rol): ?>
                                        <option value="<?= (int) $rol['id_rol'] ?>"><?= e($rol['nombre_rol']) ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </label>
                            <label>Estado
                                <select name="estado_cuenta" required>
                                    <option value="activo">Activo</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </label>
                            <button type="submit">Crear usuario</button>
                        </form>
                    </section>
                </div>
            </div>

            <!-- Listado General -->
            <div class="row mt-4">
                <div class="col-12">
                    <section class="panel" style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">Listado General</p>
                                <h2>Cuentas activas y solicitudes</h2>
                            </div>
                            <span class="pill"><?= (isset($usuarios) && is_array($usuarios)) ? count($usuarios) : 0 ?> usuarios</span>
                        </div>

                        <?php if (!empty($mensaje)): ?>
                            <div class="alert ok"><?= e($mensaje) ?></div>
                        <?php endif; ?>

                        <div class="table-wrap">
                            <table class="paginated">
                                <thead>
                                    <tr>
                                        <th width="25%">Usuario</th>
                                        <th width="20%">Correo</th>
                                        <th width="15%">WhatsApp</th>
                                        <th width="15%">Rol</th>
                                        <th width="10%">Estado</th>
                                        <th width="15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($usuarios) && is_array($usuarios) && !empty($usuarios)): ?>
                                        <?php foreach ($usuarios as $row): ?>
                                            <?php $formId = "form-edit-" . (int)$row['id_usuario']; ?>
                                            <tr>
                                                <td>
                                                    <form id="<?= $formId ?>" method="post" action="<?= BASE_URL ?>usuarios.php">
                                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                        <input type="hidden" name="accion" value="editar">
                                                        <input type="hidden" name="id_usuario" value="<?= (int) $row['id_usuario'] ?>">
                                                        <input type="hidden" name="dni" value="<?= e($row['dni']) ?>">
                                                        <strong style="color:var(--brand); font-size:11px;">DNI <?= e($row['dni']) ?></strong><br>
                                                        <input type="text" name="nombres" class="table-input" value="<?= e($row['nombres']) ?>" required style="margin-bottom: 2px; font-weight:700;">
                                                        <input type="text" name="apellidos" class="table-input" value="<?= e($row['apellidos']) ?>" required>
                                                    </form>
                                                </td>
                                                <td>
                                                    <input type="email" name="correo" class="table-input" form="<?= $formId ?>" value="<?= e($row['correo']) ?>" required>
                                                </td>
                                                <td>
                                                    <input type="tel" name="telefono" class="table-input" form="<?= $formId ?>" maxlength="20" pattern="[0-9]{9,20}" value="<?= e($row['telefono'] ?? '') ?>" placeholder="Sin numero">
                                                </td>
                                                <td>
                                                    <select name="id_rol" class="table-input" form="<?= $formId ?>" required>
                                                        <?php if (isset($roles) && is_array($roles)): ?>
                                                            <?php foreach ($roles as $rol): ?>
                                                                <option value="<?= (int) $rol['id_rol'] ?>" <?= (int) $rol['id_rol'] === (int) $row['id_rol'] ? 'selected' : '' ?>>
                                                                    <?= e($rol['nombre_rol']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="estado_cuenta" class="table-input" form="<?= $formId ?>" required style="font-weight:700; color:<?= $row['estado_cuenta'] === 'activo' ? 'var(--ok)' : 'var(--danger)' ?>;">
                                                        <?php foreach (['activo', 'rechazado'] as $estado): ?>
                                                            <option value="<?= e($estado) ?>" <?= $estado === $row['estado_cuenta'] ? 'selected' : '' ?>>
                                                                <?= e(ucfirst($estado)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="inline-actions">
                                                        <button type="submit" form="<?= $formId ?>">Guardar</button>
                                                        <form method="post" action="<?= BASE_URL ?>usuarios.php" onsubmit="return confirm('Eliminar este usuario?')">
                                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                            <input type="hidden" name="id_usuario" value="<?= (int) $row['id_usuario'] ?>">
                                                            <button type="submit" name="accion" value="eliminar" class="danger-btn">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="empty">No hay usuarios registrados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.paginated').forEach((table) => {
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            if (rows.length <= 10) return;
            let page = 0;
            const pager = document.createElement('div');
            pager.className = 'pager';
            const prev = document.createElement('button');
            const next = document.createElement('button');
            const label = document.createElement('span');
            prev.innerHTML = '<i class="fas fa-chevron-left"></i> Anterior';
            next.innerHTML = 'Siguiente <i class="fas fa-chevron-right"></i>';
            pager.append(prev, label, next);
            table.after(pager);
            const draw = () => {
                rows.forEach((row, index) => row.style.display = index >= page * 10 && index < (page + 1) * 10 ? '' : 'none');
                label.textContent = `Pagina ${page + 1} de ${Math.ceil(rows.length / 10)}`;
                prev.disabled = page === 0;
                next.disabled = page >= Math.ceil(rows.length / 10) - 1;
            };
            prev.addEventListener('click', () => { page--; draw(); });
            next.addEventListener('click', () => { page++; draw(); });
            draw();
        });

        const toggleBtn = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#contrasena');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>
