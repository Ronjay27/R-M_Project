<?php
// Include database connection
include 'Db_connection.php';

// Start session

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? null; // Get the user's role from the session

// Fetch statistics from the database
// Total Patients
$stmt = $conn->prepare("SELECT COUNT(*) AS total_patients FROM patients");
$stmt->execute();
$result = $stmt->get_result();
$total_patients = $result->fetch_assoc()['total_patients'];
$stmt->close();

// Total Pharmacists
$stmt = $conn->prepare("SELECT COUNT(*) AS total_pharmacists FROM pharmacists");
$stmt->execute();
$result = $stmt->get_result();
$total_pharmacists = $result->fetch_assoc()['total_pharmacists'];
$stmt->close();

// Total Medicines
$stmt = $conn->prepare("SELECT COUNT(*) AS total_medicines FROM inventory");
$stmt->execute();
$result = $stmt->get_result();
$total_medicines = $result->fetch_assoc()['total_medicines'];
$stmt->close();

// Total Payments
$stmt = $conn->prepare("SELECT COUNT(*) AS total_payments FROM payments");
$stmt->execute();
$result = $stmt->get_result();
$total_payments = $result->fetch_assoc()['total_payments'];
$stmt->close();

// Total Cashiers
$stmt = $conn->prepare("SELECT COUNT(*) AS total_cashiers FROM cashiers");
$stmt->execute();
$result = $stmt->get_result();
$total_cashiers = $result->fetch_assoc()['total_cashiers'];
$stmt->close();

// Total Suppliers
$stmt = $conn->prepare("SELECT COUNT(*) AS total_suppliers FROM suppliers");
$stmt->execute();
$result = $stmt->get_result();
$total_suppliers = $result->fetch_assoc()['total_suppliers'];
$stmt->close();

// Total Prescriptions
$stmt = $conn->prepare("SELECT COUNT(*) AS total_prescriptions FROM prescriptions");
$stmt->execute();
$result = $stmt->get_result();
$total_prescriptions = $result->fetch_assoc()['total_prescriptions'];
$stmt->close();

// Total Users
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users");
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total_users'];
$stmt->close();

// Total Sales
$stmt = $conn->prepare("SELECT SUM(total_price) AS total_sales FROM sales");
$stmt->execute();
$result = $stmt->get_result();
$total_sales = $result->fetch_assoc()['total_sales'];
$stmt->close();

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management System - Home</title>
    <link rel="shortcut icon" href="&.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <header class="header">
            <img src="&.png" alt="Pharmacy Management System Logo" class="logo">
            <div class="header-text">
            <h1>Welcome to R&M Pharmacy Management System</h1>
            <p>Manage patients, pharmacists, cashiers, prescriptions, inventory, suppliers, and payments efficiently.</p>
            </div>
        </header>

        <h2>Dashboard</h2>

        <nav class="navigation">
            <ul>
                <!-- Dashboard Link (Visible to All Logged-In Users) -->
                <?php if ($is_logged_in): ?>
                    <li><a href="Editrecords.php">Details</a></li>

                    <!-- Admin-Specific Links -->
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="createUser.php">Manage Users</a></li>
                        <li><a href="audit_logs.php">Audit Logs</a></li>
                    <?php endif; ?>
                    <!-- Logout Link (Visible to All Logged-In Users) -->
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <!-- Login Link (Visible to Guests) -->
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>


        <?php if ($is_logged_in): ?>
            <section class="dashboard">
                <h2>System Overview</h2>
                <div class="stats">

                    <div class="stat">
                    <img src="elderly-patient-talking-nurse-ward-600nw-2309648671.webp" alt="Patient Management" class="patient-image">
                        <div class="patients" >
                        <h3 >Total Patients</h3>
                        <p><?php echo $total_patients; ?></p>
                        </div>
                    </div>
                    <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>

                        <div class="stat">
                            <img src="medicine2.jpg" alt="Inventory Management" class="inventory-image">
                            <h3>Total Medicines</h3>
                            <p><?php echo $total_medicines; ?></p>
                        </div>
                        <div class="stat">
                            <img src="pharmacists.jpg" alt="Pharmacist Management" class="pharmacist-image">
                            <h3>Total Pharmacists</h3>
                            <p><?php echo $total_pharmacists; ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin' || $user_role === 'cashier Manager'): ?>
                        <div class="stat">
                            <img src="payment1.jpg" alt="Payment Management" class="payment-image">
                            <h3>Total Payments</h3>
                            <p> <?php echo $total_payments; ?></p>
                        </div>
                        <div class="stat">
                            <img src="cashierpayment.jpg" alt="Cashier Management" class="cashier-image">
                            <h3>Total Cashiers</h3>
                            <p><?php echo $total_cashiers; ?></p>
                        </div>
                        <div class="stat">
                            <img src="sales.jpg" alt="Sales Management" class="sales-image">
                            <h3>Total Sales</h3>
                            <p>UGX <?php echo number_format($total_sales, 2); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                        
                        <div class="stat">
                        <img src="users.jpg" alt="Users" class="user-image">
                            <h3>Total Users</h3>
                            <p><?php echo $total_users; ?></p>
                        </div>
                       
                        <div class="stat">
                            <img src="supplier2.jpg" alt="Supplier Management" class="supplier-image">
                            <h3>Total Suppliers</h3>
                            <p><?php echo $total_suppliers; ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>
                        <div class="stat">
                            <img src="prescription.jpg" alt="Prescription Management" class="prescription-image">
                            <h3>Total Prescriptions</h3>
                            <p><?php echo $total_prescriptions; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <button id="refresh-stats" onclick="refreshStats()">Refresh Statistics</button>
            </section>
        <?php else: ?>
            <p>Please <a href="login.php">login</a> to view the dashboard.</p>
        <?php endif; ?>

        <script>
            function refreshStats() {
                fetch('fetchStats.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update the statistics on the page
                        document.querySelector('.stat:nth-child(1) p').textContent = data.total_users;
                        document.querySelector('.stat:nth-child(1) p').textContent = data.total_patients;
                        if (data.total_pharmacists !== undefined) {
                            document.querySelector('.stat:nth-child(2) p').textContent = data.total_pharmacists;
                        }
                        if (data.total_medicines !== undefined) {
                            document.querySelector('.stat:nth-child(3) p').textContent = data.total_medicines;
                        }
                        if (data.total_payments !== undefined) {
                            document.querySelector('.stat:nth-child(4) p').textContent = data.total_payments;
                        }
                        if (data.total_cashiers !== undefined) {
                            document.querySelector('.stat:nth-child(5) p').textContent = data.total_cashiers;
                        }
                        if (data.total_suppliers !== undefined) {
                            document.querySelector('.stat:nth-child(6) p').textContent = data.total_suppliers;
                        }
                        if (data.total_prescriptions !== undefined) {
                            document.querySelector('.stat:nth-child(7) p').textContent = data.total_prescriptions;
                        }
                    })
                    .catch(error => console.error('Error fetching statistics:', error));
            }
        </script>

        <footer class="footer">
            <p>&copy; 2025 R&M Pharmacy Management System. All rights reserved.</p>
            <p>Developed by Ssemanda Ronald</p>
        </footer>
    </div>
</body>

</html>