<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/informe.php';

/** Solo administradores (el router lo exige). */
class InformeController
{
    public function guardar(): void
    {
        $tipo = trim((string) post('tipo_informe'));
        $descripcion = trim((string) post('descripcion'));
        if ($tipo === '' || $descripcion === '') {
            flash('error', 'Faltan datos del informe.');
            redirect('view/informes.php');
        }
        (new Informe())->guardar($tipo, $descripcion, (int) (current_user()['id'] ?? 0));
        flash('success', 'Informe guardado en el historial.');
        redirect('view/informes.php?' . http_build_query([
            'inicio' => (string) post('inicio'),
            'fin' => (string) post('fin'),
        ]));
    }
}
