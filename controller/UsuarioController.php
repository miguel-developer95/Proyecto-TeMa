<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/historial.php';

class UsuarioController
{
    private Usuario $model;
    private Historial $historial;

    public function __construct()
    {
        $this->model = new Usuario();
        $this->historial = new Historial();
    }

    public function registrar(): void
    {
        $nombre = trim((string) post('nombre'));
        $apellido = trim((string) post('apellido'));
        $rol = trim((string) post('rol'));
        $email = trim((string) post('email'));
        $documento = trim((string) post('documento'));
        $password = (string) post('password');
        $confirm = (string) post('password_confirm');

        if ($nombre === '' || $apellido === '' || $rol === '' || $email === '' || $documento === '' || $password === '') {
            flash('error', 'Todos los campos son obligatorios.');
            redirect('view/register.php');
        }
        if (!in_array($rol, ['Administrador', 'Vendedor'], true)) {
            flash('error', 'Rol inválido.');
            redirect('view/register.php');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Correo electrónico inválido.');
            redirect('view/register.php');
        }
        if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
            flash('error', 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres.');
            redirect('view/register.php');
        }
        if ($password !== $confirm) {
            flash('error', 'Las contraseñas no coinciden.');
            redirect('view/register.php');
        }

        $res = $this->model->registrar($nombre, $apellido, $rol, $password, $email, $documento);
        if ($res['ok']) {
            $this->historial->registrar('registro_usuario', 'Registro: ' . $res['username'], null);
            flash('success', 'Cuenta creada. Tu nombre de usuario es: ' . $res['username']);
            redirect('view/login.php?status=registered');
        }
        flash('error', $res['error'] ?? 'No se pudo crear la cuenta. Verifica los datos.');
        redirect('view/register.php');
    }

    public function login(): void
    {
        $username = trim((string) post('username'));
        $password = (string) post('password');

        if ($username === '' || $password === '') {
            redirect('view/login.php?error=invalid_credentials');
        }

        // RF 1.7: bloqueo temporal antes de validar la clave
        $segundos = $this->model->verificarBloqueo($username);
        if ($segundos > 0) {
            redirect('view/login.php?error=locked&segundos=' . $segundos);
        }

        $user = $this->model->login($username, $password);
        if ($user) {
            $this->model->resetearIntentos($username);
            login_user($user);
            redirect_by_role();
        }

        $this->model->registrarIntentoFallido($username);
        $segundos = $this->model->verificarBloqueo($username);
        if ($segundos > 0) {
            redirect('view/login.php?error=locked&segundos=' . $segundos);
        }
        redirect('view/login.php?error=invalid_credentials');
    }

    public function logout(): void
    {
        logout_user();
        redirect('view/login.php');
    }

    /** Admin: crear o actualizar usuario desde Configuración. */
    public function guardar(): void
    {
        $id = (int) post('id');
        $datos = [
            'nombre' => trim((string) post('nombre')),
            'apellido' => trim((string) post('apellido')),
            'rol' => trim((string) post('rol')),
            'username' => trim((string) post('username')),
            'email' => trim((string) post('email')) ?: null,
            'documento' => trim((string) post('documento')) ?: null,
            'estado' => post('estado') === 'inactivo' ? 'inactivo' : 'activo',
            'password' => (string) post('password'),
        ];

        if ($datos['nombre'] === '' || $datos['username'] === '') {
            flash('error', 'Nombre y nombre de usuario son obligatorios.');
            redirect('view/configuracion.php');
        }
        if (!in_array($datos['rol'], ['Administrador', 'Vendedor'], true)) {
            flash('error', 'Rol inválido.');
            redirect('view/configuracion.php');
        }
        if ($datos['email'] !== null && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Correo electrónico inválido.');
            redirect('view/configuracion.php');
        }
        if ($datos['password'] !== '' && mb_strlen($datos['password']) < PASSWORD_MIN_LENGTH) {
            flash('error', 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres.');
            redirect('view/configuracion.php');
        }

        // Proteger al último administrador activo
        if ($id > 0) {
            $actual = $this->model->obtenerPorId($id);
            if ($actual && $actual['rol'] === 'Administrador'
                && ($datos['rol'] !== 'Administrador' || $datos['estado'] === 'inactivo')
                && $this->model->contarAdminsActivos() <= 1) {
                flash('error', 'No puedes degradar o desactivar al último administrador activo.');
                redirect('view/configuracion.php');
            }
            $ok = $this->model->actualizar($id, $datos);
            flash($ok ? 'success' : 'error', $ok ? 'Usuario actualizado.' : 'No se pudo actualizar (datos duplicados).');
            $this->historial->registrar('actualizar_usuario', 'Usuario #' . $id, current_user()['id'] ?? null);
        } else {
            if ($datos['password'] === '') {
                flash('error', 'La contraseña es obligatoria para usuarios nuevos.');
                redirect('view/configuracion.php');
            }
            $res = $this->model->registrar(
                $datos['nombre'], $datos['apellido'], $datos['rol'],
                $datos['password'], $datos['email'], $datos['documento']
            );
            flash($res['ok'] ? 'success' : 'error', $res['ok']
                ? 'Usuario creado: ' . $res['username']
                : ($res['error'] ?? 'No se pudo crear el usuario.'));
        }
        redirect('view/configuracion.php');
    }

    /** Admin: activar/desactivar usuario sin borrar (POST + CSRF). */
    public function estado(): void
    {
        $id = (int) post('id');
        $estado = post('estado') === 'inactivo' ? 'inactivo' : 'activo';
        $yo = (int) (current_user()['id'] ?? 0);
        $retorno = post('return_estado') === 'inactivo' ? '?estado=inactivo' : '';

        if ($id <= 0) {
            flash('error', 'Usuario inválido.');
            redirect('view/configuracion.php' . $retorno);
        }
        $u = $this->model->obtenerPorId($id);
        if (!$u) {
            flash('error', 'Usuario no encontrado.');
            redirect('view/configuracion.php' . $retorno);
        }
        if ($estado === 'inactivo') {
            if ($id === $yo) {
                flash('error', 'No puedes desactivar tu propia cuenta.');
                redirect('view/configuracion.php' . $retorno);
            }
            if ($u['rol'] === 'Administrador' && $this->model->contarAdminsActivos() <= 1) {
                flash('error', 'No puedes desactivar al último administrador activo.');
                redirect('view/configuracion.php' . $retorno);
            }
        }

        if ($this->model->cambiarEstado($id, $estado)) {
            $this->historial->registrar('estado_usuario', 'Usuario #' . $id . ' -> ' . $estado, $yo);
            flash('success', $estado === 'activo' ? 'Usuario reactivado.' : 'Usuario desactivado.');
        } else {
            flash('error', 'No se pudo cambiar el estado.');
        }
        redirect('view/configuracion.php' . $retorno);
    }

    /** Admin: eliminar usuario (POST + CSRF). */
    public function eliminar(): void
    {
        $id = (int) post('id');
        $yo = (int) (current_user()['id'] ?? 0);

        if ($id <= 0) {
            flash('error', 'Usuario inválido.');
            redirect('view/configuracion.php');
        }
        if ($id === $yo) {
            flash('error', 'No puedes eliminar tu propia cuenta.');
            redirect('view/configuracion.php');
        }
        $u = $this->model->obtenerPorId($id);
        if ($u && $u['rol'] === 'Administrador' && $this->model->contarAdminsActivos() <= 1) {
            flash('error', 'No puedes eliminar al último administrador activo.');
            redirect('view/configuracion.php');
        }

        if ($this->model->eliminar($id)) {
            $this->historial->registrar('eliminar_usuario', 'Usuario #' . $id, $yo);
            flash('success', 'Usuario eliminado.');
        } else {
            flash('error', 'No se pudo eliminar (puede tener registros asociados). Desactívalo en su lugar.');
        }
        redirect('view/configuracion.php');
    }

    /** RF 1.5: solicitar enlace de restablecimiento. */
    public function solicitarReset(): void
    {
        $email = trim((string) post('email'));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->model->obtenerPorEmail($email);
            if ($user) {
                $token = $this->model->crearTokenReset((int) $user['id']);
                // En el correo el enlace debe ser ABSOLUTO (uno relativo no abre fuera del sitio).
                $link = base_url_abs('view/restablecer.php?token=' . urlencode($token));
                // Intento de envío real (restaurado de bda5c73, saneado vía .env).
                $mailer = __DIR__ . '/../helpers/mailer_recuperacion.php';
                if (is_file($mailer)) {
                    require_once $mailer;
                    $nombre = (string) ($user['nombre'] ?? $user['username'] ?? 'usuario');
                    if (mailerConfigurado() && enviarCorreoRecuperacion($email, $nombre, $link)) {
                        flash('success', 'Si el correo está registrado, recibirás un enlace de recuperación válido por 1 hora.');
                        redirect('view/recuperar.php');
                    }
                }
                // Fallback local (comportamiento de 677f89f): sin SMTP se muestra el enlace.
                if (APP_DEBUG) {
                    flash('info', 'Enlace de recuperación (modo local): ' . $link);
                }
            }
        }
        // Mensaje genérico para no revelar si el correo existe.
        flash('success', 'Si el correo está registrado, recibirás un enlace de recuperación válido por 1 hora.');
        redirect('view/recuperar.php');
    }

    /** RF 1.5: aplicar nueva contraseña con token válido. */
    public function restablecer(): void
    {
        $token = (string) post('token');
        $p1 = (string) post('password');
        $p2 = (string) post('password_confirm');

        if ($token === '' || mb_strlen($p1) < PASSWORD_MIN_LENGTH || $p1 !== $p2) {
            flash('error', 'Datos inválidos: verifica el enlace y que ambas contraseñas coincidan (mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres).');
            redirect('view/restablecer.php?token=' . urlencode($token));
        }
        if ($this->model->restablecerConToken($token, $p1)) {
            flash('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
            redirect('view/login.php?status=password_reset');
        }
        flash('error', 'El enlace es inválido o expiró. Solicita uno nuevo.');
        redirect('view/recuperar.php');
    }
}
