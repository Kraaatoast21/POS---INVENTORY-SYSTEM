<?php
require_once 'auth_check.php'; // Check if user is logged in
require_once 'db_connect.php';

// Role-based access control: only admins can manage products
if ($_SESSION['role'] !== 'admin') {
    // Or redirect to a 'permission-denied' page
    die("Access Denied: You do not have permission to access this page.");
}

$message = '';
$productToEdit = null;

// --- FILE UPLOAD HANDLER ---
function handle_photo_upload($current_image_url = '') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Delete old photo if a new one is uploaded during an update
        if (!empty($current_image_url) && file_exists($current_image_url)) {
            unlink($current_image_url);
        }

        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid('product_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            return $upload_path;
        }
    }
    return $current_image_url; // Return the old path if no new file is uploaded
}

// --- POST REQUEST HANDLING (ADD/UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $category = $_POST['category'] ?? null;

    if (empty($name) || empty($price) || !isset($quantity)) {
        $message = "All fields are required.";
    } else {
        if ($_POST['action'] === 'add_product') {
            $image_url = handle_photo_upload();
            $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, category, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiss", $name, $price, $quantity, $category, $image_url);
            if ($stmt->execute()) {
                $message = "Product added successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update_product') {
            $id = $_POST['id'];
            $current_image_url = $_POST['current_image_url'] ?? '';
            $image_url = handle_photo_upload($current_image_url);

            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, category=?, image_url=? WHERE id=?");
            $stmt->bind_param("sdissi", $name, $price, $quantity, $category, $image_url, $id);
            if ($stmt->execute()) {
                $message = "Product updated successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: products.php?message=" . urlencode($message));
    exit();
}

// --- GET REQUEST HANDLING (DELETE/EDIT) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // First, get photo URL to delete the file from the server
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['image_url']) && file_exists($row['image_url'])) {
            unlink($row['image_url']);
        }
    }
    $stmt->close();

    // Now delete the record from the database
    // Call the stored procedure to delete the product and re-index the table
    $stmt = $conn->prepare("CALL delete_product_and_reindex(?)");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Product deleted and IDs re-ordered successfully!";
    } else {
        $message = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();

    header("Location: products.php?message=" . urlencode($message));
    exit();
}

if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $productToEdit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- DATA FETCHING FOR DISPLAY ---
$sql = "SELECT * FROM products ORDER BY name ASC";
$result = $conn->query($sql);

// Fetch categories for the dropdown
$category_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $category_result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <!-- Link to your shared CSS file -->
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts for the 'Inter' font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS for utility classes -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            color: #2563eb;
            background-color: #dbeafe;
        }
        .btn-edit:hover {
            background-color: #bfdbfe;
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
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 400px;
        }
        .modal-content p {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="sidebar-overlay"></div>

        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php">
                    <i class="fas fa-cubes"></i>
                    <span>TINDAHAN NYO</span>
                </a>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard.php">
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
                    <a href="products.php" class="active">
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
            <div class="notification" id="notification"></div>
            <div class="modal-overlay" id="confirm-modal">
        <div class="modal-content">
            <p id="confirm-text">Are you sure you want to delete this product?</p>
            <div class="modal-buttons">
                <button class="btn-action btn-delete" id="modal-confirm-btn">Delete</button>
                <button class="btn-action btn-edit" id="modal-cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
            <div class="main-header">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <h1 class="text-2xl font-bold text-gray-800">Products Management</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
                <!-- Add/Edit Product Form Card -->
                <div class="stat-card lg:col-span-1">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4"><?php echo $productToEdit ? 'Edit Product' : 'Add New Product'; ?></h2>
                    <form action="products.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="<?php echo $productToEdit ? 'update_product' : 'add_product'; ?>">
                        <?php if ($productToEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $productToEdit['id']; ?>">
                            <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($productToEdit['image_url']); ?>">
                        <?php endif; ?>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-600">Product Name</label>
                            <input type="text" id="name" name="name" value="<?php echo $productToEdit ? $productToEdit['name'] : ''; ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-600">Price (P)</label>
                            <input type="number" step="0.01" id="price" name="price" value="<?php echo $productToEdit ? htmlspecialchars($productToEdit['price']) : ''; ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-600">Quantity</label>
                            <input type="number" id="quantity" name="quantity" value="<?php echo $productToEdit ? htmlspecialchars($productToEdit['quantity']) : ''; ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-600">Category</label>
                            <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                        <?= ($productToEdit && $productToEdit['category'] == $cat['slug']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="photo" class="block text-sm font-medium text-gray-600">Product Photo</label>
                            <input type="file" id="photo" name="photo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <?php echo $productToEdit ? 'Update Product' : 'Add Product'; ?>
                            </button>
                            <?php if ($productToEdit): ?>
                                <a href="products.php" class="w-full text-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Products Table Card -->
                <div class="stat-card md:col-span-2">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Current Inventory</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Category</th>
                        <th>Photo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td class='font-medium text-gray-900'>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>P" . number_format($row['price'], 2) . "</td>";
                            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                            echo "<td>" . htmlspecialchars(ucwords(str_replace('-', ' ', $row['category']))) . "</td>";
                            echo "<td>";
                            if (!empty($row['image_url']) && file_exists($row['image_url'])) {
                                echo "<img src='" . htmlspecialchars($row['image_url']) . "' alt='" . htmlspecialchars($row['name']) . "' class='product-photo'>";
                            } else {
                                echo "No Photo";
                            }
                            echo "</td>";
                            echo "<td class='actions'>";
                            echo "<a href='products.php?edit_id=" . htmlspecialchars($row['id']) . "' class='btn-action btn-edit'>Edit</a>";
                            echo "<a href='#' class='btn-action btn-delete delete-product' data-id='" . htmlspecialchars($row['id']) . "' data-name='" . htmlspecialchars($row['name']) . "'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>No products found.</td></tr>";
                    }
                    // $conn->close(); // Connection is closed later or by PHP
                    ?>
                </tbody>
            </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sidebar Logic ---
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

            // Show notification if a message is present in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const notification = document.getElementById('notification');

            if (message) {
                notification.textContent = decodeURIComponent(message);
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                    window.history.replaceState({}, document.title, "products.php");
                }, 3000);
            }

            // Custom confirmation modal for delete button
            const deleteButtons = document.querySelectorAll('.delete-product');
            const confirmModal = document.getElementById('confirm-modal');
            const confirmText = document.getElementById('confirm-text');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');
            let productIdToDelete = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    productIdToDelete = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    confirmText.textContent = `Are you sure you want to delete "${productName}"?`;
                    confirmModal.classList.add('show');
                });
            });

            confirmBtn.addEventListener('click', function() {
                if (productIdToDelete) {
                    window.location.href = `products.php?delete_id=${productIdToDelete}`;
                }
            });

            cancelBtn.addEventListener('click', function() {
                confirmModal.classList.remove('show');
                productIdToDelete = null;
            });

            // Close modal if clicking outside
            confirmModal.addEventListener('click', function(e) {
                if (e.target === confirmModal) {
                    confirmModal.classList.remove('show');
                }
            });
        });
    </script>

</body>
</html>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show notification if a message is present in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const notification = document.getElementById('notification');

            if (message) {
                notification.textContent = decodeURIComponent(message);
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                    window.history.replaceState({}, document.title, "products.php");
                }, 3000);
            }

            // Custom confirmation modal for delete button
            const deleteButtons = document.querySelectorAll('.delete-product');
            const confirmModal = document.getElementById('confirm-modal');
            const confirmText = document.getElementById('confirm-text');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');
            let productIdToDelete = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    productIdToDelete = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    confirmText.textContent = `Are you sure you want to delete "${productName}"?`;
                    confirmModal.classList.add('show');
                });
            });

            confirmBtn.addEventListener('click', function() {
                if (productIdToDelete) {
                    window.location.href = `products.php?delete_id=${productIdToDelete}`;
                }
            });

            cancelBtn.addEventListener('click', function() {
                confirmModal.classList.remove('show');
                productIdToDelete = null;
            });

            // Close modal if clicking outside
            confirmModal.addEventListener('click', function(e) {
                if (e.target === confirmModal) {
                    confirmModal.classList.remove('show');
                }
            });
        });
    </script>

</body>
</html>
