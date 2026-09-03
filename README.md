# Asistencia INDEL — Sistema PHP (MySQL / phpMyAdmin)

Sistema web en PHP conectado a una base de datos `asistencia_indel` en
**MySQL**, administrable desde **phpMyAdmin**, con las 3 primeras pantallas
del wireframe: **Iniciar sesión**, **Registrar asistencia** y **Generar
reporte**.

## 1. Requisitos

- PHP 8+
- MySQL / MariaDB (el que trae XAMPP, WAMP o Laragon)
- phpMyAdmin (incluido en XAMPP/WAMP/Laragon)
- Extensión **pdo_mysql** habilitada en PHP (viene activada por defecto en XAMPP)

## 2. Instalación

1. Copia la carpeta `asistencia_indel` dentro de `htdocs` (XAMPP) o tu servidor web.
2. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Ve a la pestaña **"Importar"**, selecciona el archivo `asistencia_indel.sql`
   incluido en este proyecto y presiona **"Continuar"**.
   Esto crea automáticamente la base de datos `asistencia_indel` con todas
   sus tablas (roles, usuarios, secciones, estudiantes, asistencia,
   servicio_social, meritos_demeritos, reportes_generados).
4. Abre `config.php` y ajusta los datos de conexión si es necesario:
   ```php
   $db_host = "localhost";
   $db_port = "3306";
   $db_name = "asistencia_indel";
   $db_user = "root";   // usuario de MySQL
   $db_pass = "";       // contraseña de MySQL (vacía por defecto en XAMPP)
   ```
5. Visita `http://localhost/asistencia_indel/crear_admin.php` en el navegador
   **una sola vez** para crear el usuario administrador (`admin` / `admin123`).
   Bórralo después, por seguridad.
6. Entra por `login.php` y crea al menos una **sección** desde `secciones.php`
   y un **estudiante** desde `estudiantes.php`. Ya no hace falta usar
   phpMyAdmin para esto: todo el catálogo (usuarios, secciones y
   estudiantes) se administra desde el propio sistema.

## 3. Páginas incluidas

| Archivo                     | Función                                                            |
|------------------------------|---------------------------------------------------------------------|
| `login.php`                  | Inicio de sesión (valida contra `usuarios` + `roles`)               |
| `usuarios.php`                | Gestión de usuarios: crear, editar, activar/desactivar (solo Administrador) |
| `process_usuario.php`         | Procesa el alta/edición/activación de usuarios                     |
| `secciones.php`               | Gestión de secciones: crear, editar, eliminar (solo Administrador) |
| `process_seccion.php`         | Procesa el alta/edición/eliminación de secciones                   |
| `estudiantes.php`             | Gestión de estudiantes: crear, editar, activar/desactivar, filtrar por sección/estado/búsqueda (solo Administrador) |
| `process_estudiante.php`      | Procesa el alta/edición/cambio de estado de estudiantes             |
| `includes/navbar.php`         | Barra de navegación superior común a todas las pantallas internas   |
| `registrar_asistencia.php`   | Lista estudiantes por sección, con búsqueda y estado editable       |
| `process_asistencia.php`     | Guarda/actualiza los registros en `asistencia`                      |
| `meritos_demeritos.php`      | Registra méritos/deméritos por estudiante y muestra el historial    |
| `process_merito.php`         | Guarda el registro en `meritos_demeritos`                           |
| `servicio_social.php`        | Registra horas de servicio social y muestra el acumulado por estudiante (meta: 50h) |
| `process_servicio.php`       | Guarda el registro en `servicio_social`                             |
| `generar_reporte.php`        | Formulario de reporte + estadísticas (activos, % asistencia, etc.)  |
| `process_reporte.php`        | Genera el reporte (CSV para Excel, vista imprimible para PDF)       |
| `report_print.php`           | Vista lista para imprimir / guardar como PDF                        |
| `crear_admin.php`            | Crea el primer usuario administrador (bórralo tras usarlo)          |
| `asistencia_indel.sql`       | Script de creación de la base de datos para importar en phpMyAdmin  |

## 4. Roles y permisos

- El sistema tiene dos roles: **Administrador** y **Docente**.
- Cualquier usuario con sesión iniciada puede registrar asistencia, méritos/deméritos,
  horas de servicio social y generar reportes.
- Solo el rol **Administrador** puede entrar a `usuarios.php`, `secciones.php` y
  `estudiantes.php`. Un docente que intente entrar a esas URL directamente recibe
  un error 403.
- Un administrador no puede desactivar su propia cuenta (para no bloquearse a sí mismo).
- Una sección solo puede eliminarse si no tiene estudiantes asignados; si los tiene,
  el sistema lo impide y muestra un aviso.
- Los estudiantes no se eliminan físicamente (para no romper el historial de
  asistencia, méritos y servicio social ya registrado): se **desactivan**, igual
  que los usuarios, y dejan de aparecer en los listados de registro.
- Después de crear el admin con `crear_admin.php`, todo el catálogo (docentes,
  secciones y estudiantes) se administra desde el propio sistema — ya no hace
  falta editar la base de datos a mano ni usar phpMyAdmin para el día a día.

## 5. Notas

- Las contraseñas se guardan con `password_hash()` (bcrypt), nunca en texto plano.
- El reporte en formato **PDF** abre una vista imprimible en el navegador;
  usa "Imprimir → Guardar como PDF". Si luego quieres un PDF generado en
  servidor, se puede integrar una librería como **mPDF** o **TCPDF** sin
  cambiar la lógica de datos ya construida en `process_reporte.php`.
- El diseño (`assets/css/style.css`) sigue el estilo del wireframe original:
  tarjetas blancas, esquinas redondeadas y acentos en azul/marino.
- La conexión usa PDO con `mysql:` como driver (antes era `sqlsrv:` para
  SQL Server). Todas las consultas ya usaban parámetros con nombre
  (`:parametro`), así que son 100% compatibles con MySQL sin cambios,
  salvo la fecha actual del sistema, que ahora usa `NOW()` en lugar de
  `GETDATE()`.
