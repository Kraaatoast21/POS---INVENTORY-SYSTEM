<?php
require_once 'auth_check.php'; // Ensures user is logged in
require_once 'db_connect.php'; // Connects to the database

// Fetch all available products from the database
$product_result = $conn->query("SELECT id, name, price, quantity, image_url, category FROM products WHERE quantity > 0 ORDER BY name ASC");
$products_from_db = [];
if ($product_result && $product_result->num_rows > 0) {
    while ($row = $product_result->fetch_assoc()) {
        $products_from_db[] = $row;
    }
}

// Fetch categories for the sidebar
$category_result = $conn->query("SELECT name, slug FROM categories ORDER BY name ASC");
$categories_from_db = [];
if ($category_result && $category_result->num_rows > 0) {
    $categories_from_db = $category_result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive POS System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            overflow-x: hidden; /* Prevents horizontal scrollbar */
        }

        .product-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        /* Custom styles for the sidebar and main content to ensure proper overflow */
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            color: #d1d5db;
            transition: background-color 0.2s, color 0.2s;
        }

        .sidebar-sub-item {
            display: block;
            padding: 0.5rem 1.5rem 0.5rem 3rem;
            margin-bottom: 0.25rem;
            border-radius: 0.75rem;
            font-weight: 500;
            color: #9ca3af;
            transition: background-color 0.2s, color 0.2s;
        }

        .sidebar-item:hover,
        .sidebar-sub-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-item.active,
        .sidebar-sub-item.active {
            background-color: #4f46e5;
            color: white;
        }

        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            z-index: 50;
        }

        #sidebar.open {
            transform: translateX(0);
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            display: none;
        }
        .sidebar-backdrop.show {
            display: block;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 1.5rem; /* Position from the top */
            left: 50%;
            background-color: #22c55e; /* green-500 */
            color: white;
            padding: 1rem 2rem; /* Make it bigger */
            border-radius: 9999px; /* Pill shape */
            display: flex; /* For icon alignment */
            align-items: center; /* For icon alignment */
            font-size: 1.125rem; /* Larger text */
            font-weight: 600;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            opacity: 0;
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            transform: translateX(-50%) translateY(-50px); /* Start off-screen */
            z-index: 1000;
        }
        .notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0); /* Slide into view */
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-indigo-700 text-white shadow-md p-4">
        <div class="w-full flex items-center justify-between">
            <!-- Left Side: Hamburger and Title -->
            <div class="flex items-center">
                <button id="menu-toggle-btn" class="text-white focus:outline-none mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>

            <!-- Right Side: Search and Logout -->
            <div class="flex items-center gap-4">
                <!-- Search Bar -->
                <div class="relative">
                    <input id="search-bar" type="text" placeholder="Search..." class="w-32 sm:w-64 py-2 pl-10 pr-4 rounded-full text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all duration-200">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar container with a fixed position -->
    <div id="sidebar" class="fixed top-0 left-0 h-full w-64 md:w-80 bg-gray-800 text-gray-300 p-4 flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <div class="text-lg font-bold text-white">Menu</div>
        </div>
        <nav class="flex-grow">
            <a href="#" class="sidebar-item active" data-view="dashboard" data-category="all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboard.php" class="sidebar-item">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v10H5V5z" clip-rule="evenodd" />
                        <path d="M7 7h6v2H7V7z" />
                    </svg>
                    <span>Inventory</span>
                </a>
            <?php endif; ?>
            <a href="#" class="sidebar-item" data-view="transactions">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Transactions</span>
            </a>
            <!-- Categories Section -->
            <div class="my-2">
                <a href="#" class="sidebar-item" id="categories-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7z" />
                        <path fill-rule="evenodd" d="M3 6a1 1 0 011-1h12a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V6z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>Categories</span>
                    <svg id="categories-arrow" class="h-5 w-5 ml-auto transform transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </a>
                <!-- Sub-categories (initially hidden) -->
                <div id="sub-categories-menu" class="hidden">
                </div>
            </div>
        </nav>
    </div>
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

    <!-- Main Content Area -->
    <div id="main-content"
        class="flex-grow p-2 sm:p-4 space-y-4 md:space-y-0 md:space-x-4 flex flex-col md:flex-row">
        <!-- Products Section (Initially visible) -->
        <div id="dashboard-view" class="bg-white rounded-2xl shadow-lg p-2 sm:p-6 flex-grow overflow-hidden flex flex-col min-h-[400px] md:min-h-0">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Products</h2>
            <div class="relative flex-grow overflow-hidden">
                <div id="products-grid"
                    class="absolute inset-0 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 overflow-y-auto pr-2">
                    <!-- Product cards will be generated here -->
                </div>
            </div>
        </div>

        <!-- Transactions Section (Initially hidden) -->
        <div id="transactions-view"
            class="bg-white rounded-2xl shadow-lg p-6 flex-grow overflow-hidden flex-col hidden">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Transaction History</h2>
            <div class="relative flex-grow overflow-hidden">
                <div id="transactions-list" class="absolute inset-0 overflow-y-auto pr-2">
                    <!-- Transactions will be rendered here -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Transaction rows will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="w-full md:w-96 bg-white rounded-2xl shadow-lg p-4 sm:p-6 flex flex-col">

            <!-- New Date/Time/Cashier Card inside the Cart Section -->
            <div class="bg-indigo-600 text-white rounded-xl shadow-md p-4 mb-4">
                <div class="text-sm font-medium"><span class="font-bold" id="cashierName"></span>
                </div>
                <div class="flex flex-col mt-2">
                    <div class="text-sm font-semibold" id="dayDisplay"></div>
                    <div class="text-xl font-bold" id="dateTimeDisplay"></div>
                </div>
            </div>

            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Current Order</h2>

            <div id="cart-items" class="flex-grow overflow-y-auto border-b border-gray-200 pb-4">
                <!-- Cart items will be generated here -->
            </div>

            <div class="mt-4 text-lg font-medium text-gray-700 space-y-2">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span id="subtotalDisplay" class="font-bold text-gray-900">$0.00</span>
                </div>
                <div class="flex justify-between">
                    <span>Tax (8%):</span>
                    <span id="taxDisplay" class="font-bold text-gray-900">$0.00</span>
                </div>
                <div class="flex justify-between text-xl font-bold text-gray-900 pt-2 border-t border-gray-200">
                    <span>Total:</span>
                    <span id="totalDisplay">$0.00</span>
                </div>
            </div>

            <button id="process-payment-btn"
                class="mt-6 w-full py-3 bg-indigo-500 text-white rounded-full font-bold text-lg shadow-lg hover:bg-indigo-600 transition-colors">
                Process Payment
            </button>

            <!-- Custom Modal UI for messages -->
            <div id="modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full text-center">
                    <h3 id="modal-title" class="text-xl font-bold mb-4"></h3>
                    <p id="modal-message" class="text-gray-600 mb-6"></p>
                    <button id="modal-close-btn"
                        class="w-full py-2 bg-indigo-500 text-white rounded-full font-semibold hover:bg-indigo-600">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification placeholder -->
    <div class="notification" id="notification"></div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-400 text-center p-4 text-sm">
        <p>&copy; 2023 POS Terminal. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const products = <?php echo json_encode($products_from_db); ?>;
        const categories = <?php echo json_encode($categories_from_db); ?>;

        // This will be populated from the database in a future step
        let transactionHistory = [];

        let cartItems = [];
        const TAX_RATE = 0.08;
        let currentCategory = 'all';
        const cashierName = '<?php 
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo 'Admin: ' . htmlspecialchars($_SESSION['first_name']) . ' ' . htmlspecialchars($_SESSION['last_name']);
            } else {
                echo 'Cashier: ' . htmlspecialchars($_SESSION['first_name']) . ' ' . htmlspecialchars($_SESSION['last_name']);
            }
        ?>'; // Get cashier name from session

        // UI elements
        const menuToggleBtn = document.getElementById('menu-toggle-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');
        const cartItemsContainer = document.getElementById('cart-items');
        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const taxDisplay = document.getElementById('taxDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const processPaymentBtn = document.getElementById('process-payment-btn');
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const notification = document.getElementById('notification');
        const searchBar = document.getElementById('search-bar');
        const productsGrid = document.getElementById('products-grid');

        const dashboardView = document.getElementById('dashboard-view');
        const transactionsView = document.getElementById('transactions-view');
        const transactionTableBody = document.getElementById('transaction-table-body');

        // New elements for date, time, and cashier
        const dateTimeDisplay = document.getElementById('dateTimeDisplay');
        const dayDisplay = document.getElementById('dayDisplay');
        const cashierNameDisplay = document.getElementById('cashierName');


        // New category elements
        const categoriesToggle = document.getElementById('categories-toggle');
        const subCategoriesMenu = document.getElementById('sub-categories-menu');
        const categoriesArrow = document.getElementById('categories-arrow');
        const sidebarItems = document.querySelectorAll('.sidebar-item, .sidebar-sub-item');

        // Function to show a custom modal
        function showModal(title, message) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modal.classList.remove('hidden');
        }

        // Function to update the date and time
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const dayOptions = { weekday: 'long' };

            const formattedDate = now.toLocaleDateString('en-US', dateOptions);
            const formattedTime = now.toLocaleTimeString('en-US', timeOptions);
            const formattedDay = now.toLocaleDateString('en-US', dayOptions);

            dateTimeDisplay.textContent = `${formattedDate} ${formattedTime}`;
            dayDisplay.textContent = formattedDay;
        }

        // Function to open the sidebar
        function openSidebar() {
            sidebar.classList.add('open');
            sidebarBackdrop.classList.add('show');
        }

        // Function to close the sidebar
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarBackdrop.classList.remove('show');
        }

        menuToggleBtn.addEventListener('click', openSidebar);
        sidebarBackdrop.addEventListener('click', closeSidebar);

        // Function to show/hide views
        function showView(viewName) {
            dashboardView.classList.add('hidden');
            transactionsView.classList.add('hidden');

            if (viewName === 'dashboard') {
                dashboardView.classList.remove('hidden');
                dashboardView.classList.add('flex');
            } else if (viewName === 'transactions') {
                transactionsView.classList.remove('hidden');
                transactionsView.classList.add('flex');
            }
        }

        // Dynamically build category sub-menu
        categories.forEach(cat => {
            const link = document.createElement('a');
            link.href = '#';
            link.className = 'sidebar-sub-item';
            link.dataset.view = 'dashboard';
            link.dataset.category = cat.slug;
            link.textContent = cat.name;
            subCategoriesMenu.appendChild(link);
        });

        // Use event delegation on the sidebar to handle clicks on static and dynamic items
        sidebar.addEventListener('click', (e) => {
            const item = e.target.closest('.sidebar-item, .sidebar-sub-item');
            if (!item) return; // Exit if the click was not on a menu item

            e.preventDefault();

            const href = item.getAttribute('href');
            if (href && href !== '#') {
                window.location.href = href;
                return;
            }

            // If the item is the main category toggle, just handle the dropdown
            if (item.id === 'categories-toggle') {
                subCategoriesMenu.classList.toggle('hidden');
                categoriesArrow.classList.toggle('rotate-180');
                return;
            }

            // If the item is the main category toggle, just handle the dropdown and exit
            if (item.id === 'categories-toggle') {
                return;
            }

            // For any other item, close the sidebar
            closeSidebar();

            // --- Handle active state and view switching ---
            // Remove active class from all items first
            document.querySelectorAll('.sidebar-item, .sidebar-sub-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            const view = item.dataset.view;
            const category = item.dataset.category;

            showView(view);

            if (view === 'dashboard' && category) {
                currentCategory = category;
                searchBar.value = '';
                renderProducts();
            } else if (view === 'transactions') {
                renderTransactions();
            }
        });

        // Event listener for the search bar
        searchBar.addEventListener('input', () => {
            sidebarItems.forEach(i => i.classList.remove('active'));

            showView('dashboard');
            const searchTerm = searchBar.value.toLowerCase();
            const filteredProducts = products.filter(product =>
                product.name.toLowerCase().includes(searchTerm)
            );
            renderProducts(filteredProducts);
        });

        // Function to render products
        function renderProducts(productsToRender = null) {
            productsGrid.innerHTML = '';

            const listToRender = productsToRender || (currentCategory === 'all'
                ? products
                : products.filter(p => p.category === currentCategory));

            listToRender.forEach(product => {
                const card = document.createElement('div');
                // Ensure product properties are treated as the correct type
                product.id = parseInt(product.id);
                product.price = parseFloat(product.price);
                product.quantity = parseInt(product.quantity);

                card.className = 'product-card bg-white p-3 rounded-xl shadow-md flex flex-row items-center text-left sm:flex-col sm:p-4 sm:text-center';
                card.innerHTML = `
                    <img src="${product.image_url || 'https://placehold.co/150x150/e2e8f0/a0aec0?text=N/A'}" alt="${product.name}" class="rounded-lg w-16 h-16 sm:w-full sm:h-auto aspect-square object-cover mr-4 sm:mr-0 sm:mb-2">
                    <div class="flex-grow">
                        <span class="font-semibold text-gray-800 w-full truncate">${product.name}</span>
                        <div class="flex flex-col sm:items-center mt-1 text-sm">
                            <span class="text-gray-800 font-bold">P${product.price.toFixed(2)}</span>
                            <span class="text-xs font-medium text-gray-500 mt-0.5">Stock: ${product.quantity}</span>
                        </div>
                    </div>
                `;
                card.onclick = () => addToCart(product);
                productsGrid.appendChild(card);
            });
        }

        // Function to render transactions
        function renderTransactions() {
            transactionTableBody.innerHTML = '';
            transactionHistory.forEach(transaction => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-100 transition-colors';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${transaction.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${transaction.date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">P${transaction.total.toFixed(2)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="editTransaction('${transaction.id}')" class="text-indigo-600 hover:text-indigo-900 transition-colors">Edit</button>
                    </td>
                `;
                transactionTableBody.appendChild(row);
            });
        }

        // Function to handle editing a past transaction
        function editTransaction(transactionId) {
            // Find the transaction to edit
            const transactionToEdit = transactionHistory.find(t => t.id === transactionId);

            if (transactionToEdit) {
                // Set the cart to the items from the selected transaction
                cartItems = JSON.parse(JSON.stringify(transactionToEdit.items)); // Deep copy to avoid reference issues

                // Switch to the dashboard view and render the cart
                showView('dashboard');
                renderCart();
            } else {
                showModal('Error', 'Transaction not found.');
            }
        }


        // Function to add an item to the cart
        function addToCart(productToAdd) {
            const existingItem = cartItems.find(item => item.id === productToAdd.id);

            if (existingItem) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.price;
            } else {
                cartItems.push({
                    id: productToAdd.id,
                    name: productToAdd.name,
                    price: productToAdd.price,
                    quantity: 1,
                    total: productToAdd.price
                });
            }

            renderCart();
        }

        // Function to update item quantity in the cart
        function updateQuantity(itemId, change) {
            const idToUpdate = parseInt(itemId); // Convert string ID to a number
            const item = cartItems.find(i => i.id === idToUpdate);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeItem(idToUpdate); // Pass the numeric ID
                    return; // Stop the function here after removing the item
                } else {
                    item.total = item.quantity * item.price;
                    renderCart();
                }
            }
        }
        window.updateQuantity = updateQuantity; // Make it globally accessible

        // Function to remove an item from the cart
        function removeItem(itemId) {
            const idToRemove = parseInt(itemId, 10); // Always specify radix
            cartItems = cartItems.filter(i => i.id !== idToRemove);
            renderCart();
        }
        window.removeItem = removeItem; // Make it globally accessible

        // Function to render the cart
        function renderCart() {
            cartItemsContainer.innerHTML = '';
            let subtotal = 0;

            if (cartItems.length === 0) {
                cartItemsContainer.innerHTML = '<p class="text-center text-gray-500 mt-4">Cart is empty.</p>';
            }

            cartItems.forEach(item => {
                subtotal += item.total;
                const cartItem = document.createElement('div');
                cartItem.className = 'flex justify-between items-center py-2 border-b border-gray-100';
                cartItem.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-800">${item.name}</span>
                        <div class="flex items-center text-sm text-gray-500">
                            <button onclick="updateQuantity(${item.id}, -1)" class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center font-bold pb-1">-</button>
                            <span class="mx-2 font-semibold">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors flex items-center justify-center font-bold pb-1">+</button>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="font-semibold text-gray-900">P${item.total.toFixed(2)}</span>
                        <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700 transition-colors text-xs font-semibold uppercase">Remove</button>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItem);
            });

            const tax = subtotal * TAX_RATE;
            const total = subtotal + tax;

            subtotalDisplay.textContent = `P${subtotal.toFixed(2)}`;
            taxDisplay.textContent = `P${tax.toFixed(2)}`;
            totalDisplay.textContent = `P${total.toFixed(2)}`;
        }

        // Function to process payment and clear the cart
        processPaymentBtn.addEventListener('click', async () => {
            if (cartItems.length === 0) {
                showModal('Cart Empty', 'Please add items to the cart before processing payment.');
                return;
            }

            const saleData = {
                action: 'process_sale',
                total_amount: parseFloat(totalDisplay.textContent.replace('P', '')),
                items: cartItems
            };

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });
                const result = await response.json();

                if (result.success) {
                    cartItems = [];
                    renderCart();
                    showModal('Payment Successful!', 'Transaction processed successfully. The page will now reload.');
                    setTimeout(() => window.location.reload(), 2000); // Reload to show updated quantities
                } else {
                    showModal('Error', result.message || 'Could not process payment.');
                }
            } catch (error) {
                showModal('Connection Error', 'Could not connect to the server to process the sale.');
            }
        });

        modalCloseBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Set the cashier name on load
        cashierNameDisplay.textContent = cashierName;
        
        // Initial rendering of products and cart
        renderProducts();
        renderCart();

        // Update date and time on load and every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');

        if (message && notification) {
            notification.innerHTML = `<i class="fas fa-check-circle mr-3"></i> ${decodeURIComponent(message)}`;
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                window.history.replaceState({}, document.title, "index1.php");
            }, 4000);
        }
    });
    </script>
</body>

</html>
