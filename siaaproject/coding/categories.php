<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: You do not have permission to access this page.");
}

$message = '';
$categoryToEdit = null;

function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $slug = create_slug($name);

    if (empty($name)) {
        $message = "Category name is required.";
    } else {
        if ($_POST['action'] === 'add_category') {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $slug);
        } elseif ($_POST['action'] === 'update_category') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $slug, $id);
        }

        if (isset($stmt) && $stmt->execute()) {
            $message = "Category " . ($_POST['action'] === 'add_category' ? 'added' : 'updated') . " successfully!";
        } else {
            $message = "Error: " . ($stmt->error ?? 'Could not perform action.');
        }
        $stmt->close();
    }
    header("Location: categories.php?message=" . urlencode($message));
    exit();
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Category deleted successfully!";
    } else {
        $message = "Error deleting category: " . $stmt->error;
    }
    $stmt->close();
    header("Location: categories.php?message=" . urlencode($message));
    exit();
}

if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $categoryToEdit = $result->fetch_assoc();
    }
    $stmt->close();
}

$sql = "SELECT * FROM categories ORDER BY name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-action { padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s; text-decoration: none; display: inline-block; }
        .btn-edit { color: #2563eb; background-color: #dbeafe; }
        .btn-edit:hover { background-color: #bfdbfe; }
        .btn-delete { color: #dc2626; background-color: #fee2e2; }
        .btn-delete:hover { background-color: #fecaca; }
        .notification { position: fixed; bottom: 1rem; right: 1rem; background-color: rgba(0, 0, 0, 0.7); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; opacity: 0; transition: opacity 0.3s ease-in-out; z-index: 1000; }
        .notification.show { opacity: 1; }
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
                <li><a href="users.php"><i class="fa-solid fa-user"></i>Users</a></li>
                <li><a href="products.php"><i class="fas fa-tags"></i>All Products</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-sitemap"></i>Categories</a></li>
                <li><a href="index1.php"><i class="fas fa-chart-line"></i>POS</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="notification" id="notification"></div>
            <div class="main-header">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1 class="text-2xl font-bold text-gray-800">Categories Management</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
                <div class="stat-card lg:col-span-1">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4"><?= $categoryToEdit ? 'Edit Category' : 'Add New Category' ?></h2>
                    <form action="categories.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="<?= $categoryToEdit ? 'update_category' : 'add_category' ?>">
                        <?php if ($categoryToEdit): ?>
                            <input type="hidden" name="id" value="<?= $categoryToEdit['id'] ?>">
                        <?php endif; ?>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-600">Category Name</label>
                            <input type="text" id="name" name="name" value="<?= $categoryToEdit ? htmlspecialchars($categoryToEdit['name']) : '' ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <?= $categoryToEdit ? 'Update Category' : 'Add Category' ?>
                            </button>
                            <?php if ($categoryToEdit): ?>
                                <a href="categories.php" class="w-full text-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="stat-card md:col-span-2">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">All Categories</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Slug</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td class="font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= htmlspecialchars($row['slug']) ?></td>
                                            <td class="actions">
                                                <a href="categories.php?edit_id=<?= $row['id'] ?>" class="btn-action btn-edit">Edit</a>
                                                <a href="categories.php?delete_id=<?= $row['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4">No categories found.</td></tr>
                                <?php endif; ?>
                                <?php $conn->close(); ?>
                            </tbody>
                        </table>
                    </div>
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
                    window.history.replaceState({}, document.title, "categories.php");
                }, 3000);
            }
        });
    </script>
</body>
</html>