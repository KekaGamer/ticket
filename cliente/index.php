<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotCliente();

$functions = new Functions();
// La llamada a getEstadisticasTickets necesita el ID del usuario para el rol de cliente.
$estadisticas = $functions->getEstadisticasTickets($_SESSION['user_id'], 'cliente'); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Mi Dashboard</h1>
            
            <div class="dashboard-grid">
                <div class="stats-card total">
                    <h3>Mis Tickets Totales</h3>
                    <p><?php echo $estadisticas['total']; ?></p>
                </div>
                
                <div class="stats-card abiertos">
                    <h3>Abiertos</h3>
                    <p><?php echo $estadisticas['abiertos']; ?></p>
                </div>
                
                <div class="stats-card pendientes">
                    <h3>Pendientes</h3>
                    <p><?php echo $estadisticas['pendientes']; ?></p>
                </div>
                
                <div class="stats-card cerrados">
                    <h3>Cerrados</h3>
                    <p><?php echo $estadisticas['cerrados']; ?></p>
                </div>
                
                <div class="chart-card text-center" style="grid-column: span 2; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <h2>¿Necesitas Ayuda?</h2>
                    <p class="text-muted" style="max-width: 400px; margin: 1rem auto;">Haz clic aquí para ver todos nuestros servicios disponibles y crear una nueva solicitud de forma rápida y sencilla.</p>
                    <a href="catalogo.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 30px;">
                        <i class="fas fa-book-open"></i> Ir al Catálogo de Solicitudes
                    </a>
                </div>
                <div class="tickets-card" style="grid-column: span 3;">
                    <h3>Mis Tickets Recientes</h3>
                    <div class="tickets-list">
                        <?php
                        // Asegúrate de que la función getTicketsCliente exista en tu functions.php
                        $tickets = $functions->getTicketsCliente($_SESSION['user_id'], 5);
                        foreach ($tickets as $ticket): ?>
                            <div class="ticket-item">
                                <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                                <span class="ticket-title"><?php echo htmlspecialchars($ticket['titulo']); ?></span>
                                <span class="ticket-status <?php echo htmlspecialchars($ticket['estado']); ?>"><?php echo ucfirst($ticket['estado']); ?></span>
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-view">Ver</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const ctx = document.getElementById('ticketsChart');
        if(ctx) { // Añadido para evitar errores si el canvas no existe
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Abiertos', 'Pendientes', 'Cerrados', 'Reabiertos'],
                    datasets: [{
                        data: [
                            <?php echo $estadisticas['abiertos'] ?? 0; ?>,
                            <?php echo $estadisticas['pendientes'] ?? 0; ?>,
                            <?php echo $estadisticas['cerrados'] ?? 0; ?>,
                            <?php echo $estadisticas['reabiertos'] ?? 0; ?>
                        ],
                        backgroundColor: ['#36a2eb', '#ffcd56', '#4bc0c0', '#ff6384'],
                        borderWidth: 1
                    }]
                },
                options: { /* Opciones del gráfico */ }
            });
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
