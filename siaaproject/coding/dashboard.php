<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Dashboard</title>
    <!-- Link to your CSS file -->
    <link rel="stylesheet" href="styles.css">
    <!-- Chart.js for the graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts for the 'Inter' font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="sidebar-overlay"></div>

        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar">
            <div class="sidebar-header">
                <a href="#">
                    <i class="fas fa-cubes"></i>
                    <span>TINDAHAN NYO</span>
                </a>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="fa-solid fa-user"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="products.php">
                        <i class="fas fa-tags"></i>
                       All Products
                    </a>
                </li>
                <li>
                    <a href="categories.php">
                        <i class="fas fa-sitemap"></i>
                        Categories
                    </a>
                </li>
                <li>
                    <a href="index1.php">
                        <i class="fas fa-chart-line"></i>
                        POS
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="main-header">
                <!-- Hamburger button for mobile, now inside the header -->
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1>Dashboard</h1>
            </div>

            <!-- Chart -->
            <div class="stat-card h-72 sm:h-80 md:h-96 my-6">
                <canvas id="myChart" class="w-full h-full"></canvas>
            </div>

            <!-- Stat Cards -->
            <div class="stat-grid">
                <div class="stat-card">
                    <h3>Current Users</h3>
                    <p style="font-size: 2rem; font-weight: 700;">281</p>
                </div>
                <div class="stat-card">
                    <h3></h3>
                    <p style="font-size: 2rem; font-weight: 700;">15</p>
                </div>
                <div class="stat-card">
                    <h3></h3>
                    <p style="font-size: 2rem; font-weight: 700;">55,430</p>
                </div>
                <div class="stat-card">
                    <h3>Revenue</h3>
                    <p style="font-size: 2rem; font-weight: 700;">$2,345</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebarMenu');
            const toggleButton = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebar-overlay');

            function closeSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }

            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            new Chart(document.getElementById('myChart'), {
                type: 'bar',
                data: {
                    labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    datasets: [{
                        label: 'Sales',
                        data: [15000, 21500, 18500, 24000, 23500, 24200, 12500],
                        backgroundColor: '#2563EB',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>