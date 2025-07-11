/**
 * Funciones para manejar gráficos en el sistema
 */

// Inicializar gráfico de tickets
function initTicketsChart(ctx, data) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Abiertos', 'Pendientes', 'Cerrados', 'Reabiertos'],
            datasets: [{
                data: [
                    data.abiertos || 0,
                    data.pendientes || 0,
                    data.cerrados || 0,
                    data.reabiertos || 0
                ],
                backgroundColor: [
                    '#36a2eb', // Azul para abiertos
                    '#ffcd56', // Amarillo para pendientes
                    '#4bc0c0', // Verde para cerrados
                    '#ff6384'  // Rojo para reabiertos
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
}

// Inicializar gráfico de barras para tickets por mes
function initMonthlyTicketsChart(ctx, data) {
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const currentMonth = new Date().getMonth();
    const labels = [];
    const abiertosData = [];
    const cerradosData = [];
    
    // Preparar datos para los últimos 6 meses
    for (let i = 5; i >= 0; i--) {
        const monthIndex = (currentMonth - i + 12) % 12;
        labels.push(months[monthIndex]);
        
        const monthData = data.find(item => item.mes === monthIndex + 1);
        abiertosData.push(monthData ? monthData.abiertos : 0);
        cerradosData.push(monthData ? monthData.cerrados : 0);
    }
    
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Tickets Abiertos',
                    backgroundColor: '#36a2eb',
                    data: abiertosData
                },
                {
                    label: 'Tickets Cerrados',
                    backgroundColor: '#4bc0c0',
                    data: cerradosData
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: false,
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Actualizar gráfico con nuevos datos
function updateChart(chart, newData) {
    chart.data.datasets[0].data = [
        newData.abiertos || 0,
        newData.pendientes || 0,
        newData.cerrados || 0,
        newData.reabiertos || 0
    ];
    chart.update();
}

// Cargar datos de estadísticas via AJAX
function loadChartData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error al cargar datos:', error));
}

// Inicializar todos los gráficos en la página
function initAllCharts() {
    // Gráfico principal de tickets
    const ticketsChartCtx = document.getElementById('ticketsChart');
    if (ticketsChartCtx) {
        const initialData = {
            abiertos: parseInt(ticketsChartCtx.dataset.abiertos) || 0,
            pendientes: parseInt(ticketsChartCtx.dataset.pendientes) || 0,
            cerrados: parseInt(ticketsChartCtx.dataset.cerrados) || 0,
            reabiertos: parseInt(ticketsChartCtx.dataset.reabiertos) || 0
        };
        
        const chart = initTicketsChart(ticketsChartCtx, initialData);
        
        // Si hay una URL para actualización, configurar intervalo
        if (ticketsChartCtx.dataset.updateUrl) {
            setInterval(() => {
                loadChartData(ticketsChartCtx.dataset.updateUrl, data => {
                    updateChart(chart, data);
                });
            }, 30000); // Actualizar cada 30 segundos
        }
    }
    
    // Gráfico de tickets por mes (si existe)
    const monthlyChartCtx = document.getElementById('monthlyTicketsChart');
    if (monthlyChartCtx && monthlyChartCtx.dataset.statsUrl) {
        loadChartData(monthlyChartCtx.dataset.statsUrl, data => {
            initMonthlyTicketsChart(monthlyChartCtx, data);
        });
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', initAllCharts);