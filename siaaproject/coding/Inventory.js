document.addEventListener('DOMContentLoaded', () => {
    // --- Logic for Dashboard Page ---
    const sidebar = document.getElementById('sidebarMenu');
    const chartCanvas = document.getElementById('myChart');

    // --- Sidebar Toggle Logic (for any page with a sidebar) ---
    if (sidebar) {
        const toggleButton = document.getElementById('sidebarToggle') || document.getElementById('menu-toggle-btn'); // Find any toggle button
        const overlay = document.getElementById('sidebar-overlay') || document.getElementById('sidebar-backdrop'); // Find any overlay

        function closeSidebar() {
            sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });

        if (overlay) overlay.addEventListener('click', closeSidebar);

    }

    // --- Chart Logic (only for the dashboard page) ---
    if (chartCanvas) {
        // Chart.js configuration
        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: [{
                    label: 'Sales',
                    data: [15000, 21500, 18500, 24000, 23500, 24200, 12500],
                    backgroundColor: '#2563EB',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});