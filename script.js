Chart.register(ChartDataLabels);

// Live Clock
function updateClock() {
    document.getElementById('liveClock').textContent = new Date().toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', weekday: 'long',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
    });
}
setInterval(updateClock, 1000);
updateClock();

// Pie Chart
new Chart(document.getElementById('popChart'), {
    type: 'pie',
    data: {
        labels: ['Children', 'Adult'],
        datasets: [{
            data: [childCount, adultCount],
            backgroundColor: ['#e74c3c', '#2ecc71']
        }]
    },
    options: {
        plugins: {
            legend: { position: 'right' },
            datalabels: {
                color: '#fff',
                formatter: (val, ctx) => {
                    let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    return sum > 0 ? (val * 100 / sum).toFixed(1) + "%" : "0%";
                }
            }
        }
    }
});