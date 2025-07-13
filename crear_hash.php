<?php
// Define la contraseña que quieres establecer
$password = '%R0dr1g03009%';

// Genera el hash de la contraseña
$hash = password_hash($password, PASSWORD_BCRYPT);

// Muestra el hash en pantalla
echo "Copia este hash y úsalo en el comando SQL:<br><br>";
echo "<b>" . $hash . "</b>";

// Muestra el comando SQL completo para facilitar la tarea
echo "<br><br>---<br><br>";
echo "Comando SQL para ejecutar en phpMyAdmin:<br><br>";
echo "<code>UPDATE usuarios SET password = '" . $hash . "' WHERE email = 'admin@masercom.cl';</code>";
?>