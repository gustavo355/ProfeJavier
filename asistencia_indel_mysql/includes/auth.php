<?php
/**
 * includes/auth.php
 * Manejo de sesión y protección de páginas.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Corta el acceso si no hay sesión iniciada */
function requerir_login() {
    if (empty($_SESSION['id_usuario'])) {
        header("Location: login.php");
        exit;
    }
}

/** Corta el acceso si el rol del usuario no está permitido */
function requerir_rol(array $roles_permitidos) {
    requerir_login();
    if (!in_array($_SESSION['nombre_rol'], $roles_permitidos)) {
        http_response_code(403);
        die("No tienes permiso para acceder a esta sección.");
    }
}
