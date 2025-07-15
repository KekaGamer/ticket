<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotAdmin();

$functions = new Functions();
$estadisticas = $functions->getEstadisticasTickets();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
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
            <div class="page-header">
                <h1>Dashboard Administrador</h1>
            </div>
            
            <div class="dashboard-grid">
                <!-- Estadísticas rápidas -->
                <div class="stats-card total">
                    <h3>Total Tickets</h3>
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
                
                <div class="stats-card reabiertos">
                    <h3>Reabiertos</h3>
                    <p><?php echo $estadisticas['reabiertos']; ?></p>
                </div>
                
                <!-- Gráfico de torta -->
                <div class="chart-card">
                    <h3>Distribución de Tickets</h3>
                    <canvas id="ticketsChart"></canvas>
                </div>
                
                <!-- Últimos tickets -->
                <div class="tickets-card">
                    <h3>Últimos Tickets</h3>
                    <div class="tickets-list">
                        <?php
                        $tickets = $functions->getUltimosTickets(5);
                        foreach ($tickets as $ticket): ?>
                            <div class="ticket-item">
                                <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                                <span class="ticket-title"><?php echo $ticket['titulo']; ?></span>
                                <span class="ticket-status <?php echo $ticket['estado']; ?>"><?php echo ucfirst($ticket['estado']); ?></span>
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-view">Ver</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Configuración del gráfico
        const ctx = document.getElementById('ticketsChart').getContext('2d');
        const ticketsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Abiertos', 'Pendientes', 'Cerrados', 'Reabiertos'],
                datasets: [{
                    data: [
                        <?php echo $estadisticas['abiertos']; ?>,
                        <?php echo $estadisticas['pendientes']; ?>,
                        <?php echo $estadisticas['cerrados']; ?>,
                        <?php echo $estadisticas['reabiertos']; ?>
                    ],
                    backgroundColor: [
                        '#36a2eb',
                        '#ffcd56',
                        '#4bc0c0',
                        '#ff6384'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>