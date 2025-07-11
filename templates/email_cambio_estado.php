<?php
/**
 * Plantilla de correo para cambio de estado de ticket
 * Variables disponibles:
 * - $ticket: Array con información del ticket
 * - $estado: Nuevo estado del ticket
 * - $comentario: Comentario opcional
 * - $usuario: Nombre del usuario que realizó el cambio
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['id']; ?> - Estado actualizado</title>
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
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-cerrado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-reabierto {
            background-color: #f8d7da;
            color: #721c24;
        }
        .comment {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Tickets <?php echo SITE_NAME; ?></h1>
    </div>
    
    <div class="content">
        <h2>Estado del ticket actualizado</h2>
        
        <div class="ticket-info">
            <p><span class="ticket-id">Ticket #<?php echo $ticket['id']; ?></span></p>
            <p><strong>Título:</strong> <?php echo htmlspecialchars($ticket['titulo']); ?></p>
            <p><strong>Nuevo Estado:</strong> <span class="status status-<?php echo $estado; ?>"><?php echo ucfirst($estado); ?></span></p>
            <p><strong>Cambiado por:</strong> <?php echo htmlspecialchars($usuario); ?></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        
        <?php if ($comentario): ?>
            <div class="comment">
                <p><strong>Comentario:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($comentario)); ?></p>
            </div>
        <?php endif; ?>
        
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