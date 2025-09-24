<?php
require_once 'auth_check.php'; // Check if user is logged in
require_once 'db_connect.php';

// Role-based access control: only admins can manage users
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: You do not have permission to access this page.");
}

$message = '';

// --- GET REQUEST HANDLING (DELETE) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Call the stored procedure to delete the user and re-index the table
    $stmt = $conn->prepare("CALL delete_user_and_reindex(?)");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "User deleted and IDs re-ordered successfully!";
    } else {
        $message = "Error deleting user: " . $stmt->error;
    }
    $stmt->close();

    header("Location: users.php?message=" . urlencode($message));
    exit();
}

// --- DATA FETCHING FOR DISPLAY ---
$sql = "SELECT id, first_name, last_name, username, email, role, created_at FROM users ORDER BY id ASC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-action {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-delete {
            color: #dc2626;
            background-color: #fee2e2;
        }
        .btn-delete:hover {
            background-color: #fecaca;
        }
        .notification {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 1000;
        }
        .notification.show {
            opacity: 1;
        }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex; justify-content: center; align-items: center;
            z-index: 2000; opacity: 0; visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content {
            background-color: #fff; padding: 2rem; border-radius: 0.75rem;
            text-align: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }
        .modal-buttons { display: flex; justify-content: center; gap: 1rem; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <div id="sidebar-overlay" class="sidebar-overlay"></div>
        <nav id="sidebarMenu" class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php"><i class="fas fa-cubes"></i><span>TINDAHAN NYO</span></a>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
                <li><a href="users.php" class="active"><i class="fa-solid fa-user"></i>Users</a></li>
                <li><a href="products.php"><i class="fas fa-tags"></i>All Products</a></li>
                <li><a href="index1.php"><i class="fas fa-chart-line"></i>POS</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="notification" id="notification"></div>
            <div class="modal-overlay" id="confirm-modal">
                <div class="modal-content">
                    <p id="confirm-text" class="text-lg font-medium mb-6"></p>
                    <div class="modal-buttons">
                        <button class="btn-action btn-delete" id="modal-confirm-btn">Delete</button>
                        <button class="btn-action bg-gray-200" id="modal-cancel-btn">Cancel</button>
                    </div>
                </div>
            </div>

            <div class="main-header">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1 class="text-2xl font-bold text-gray-800">Users Management</h1>
            </div>

            <div class="stat-card mt-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">All Users</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td class="font-medium text-gray-900"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
                                        <td class="actions">
                                            <?php if ($row['id'] != 1): // Prevent deleting admin ID 1 ?>
                                                <a href="#" class="btn-action btn-delete delete-user" data-id="<?= htmlspecialchars($row['id']) ?>" data-name="<?= htmlspecialchars($row['username']) ?>">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4">No users found.</td></tr>
                            <?php endif; ?>
                            <?php $conn->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const notification = document.getElementById('notification');
            if (message) {
                notification.textContent = decodeURIComponent(message);
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                    window.history.replaceState({}, document.title, "users.php");
                }, 3000);
            }

            const deleteButtons = document.querySelectorAll('.delete-user');
            const confirmModal = document.getElementById('confirm-modal');
            const confirmText = document.getElementById('confirm-text');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');
            let userIdToDelete = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    userIdToDelete = this.getAttribute('data-id');
                    const userName = this.getAttribute('data-name');
                    confirmText.textContent = `Are you sure you want to delete user "${userName}"?`;
                    confirmModal.classList.add('show');
                });
            });

            confirmBtn.addEventListener('click', () => {
                if (userIdToDelete) window.location.href = `users.php?delete_id=${userIdToDelete}`;
            });
            cancelBtn.addEventListener('click', () => confirmModal.classList.remove('show'));
            confirmModal.addEventListener('click', (e) => {
                if (e.target === confirmModal) confirmModal.classList.remove('show');
            });
        });
    </script>
</body>
</html>
