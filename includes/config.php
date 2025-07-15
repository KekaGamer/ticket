<?php
// Configuración básica del sistema
define('DB_HOST', 'localhost');
define('DB_USER', 'cma110690_masercom'); // <-- ¡Verifica esta credencial!
define('DB_PASS', '%R0dr1g03009%'); // <-- ¡Verifica esta credencial!
define('DB_NAME', 'cma110690_masercom_tickets');

// LÍNEA CORREGIDA
define('BASE_URL', 'https://masercom-qa.cl/ticket/'); // Corregido a .cl

define('SITE_NAME', 'MASERCOM Tickets');
define('ADMIN_EMAIL', 'soporte@masercom-qa.cl');

// Configuración de correo
define('MAIL_HOST', 'mail.masercom-qa.cl');
define('MAIL_USER', 'tickets@masercom-qa.cl');
define('MAIL_PASS', '%R0dr1g03009%');
define('MAIL_PORT', 587);
define('MAIL_FROM', 'tickets@masercom-qa.cl');
define('MAIL_FROM_NAME', 'Sistema de Tickets MASERCOM');

// Configuración de Active Directory
define('AD_SERVER', 'ldap://cl1.masercom.cl');
define('AD_DOMAIN', 'masercom.cl');
define('AD_BASEDN', 'dc=masercom,dc=cl');
define('AD_USER', 'admin_ad@masercom-qa.cl');
define('AD_PASS', '%R0dr1g03009%');

// Iniciar sesión
session_start();

// Zona horaria
date_default_timezone_set('America/Santiago');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');
?>