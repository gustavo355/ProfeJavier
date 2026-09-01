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
5. Crea al menos una **sección** y un **estudiante** de prueba desde
   phpMyAdmin (pestaña "Insertar" en cada tabla), o descomenta los `INSERT`
   de ejemplo al final de `asistencia_indel.sql`.
6. Visita `http://localhost/asistencia_indel/crear_admin.php` en el navegador
   **una sola vez** para crear el usuario administrador (`admin` / `admin123`).
   Bórralo después, por seguridad.
7. Entra por `login.php`.

## 3. Páginas incluidas

| Archivo                     | Función                                                            |
|------------------------------|---------------------------------------------------------------------|
| `login.php`                  | Inicio de sesión (valida contra `usuarios` + `roles`)               |
| `registrar_asistencia.php`   | Lista estudiantes por sección, con búsqueda y estado editable       |
| `process_asistencia.php`     | Guarda/actualiza los registros en `asistencia`                      |
| `generar_reporte.php`        | Formulario de reporte + estadísticas (activos, % asistencia, etc.)  |
| `process_reporte.php`        | Genera el reporte (CSV para Excel, vista imprimible para PDF)       |
| `report_print.php`           | Vista lista para imprimir / guardar como PDF                        |
| `crear_admin.php`            | Crea el primer usuario administrador (bórralo tras usarlo)          |
| `asistencia_indel.sql`       | Script de creación de la base de datos para importar en phpMyAdmin  |

## 4. Notas

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
