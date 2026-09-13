<?php
/** Encabezado compartido. Requiere: $titulo (string). Abre <main>. */
$titulo = $titulo ?? 'Tentaciones Marlly';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - Tentaciones Marlly</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <?php foreach (flashes() as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
