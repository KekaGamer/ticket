<?php
/**
 * Plantilla de correo para nuevo ticket
 * Variables disponibles:
 * - $ticket: Array con información del ticket
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ticket #<?php echo $ticket['id']; ?></title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Tickets <?php echo SITE_NAME; ?></h1>
    </div>
    
    <div class="content">
        <h2>Se ha creado un nuevo ticket</h2>
        
        <div class="ticket-info">
            <p><span class="ticket-id">Ticket #<?php echo $ticket['id']; ?></span></p>
            <p><strong>Título:</strong> <?php echo htmlspecialchars($ticket['titulo']); ?></p>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($ticket['nombre'] . ' ' . $ticket['apellido']); ?></p>
            <p><strong>Estado:</strong> <span class="status status-<?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></p>
        </div>
        
        <p><strong>Descripción:</strong></p>
        <p><?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?></p>
        
        <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn">
            Ver Ticket
        </a>
    </div>
    
    <div class="footer">
        <p>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
    </div>
</body>
</html>