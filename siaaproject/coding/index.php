<?php
// No auth_check, this is a public page
require_once 'db_connect.php'; // Connects to the database

// Fetch all available products from the database for display
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
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navbar -->
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
                <a href="login.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Login</a>
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
                <div id="transactions-list" class="absolute inset-0 overflow-y-auto pr-2 flex items-center justify-center">
                    <p class="text-center text-gray-500">Please log in to view transaction history.</p>
                </div>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="w-full md:w-96 bg-white rounded-2xl shadow-lg p-4 sm:p-6 flex flex-col">

            <!-- New Date/Time/Cashier Card inside the Cart Section -->
            <div class="bg-indigo-600 text-white rounded-xl shadow-md p-4 mb-4">
                <div class="text-sm font-medium">Cashier: <span class="font-bold" id="cashierName"></span>
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

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-400 text-center p-4 text-sm">
        <p>&copy; 2023 POS Terminal. All rights reserved.</p>
    </footer>

    <script>
        // Products are loaded from the database for display
        const products = <?php echo json_encode($products_from_db); ?>;
        const categories = <?php echo json_encode($categories_from_db); ?>;

        let cartItems = [];
        const TAX_RATE = 0.08;
        let currentCategory = 'all';
        const cashierName = 'Guest'; // Set to Guest for public view

        // UI elements
        const menuToggleBtn = document.getElementById('menu-toggle-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');
        const productsGrid = document.getElementById('products-grid');
        const cartItemsContainer = document.getElementById('cart-items');
        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const taxDisplay = document.getElementById('taxDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const processPaymentBtn = document.getElementById('process-payment-btn');
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const searchBar = document.getElementById('search-bar');
        const dashboardView = document.getElementById('dashboard-view');
        const transactionsView = document.getElementById('transactions-view');
        const transactionsList = document.getElementById('transactions-list');
        const dateTimeDisplay = document.getElementById('dateTimeDisplay');
        const dayDisplay = document.getElementById('dayDisplay');
        const cashierNameDisplay = document.getElementById('cashierName');


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

            dateTimeDisplay.textContent = `${now.toLocaleDateString('en-US', dateOptions)} ${now.toLocaleTimeString('en-US', timeOptions)}`;
            dayDisplay.textContent = now.toLocaleDateString('en-US', dayOptions);
        }

        // --- MODIFIED FOR PUBLIC PAGE: All actions requiring login show a modal ---

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarBackdrop.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarBackdrop.classList.remove('show');
        }

        menuToggleBtn.addEventListener('click', openSidebar);
        sidebarBackdrop.addEventListener('click', closeSidebar);

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

            // For the public page, only allow category filtering and toggling. Block other actions.
            const isCategoryFilter = item.classList.contains('sidebar-sub-item') || (item.dataset.category === 'all');
            const isCategoryToggle = item.id === 'categories-toggle';

            if (!isCategoryFilter && !isCategoryToggle) {
                showModal('Login Required', 'Please log in to use this feature.');
                return;
            }

            // If it's the category toggle, handle the dropdown
            if (isCategoryToggle) {
                subCategoriesMenu.classList.toggle('hidden');
                categoriesArrow.classList.toggle('rotate-180');
                return;
            }

            // If it's a category filter, update the view
            document.querySelectorAll('.sidebar-item, .sidebar-sub-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            currentCategory = item.dataset.category;
            searchBar.value = '';
            renderProducts();
        });

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

        searchBar.addEventListener('input', () => {
            sidebarItems.forEach(i => i.classList.remove('active'));

            showView('dashboard');
            const searchTerm = searchBar.value.toLowerCase();
            const filteredProducts = products.filter(product =>
                product.name.toLowerCase().includes(searchTerm)
            );
            renderProducts(filteredProducts);
        });

        function renderProducts(productsToRender = null) {
            productsGrid.innerHTML = '';
            const listToRender = productsToRender || (currentCategory === 'all'
                ? products
                : products.filter(p => p.category === currentCategory));

            listToRender.forEach(product => {
                const card = document.createElement('div');
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
                card.onclick = () => showModal('Login Required', 'Please log in or register to add items to the cart.');
                productsGrid.appendChild(card);
            });
        }

        // MODIFIED: Adding to cart is disabled
        function addToCart(productToAdd) {
            showModal('Login Required', 'Please log in or register to add items to the cart.');
        }

        function renderCart() {
            cartItemsContainer.innerHTML = '<p class="text-center text-gray-500 mt-4">Please log in to view your cart.</p>';
            subtotalDisplay.textContent = `P0.00`;
            taxDisplay.textContent = `P0.00`;
            totalDisplay.textContent = `P0.00`;
        }

        // MODIFIED: Payment is disabled
        processPaymentBtn.addEventListener('click', () => {
            showModal('Login Required', 'Please log in or register to process a payment.');
        });

        modalCloseBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        cashierNameDisplay.textContent = cashierName;
        renderProducts();
        renderCart();
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>

</html>