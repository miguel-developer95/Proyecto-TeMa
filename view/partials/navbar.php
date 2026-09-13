<?php
/**
 * Barra superior compartida (igual que la del dashboard).
 * Requiere: $titulo (string, ya definido en cada vista).
 * Opcional: $tituloNavbar (para mostrar otro texto) y $subtituloNavbar (texto plano).
 */
$tituloNavbar = $tituloNavbar ?? ($titulo ?? '');
$subtituloNavbar = $subtituloNavbar ?? null;
$userNavbar = current_user();
$nombreNavbar = trim((string) ($userNavbar['nombre'] ?? '') . ' ' . ($userNavbar['apellido'] ?? ''));
if ($nombreNavbar === '') {
    $nombreNavbar = (string) ($userNavbar['username'] ?? '');
} else {
    // Capitaliza (Enheban Gomex) sin importar cómo quedó guardado en la BD.
    $nombreNavbar = mb_convert_case($nombreNavbar, MB_CASE_TITLE, 'UTF-8');
}
$rolNavbar = ucfirst((string) ($userNavbar['rol'] ?? ''));
?>
<header class="top-navbar">
    <div>
        <h2><?= e($tituloNavbar) ?></h2>
        <?php if ($subtituloNavbar !== null && $subtituloNavbar !== ''): ?>
            <p class="muted" style="margin:4px 0 0"><?= e($subtituloNavbar) ?></p>
        <?php endif; ?>
    </div>
    <div class="user-profile">
        <div class="user-avatar"><?= e(strtoupper(mb_substr($nombreNavbar !== '' ? $nombreNavbar : '?', 0, 1))) ?></div>
        <div class="user-profile-info">
            <div><strong><?= e($nombreNavbar) ?></strong></div>
            <?php if ($rolNavbar !== ''): ?>
                <small class="muted" style="margin:0"><?= e($rolNavbar) ?></small>
            <?php endif; ?>
        </div>
    </div>
</header>
