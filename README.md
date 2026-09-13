# Tentaciones Marlly · Proyecto-TeMa

Copia de trabajo del proyecto original (`Proyecto-TeMa`) con la base técnica,
la seguridad y los módulos reconstruidos. El proyecto original **no fue modificado**.

## Requisitos

- XAMPP (PHP 8.0+, MySQL/MariaDB, Apache)
- La carpeta debe estar en `C:\xampp\htdocs\Proyecto-TeMa`

## Instalación

1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Crea la base de datos importando `database/schema.sql`:
   - Con phpMyAdmin: pestaña **Importar** → selecciona `database/schema.sql`.
   - O por consola: `C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql`
3. Revisa el archivo `.env` (copia `.env.example` si no existe) y ajusta
   `APP_BASE_URL`, `DB_NAME`, `DB_USER` y `DB_PASS` según tu entorno.
4. Abre `http://localhost/Proyecto-TeMa/` en el navegador.

## Acceso inicial

El seed crea un administrador (cámbialo tras el primer ingreso):

- Usuario: `admin`
- Clave: `Admin123*`

También se cargan 4 métodos de pago, 1 proveedor y 3 productos de demostración.

## Estructura

```
index.php               Front controller / router de acciones
config/
  config.php            Bootstrap (.env, BASE_URL, errores, sesión)
  Connection.php        ÚNICA clase de conexión PDO (antes había 4 formas)
database/schema.sql     Esquema ÚNICO y seeds (antes no existía)
helpers/
  functions.php         base_url(), e(), CSRF, flashes, money()
  auth.php              Sesión segura, timeout 30 min, roles
controller/             Un controlador por dominio (Usuario, Producto, Compra, Venta, Informe)
model/                  Un modelo por tabla, todos contra el mismo esquema
view/
 partials/              head/sidebar/foot compartidos (antes CSS duplicado)
  login, register, recuperar, restablecer, dashboard, configuracion,
  inventario, compras, proveedores, informes, pos, recibo, error
public/styles/app.css   Hoja de estilos ÚNICA
```

## Qué se corrigió respecto al original

1. **Conexión única**: se eliminaron `conexion.php`/`database.php`/`tentaciones_marlly.php`
   inexistentes, el `global $pdo` indefinido y la clase `Conexion` fantasma.
2. **Esquema único** (`database/schema.sql`): antes cada modelo asumía tablas y
   columnas distintas (`productos` vs `PRODUCTOS`, `ventas` vs `VENTA`,
   `cliente` vs `clientes`, `compra` vs `PROVEEDORES`...). Nada podía funcionar
   de extremo a extremo.
3. **Clase `Compra` duplicada** (estaba en `compra.php` y `detalle_compra.php`).
4. **`register_user.php`** llamaba `registrar()` con argumentos incorrectos (muerto).
5. **`DashboardController.php` vacío** y **`pos.php`** con solo "hello POS".
6. **Editar usuario borraba email y documento** (los ponía a NULL).
7. **`recuperar_contra.php`** redirigía a un archivo inexistente (`recuperar_contraseña.php`).
8. **`<main>` duplicado** en `dashboard.php` y tarjetas con ceros fijos.
9. **Sidebar con enlaces a `inventario.php`/`compra.php` inexistentes** (404).
10. **URL base `/Proyecto-TeMa` hardcodeada 47 veces** → ahora `base_url()`.
11. **Archivo con ñ** (`historial_modificación.php`) → `model/historial.php`.

## Seguridad aplicada

- Tokens **CSRF** en todos los formularios POST.
- Acciones destructivas (**eliminar/anular**) solo por **POST**, nunca por GET.
- **Control de roles en servidor** en cada vista y ruta (antes `configuracion.php`
  era accesible para vendedores por URL directa).
- **Escape `e()`** en todas las salidas (corrige el XSS de recuperación).
- `session_regenerate_id()` al iniciar sesión; cookie `HttpOnly` + `SameSite=Lax`.
- **Timeout de inactividad de 30 minutos** (RF 1.8, antes inexistente).
- Errores visibles solo con `APP_DEBUG=1`; en producción van al log.
- Bloqueo temporal tras intentos fallidos configurable (`LOCKOUT_ATTEMPTS/MINUTES`).
- Recuperación de contraseña con **token aleatorio de un solo uso** (1 hora).
- Protección del último administrador activo (no se puede degradar/eliminar).

## Cobertura de requerimientos

| RF | Estado |
|----|--------|
| 1.1–1.4, 1.6, 1.7 (registro, login, roles, logout, bloqueo) | Implementado |
| 1.5 (restablecer contraseña) | Implementado con token |
| 1.8 (cierre por inactividad 30 min) | Implementado |
| 2.1–2.2 (ganancia, rotación) | Implementado en Informes |
| 2.3 (permisos admin/vendedor) | Implementado por roles |
| 3.1–3.5 (inventario + alertas) | Implementado |
| 4.1–4.4 (compras, proveedores, historial, anulación) | Implementado |
| 5.1–5.3, 5.5, 5.6 (POS, pago, anulación, recibo) | Implementado |
| 5.4 (pausar/recuperar venta) | Implementado |

## Notas

- En modo local (`APP_DEBUG=1`) el enlace de recuperación se muestra en pantalla
  porque XAMPP no envía correos. En producción debe integrarse un servicio SMTP.
- `empresa` en ventas se deriva del método de pago (Nequi, Daviplata, etc.).

