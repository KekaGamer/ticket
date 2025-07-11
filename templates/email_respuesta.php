<?php
/**
 * Plantilla de correo para respuesta a ticket
 * Variables disponibles:
 * - $ticket: Array con información del ticket
 * - $mensaje: Mensaje de respuesta
 * - $usuario: Nombre del usuario que respondió
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta al Ticket #<?php echo $ticket['id']; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .ticket-info {
            margin-bottom: 20px;
        }
        .ticket-info p {
            margin: 5px 0;
        }
        .ticket-id {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-abierto {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .response {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #3498db;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Tickets <?php echo SITE_NAME; ?></h1>
    </div>
    
    <div class="content">
        <h2>Nueva respuesta a tu ticket</h2>
        
        <div class="ticket-info">
            <p><span class="ticket-id">Ticket #<?php echo $ticket['id']; ?></span></p>
            <p><strong>Título:</strong> <?php echo htmlspecialchars($ticket['titulo']); ?></p>
            <p><strong>Estado:</strong> <span class="status status-<?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span></p>
        </div>
        
        <div class="response">
            <div class="user-info">
                <?php if ($ticket['foto_perfil']): ?>
                    <img src="<?php echo BASE_URL; ?>assets/img/users/<?php echo htmlspecialchars($ticket['foto_perfil']); ?>" alt="Foto de perfil" class="user-avatar">
                <?php endif; ?>
                <div>
                    <strong><?php echo htmlspecialchars($usuario); ?></strong>
                    <div><?php echo date('d/m/Y H:i'); ?></div>
                </div>
            </div>
            <p><?php echo nl2br(htmlspecialchars($mensaje)); ?></p>
        </div>
        
        <a href="<?php echo BASE_URL; ?><?php echo $_SESSION['user_role'] == 'admin' ? 'admin/' : ($_SESSION['user_role'] == 'tecnico' ? 'tecnico/' : 'cliente/'); ?>tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn">
            Ver Ticket
        </a>
    </div>
    
    <div class="footer">
        <p>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
    </div>
</body>
</html>