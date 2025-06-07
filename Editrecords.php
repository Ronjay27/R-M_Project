<?php
ob_start(); // Start output buffering
// Include database connection
include 'Db_connection.php';


// Start session and check user role
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to access this page.'); window.location.href='login.php';</script>";
    exit();
}

$user_role = $_SESSION['role'] ?? null; // Get the user's role

// Function to log audit actions
function logAudit($conn, $user_id, $action) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Error: " . $conn->error);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pharmacists</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="header">
        <img src="&.png" alt="Pharmacy Management System Logo" class="logo">
        <div class="header-text">
            <h1>Welcome to R&M Pharmacy Management System</h1>
            <p>Manage patients, pharmacists, cashiers, prescriptions, inventory, suppliers, and payments efficiently.</p>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="navigation">
        
        <ul>
            <li><a href="index.php#dashboard">Dashboard</a></li>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="createUser.php">Manage Users</a></li>
                <li><a href="audit_logs.php">Audit Logs</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <!-- Users Section -->
    <?php if ($user_role === 'admin'): ?>
        <div class="form-section">
            <img src="users.jpg" alt="Users" class="user-image">
            <h2>Users</h2>
            <!-- Display Users -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM users");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['username']}</td>
                                    <td>{$row['role']}</td>
                                    
                                  </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>



    <div class="patient-container">
        <h3>Manage Patients</h3>

        <img src="elderly-patient-talking-nurse-ward-600nw-2309648671.webp" alt="Patient Management" class="patient-image">

        <?php
        // Initialize variables for editing patients
        $edit_patient_mode = false;
        $edit_patient_id = null;
        $edit_patient_name = '';
        $edit_patient_contact = '';
        $edit_patient_address = '';

        // Check if editing a patient
        if (isset($_GET['edit_patient']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
            $edit_patient_mode = true;
            $edit_patient_id = $_GET['edit_patient'];

            // Fetch patient details for editing
            $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->bind_param("i", $edit_patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_patient_name = $row['name'];
                $edit_patient_age = $row['age'];
                $edit_patient_gender = $row['gender'];
                $edit_patient_contact = $row['contact'];
                $edit_patient_address = $row['address'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_patient_mode ? '?edit_patient=' . $edit_patient_id : ''; ?>">
                <input type="text" name="patient_name" placeholder="Patient Name" value="<?php echo htmlspecialchars($edit_patient_name); ?>" required>
                <input type="number" name="patient_age" placeholder="Age" value="<?php echo isset($edit_patient_age) ? htmlspecialchars($edit_patient_age) : ''; ?>" required>
                <select name="patient_gender" required>
                    <option value="">Select</option>
                    <option value="Male" <?php echo (isset($edit_patient_gender) && $edit_patient_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($edit_patient_gender) && $edit_patient_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
                <input type="number" name="patient_contact" placeholder="Contact" value="<?php echo htmlspecialchars($edit_patient_contact); ?>" required>
                <textarea name="patient_address" placeholder="Address" required><?php echo htmlspecialchars($edit_patient_address); ?></textarea>
                <button type="submit" name="<?php echo $edit_patient_mode ? 'update_patient' : 'create_patient'; ?>">
                    <?php echo $edit_patient_mode ? 'Update Patient' : 'Add Patient'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for patients
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_patient']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new patient: " . htmlspecialchars($_POST['patient_name']));
                // Add new patient
                $patient_name = trim($_POST['patient_name']);
                $patient_age = trim($_POST['patient_age']);
                $patient_gender = trim($_POST['patient_gender']);
                $patient_contact = trim($_POST['patient_contact']);
                $patient_address = trim($_POST['patient_address']);

                $stmt = $conn->prepare("INSERT INTO patients (name, age, gender, contact, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $patient_name, $patient_age, $patient_gender, $patient_contact, $patient_address);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Patient added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding patient.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_patient']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated patient ID: " . htmlspecialchars($edit_patient_id));
                // Update existing patient
                $patient_name = trim($_POST['patient_name']);
                $patient_age = trim($_POST['patient_age']);
                $patient_gender = trim($_POST['patient_gender']);
                $patient_contact = trim($_POST['patient_contact']);
                $patient_address = trim($_POST['patient_address']);

                $stmt = $conn->prepare("UPDATE patients SET name = ?, age = ?, gender = ?, contact = ?, address = ? WHERE id = ?");
                $stmt->bind_param("sisssi", $patient_name, $patient_age, $patient_gender, $patient_contact, $patient_address, $edit_patient_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Patient updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating patient.</div>";
                }
                $stmt->close();
            }
        }

        // Handle Delete Patient
        if (isset($_GET['action']) && $_GET['action'] === 'delete_patient' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted patient ID: " . htmlspecialchars($_GET['id']));
            $patient_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
            $stmt->bind_param("i", $patient_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Patient deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting patient.</div>";
            }
            $stmt->close();
        }
        ?>

        <div class="patient-list">
            <h3>Existing Patients</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT id, name, age, gender, contact, address FROM patients");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['age']}</td>
                                <td>{$row['gender']}</td>
                                <td>{$row['contact']}</td>
                                <td>{$row['address']}</td>
                                <td>";
                        if ($user_role === 'admin' || $user_role === 'pharmacist Manager') {
                            echo "<a href='Editrecords.php?edit_patient={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_patient&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this patient?');\">Delete</a>";
                        }
                        echo "</td>
                              </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pharmacist-container">
        <h2>Manage Pharmacists</h2>

        <img src="pharmacists.jpg" alt="Pharmacist Management" class="pharmacist-image">

        <?php
        // Initialize variables for editing
        $edit_mode = false;
        $edit_pharmacist_id = null;
        $edit_name = '';
        $edit_licence_no = '';
        $edit_contact = '';
        $edit_email = '';
        $edit_shift_time = '';

        // Check if editing a pharmacist
        if (isset($_GET['edit']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
            $edit_mode = true;
            $edit_pharmacist_id = $_GET['edit'];

            // Fetch pharmacist details for editing
            $stmt = $conn->prepare("SELECT * FROM pharmacists WHERE id = ?");
            $stmt->bind_param("i", $edit_pharmacist_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_name = $row['name'];
                $edit_licence_no = $row['Licence_No'];
                $edit_contact = $row['contact'];
                $edit_email = $row['email'];
                $edit_shift_time = $row['shift_time'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_mode ? '?edit=' . $edit_pharmacist_id : ''; ?>">
                <input type="text" name="pharmacist_name" placeholder="Pharmacist Name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                <input type="number" name="pharmacist_Licence_No" placeholder="Licence No" value="<?php echo htmlspecialchars($edit_licence_no); ?>" required>
                <input type="number" name="pharmacist_contact" placeholder="Contact" value="<?php echo htmlspecialchars($edit_contact); ?>" required>
                <input type="email" name="pharmacist_email" placeholder="Email" value="<?php echo htmlspecialchars($edit_email); ?>" required>
                <input type="text" name="shift_time" placeholder="Shift Time" value="<?php echo htmlspecialchars($edit_shift_time); ?>">
                <button type="submit" name="<?php echo $edit_mode ? 'update_pharmacist' : 'create_pharmacist'; ?>">
                    <?php echo $edit_mode ? 'Update Pharmacist' : 'Add Pharmacist'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_pharmacist']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new pharmacist: " . htmlspecialchars($_POST['pharmacist_name']));
                // Add new pharmacist
                $name = trim($_POST['pharmacist_name']);
                $licence_no = trim($_POST['pharmacist_Licence_No']);
                $contact = trim($_POST['pharmacist_contact']);
                $email = trim($_POST['pharmacist_email']);
                $shift_time = trim($_POST['shift_time']);

                $stmt = $conn->prepare("INSERT INTO pharmacists (name, Licence_No, contact, email, shift_time) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $licence_no, $contact, $email, $shift_time);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Pharmacist added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding pharmacist.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_pharmacist']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated pharmacist ID: " . htmlspecialchars($edit_pharmacist_id));
                // Update existing pharmacist
                $name = trim($_POST['pharmacist_name']);
                $licence_no = trim($_POST['pharmacist_Licence_No']);
                $contact = trim($_POST['pharmacist_contact']);
                $email = trim($_POST['pharmacist_email']);
                $shift_time = trim($_POST['shift_time']);

                $stmt = $conn->prepare("UPDATE pharmacists SET name = ?, Licence_No = ?, contact = ?, email = ?, shift_time = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $licence_no, $contact, $email, $shift_time, $edit_pharmacist_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Pharmacist updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating pharmacist.</div>";
                }
                $stmt->close();
            }
        }

        // Handle Delete Pharmacist
        if (isset($_GET['action']) && $_GET['action'] === 'delete_pharmacist' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted pharmacist ID: " . htmlspecialchars($_GET['id']));
            $id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM pharmacists WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Pharmacist deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting pharmacist.</div>";
            }
            $stmt->close();
        }
        ?>

        <div class="pharmacist-list">
            <h3>Existing Pharmacists</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Licence No</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Shift Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM pharmacists");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['name']}</td>
                                <td>{$row['Licence_No']}</td>
                                <td>{$row['contact']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['shift_time']}</td>
                                <td>";
                        if ($user_role === 'admin' || $user_role === 'pharmacist Manager') {
                            echo "<a href='Editrecords.php?edit={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_pharmacist&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this pharmacist?');\">Delete</a>";
                        }
                        echo "</td>
                              </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cashiers Section -->
    <div class="cashier-container">
        <h2>Manage Cashiers</h2>
        <img src="cashierpayment.jpg" alt="Cashier Management" class="cashier-image">
        <?php
        // Initialize variables for editing cashiers
        $edit_cashier_mode = false;
        $edit_cashier_id = null;
        $edit_cashier_name = '';
        $edit_cashier_contact = '';
        $edit_cashier_email = '';
        $edit_cashier_address = '';

        // Check if editing a cashier
        if (isset($_GET['edit_cashier']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
            $edit_cashier_mode = true;
            $edit_cashier_id = $_GET['edit_cashier'];

            // Fetch cashier details for editing
            $stmt = $conn->prepare("SELECT * FROM cashiers WHERE id = ?");
            $stmt->bind_param("i", $edit_cashier_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_cashier_name = $row['name'];
                $edit_cashier_contact = $row['contact'];
                $edit_cashier_email = $row['email'];
                $edit_cashier_address = $row['address'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'cashier Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_cashier_mode ? '?edit_cashier=' . $edit_cashier_id : ''; ?>">
                <input type="text" name="cashier_name" placeholder="Cashier Name" value="<?php echo htmlspecialchars($edit_cashier_name); ?>" required>
                <input type="number" name="cashier_contact" placeholder="Contact" value="<?php echo htmlspecialchars($edit_cashier_contact); ?>" required>
                <input type="email" name="cashier_email" placeholder="Email" value="<?php echo htmlspecialchars($edit_cashier_email); ?>" required>
                <textarea name="cashier_address" placeholder="Address" required><?php echo htmlspecialchars($edit_cashier_address); ?></textarea>
                <button type="submit" name="<?php echo $edit_cashier_mode ? 'update_cashier' : 'create_cashier'; ?>">
                    <?php echo $edit_cashier_mode ? 'Update Cashier' : 'Add Cashier'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for cashiers
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_cashier']) && ($user_role === 'admin' || $user_role === 'cashier Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new cashier: " . htmlspecialchars($_POST['cashier_name']));
                // Add new cashier
                $cashier_name = trim($_POST['cashier_name']);
                $cashier_contact = trim($_POST['cashier_contact']);
                $cashier_email = trim($_POST['cashier_email']);
                $cashier_address = trim($_POST['cashier_address']);

                $stmt = $conn->prepare("INSERT INTO cashiers (name, contact, email, address) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $cashier_name, $cashier_contact, $cashier_email, $cashier_address);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Cashier added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding cashier.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_cashier']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated cashier ID: " . htmlspecialchars($edit_cashier_id));
                // Update existing cashier
                $cashier_name = trim($_POST['cashier_name']);
                $cashier_contact = trim($_POST['cashier_contact']);
                $cashier_email = trim($_POST['cashier_email']);
                $cashier_address = trim($_POST['cashier_address']);

                $stmt = $conn->prepare("UPDATE cashiers SET name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $cashier_name, $cashier_contact, $cashier_email, $cashier_address, $edit_cashier_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Cashier updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating cashier.</div>";
                }
                $stmt->close();
            }
        }

        // Handle Delete Cashier
        if (isset($_GET['action']) && $_GET['action'] === 'delete_cashier' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted cashier ID: " . htmlspecialchars($_GET['id']));
            $cashier_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM cashiers WHERE id = ?");
            $stmt->bind_param("i", $cashier_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Cashier deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting cashier.</div>";
            }
            $stmt->close();
        }
        ?>

        <div class="cashier-list">
            <h3>Existing Cashiers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cashier ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM cashiers");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['name']}</td>
                                    <td>{$row['contact']}</td>
                                    <td>{$row['email']}</td>
                                    <td>{$row['address']}</td>
                                    <td>";
                        if ($user_role === 'admin' || $user_role === 'cashier Manager') {
                            echo "<a href='Editrecords.php?edit_cashier={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_cashier&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this cashier?');\">Delete</a>";
                        }
                        echo "</td>
                                  </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Suppliers Section -->
    <div class="supplier-container">
        <h2>Manage Suppliers</h2>
        <img src="supplier2.jpg" alt="Supplier Management" class="supplier-image">
        <?php
        // Initialize variables for editing suppliers
        $edit_supplier_mode = false;
        $edit_supplier_id = null;
        $edit_supplier_name = '';
        $edit_supplier_contact = '';
        $edit_supplier_email = '';
        $edit_supplier_address = '';

        // Check if editing a supplier
        if (isset($_GET['edit_supplier']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
            $edit_supplier_mode = true;
            $edit_supplier_id = $_GET['edit_supplier'];

            // Fetch supplier details for editing
            $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->bind_param("i", $edit_supplier_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_supplier_name = $row['name'];
                $edit_supplier_contact = $row['contact'];
                $edit_supplier_email = $row['email'];
                $edit_supplier_address = $row['address'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_supplier_mode ? '?edit_supplier=' . $edit_supplier_id : ''; ?>">
                <input type="text" name="supplier_name" placeholder="Supplier Name" value="<?php echo htmlspecialchars($edit_supplier_name); ?>" required>
                <input type="number" name="supplier_contact" placeholder="Contact" value="<?php echo htmlspecialchars($edit_supplier_contact); ?>" required>
                <input type="email" name="supplier_email" placeholder="Email" value="<?php echo htmlspecialchars($edit_supplier_email); ?>" required>
                <textarea type="text" name="supplier_address" placeholder="Address" required><?php echo htmlspecialchars($edit_supplier_address); ?></textarea>
                <button type="submit" name="<?php echo $edit_supplier_mode ? 'update_supplier' : 'create_supplier'; ?>">
                    <?php echo $edit_supplier_mode ? 'Update Supplier' : 'Add Supplier'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for suppliers
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_supplier']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new supplier: " . htmlspecialchars($_POST['supplier_name']));
                // Add new supplier
                $supplier_name = trim($_POST['supplier_name']);
                $supplier_contact = trim($_POST['supplier_contact']);
                $supplier_email = trim($_POST['supplier_email']);
                $supplier_address = trim($_POST['supplier_address']);

                $stmt = $conn->prepare("INSERT INTO suppliers (name, contact, email, address) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $supplier_name, $supplier_contact, $supplier_email, $supplier_address);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Supplier added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding supplier.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_supplier']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated supplier ID: " . htmlspecialchars($edit_supplier_id));
                // Update existing supplier
                $supplier_name = trim($_POST['supplier_name']);
                $supplier_contact = trim($_POST['supplier_contact']);
                $supplier_email = trim($_POST['supplier_email']);
                $supplier_address = trim($_POST['supplier_address']);

                $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $supplier_name, $supplier_contact, $supplier_email, $supplier_address, $edit_supplier_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Supplier updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating supplier.</div>";
                }
                $stmt->close();
            }
        }

        // Handle Delete Supplier
        if (isset($_GET['action']) && $_GET['action'] === 'delete_supplier' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted supplier ID: " . htmlspecialchars($_GET['id']));
            $supplier_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->bind_param("i", $supplier_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Supplier deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting supplier.</div>";
            }
            $stmt->close();
        }
        ?>

        <div class="supplier-list">
            <h3>Existing Suppliers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM suppliers");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['name']}</td>
                                    <td>{$row['contact']}</td>
                                    <td>{$row['email']}</td>
                                    <td>{$row['address']}</td>
                                    <td>";
                        if ($user_role === 'admin' || $user_role === 'pharmacist Manager') {
                            echo "<a href='Editrecords.php?edit_supplier={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_supplier&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this supplier?');\">Delete</a>";
                        }
                        echo "</td>
                                  </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>



    <!-- Inventory Section code -->
    <div class="inventory-container">
        <h2>Manage Inventory</h2>
        <img src="medicine2.jpg" alt="Inventory Management" class="inventory-image">
        <?php
        // Initialize variables for editing inventory
        $edit_inventory_mode = false;
        $edit_inventory_id = null;
        $edit_medicine_name = '';
        $edit_price = '';
        $edit_stock = '';
        $edit_supplier_id = '';

        // Check if editing inventory
        if (isset($_GET['edit_inventory']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
            $edit_inventory_mode = true;
            $edit_inventory_id = $_GET['edit_inventory'];

            // Fetch inventory details for editing
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->bind_param("i", $edit_inventory_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_medicine_name = $row['medicine_name'];
                $edit_price = $row['price'];
                $edit_stock = $row['stock'];
                $edit_supplier_id = $row['supplier_id'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_inventory_mode ? '?edit_inventory=' . $edit_inventory_id : ''; ?>">
                <input type="text" name="medicine_name" placeholder="Medicine Name" value="<?php echo htmlspecialchars($edit_medicine_name); ?>" required>
                <input type="number" name="price" placeholder="Price" value="<?php echo htmlspecialchars($edit_price); ?>" required>
                <input type="number" name="stock" placeholder="Stock" value="<?php echo htmlspecialchars($edit_stock); ?>" required>
                <select name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php
                    // Fetch suppliers for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM suppliers");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_supplier_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <button type="submit" name="<?php echo $edit_inventory_mode ? 'update_inventory' : 'create_inventory'; ?>">
                    <?php echo $edit_inventory_mode ? 'Update Inventory' : 'Add Inventory'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for inventory
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_inventory']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new inventory item: " . htmlspecialchars($_POST['medicine_name']));
                // Add new inventory item
                $medicine_name = trim($_POST['medicine_name']);
                $price = trim($_POST['price']);
                $stock = trim($_POST['stock']);
                $supplier_id = trim($_POST['supplier_id']);
                // Ensure supplier_id is valid
                if (!empty($supplier_id)) {
                    $stmt = $conn->prepare("INSERT INTO inventory (medicine_name, price, stock, supplier_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssii", $medicine_name, $price, $stock, $supplier_id);

                    if ($stmt->execute()) {
                        echo "<div class='alert alert-success'>Inventory item added successfully!</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Supplier ID is required.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Error adding inventory item.</div>";
                }
            } elseif (isset($_POST['update_inventory']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated inventory item ID: " . htmlspecialchars($edit_inventory_id));
                // Update existing inventory item
                $medicine_name = trim($_POST['medicine_name']);
                $price = trim($_POST['price']);
                $stock = trim($_POST['stock']);
                $supplier_id = trim($_POST['supplier_id']);

                $stmt = $conn->prepare("UPDATE inventory SET medicine_name = ?, price = ?, stock = ?, supplier_id = ? WHERE id = ?");
                $stmt->bind_param("ssiis", $medicine_name, $price, $stock, $supplier_id, $edit_inventory_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Inventory item updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating inventory item.</div>";
                }
                $stmt->close();
            }
        }
        // Handle Delete Inventory
        if (isset($_GET['action']) && $_GET['action'] === 'delete_inventory' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted inventory item ID: " . htmlspecialchars($_GET['id']));
            $inventory_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->bind_param("i", $inventory_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Inventory item deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting inventory item.</div>";
            }
            $stmt->close();
        }
        ?>
        <div class="inventory-list">
            <h3>Existing Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Medicine ID</th>
                        <th>Medicine Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Supplier ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM inventory");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['medicine_name']}</td>
                                    <td>{$row['price']}</td>
                                    <td>{$row['stock']}</td>
                                    <td>{$row['supplier_id']}</td>
                                    <td>";
                        if ($user_role === 'admin' || $user_role === 'pharmacist Manager') {
                            echo "<a href='Editrecords.php?edit_inventory={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_inventory&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this inventory item?');\">Delete</a>";
                        }
                        echo "</td>
                                  </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Prescriptions Section  code-->
    <div class="prescription-container">
        <h1>Manage Prescriptions</h1>
        <img src="prescription.jpg" alt="Prescription Management" class="prescription-image">
        <?php
        // Initialize variables for editing prescriptions
        $edit_prescription_mode = false;
        $edit_prescription_id = null;
        $edit_patient_id = '';
        $edit_pharmacist_id = '';
        $edit_medicine_id = '';
        $edit_quantity = '';
        $edit_date_prescribed = '';

        // Check if editing a prescription
        if (isset($_GET['edit_prescription']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager' || $user_role === 'Cashier Manager')) {
            $edit_prescription_mode = true;
            $edit_prescription_id = $_GET['edit_prescription'];

            // Fetch prescription details for editing
            $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
            $stmt->bind_param("i", $edit_prescription_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_patient_id = $row['patient_id'];
                $edit_pharmacist_id = $row['pharmacist_id'];
                $edit_medicine_id = $row['medicine_id'];
                $edit_quantity = $row['quantity'];
                $edit_date_prescribed = $row['date_prescribed'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'pharmacist Manager' || $user_role === 'Cashier Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_prescription_mode ? '?edit_prescription=' . $edit_prescription_id : ''; ?>">
                <select name="patient_id" required>
                    <option value="">Select Patient</option>
                    <?php
                    // Fetch patients for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM patients");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_patient_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>

                </select>
                <select name="pharmacist_id" required>
                    <option value="">Select Pharmacist</option>
                    <?php
                    // Fetch pharmacists for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM pharmacists");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_pharmacist_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <select name="medicine_id" required>
                    <option value="">Select Medicine</option>
                    <?php
                    // Fetch medicines for dropdown
                    $stmt = $conn->prepare("SELECT id, medicine_name FROM inventory");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_medicine_id == $row['id']) ? 'selected' : '') . ">{$row['medicine_name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <input type="number" name="quantity" placeholder="Quantity" value="<?php echo htmlspecialchars($edit_quantity); ?>" required>
                <input type="date" name="date_prescribed" placeholder="Date Prescribed" value="<?php echo htmlspecialchars($edit_date_prescribed); ?>" required>
                <button type="submit" name="<?php echo $edit_prescription_mode ? 'update_prescription' : 'create_prescription'; ?>">
                    <?php echo $edit_prescription_mode ? 'Update Prescription' : 'Add Prescription'; ?>
                </button>
            </form>
        <?php endif; ?>
        <?php
        // Handle form submission for prescriptions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_prescription']) && ($user_role === 'admin' || $user_role === 'prescription Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new prescription for patient ID: " . htmlspecialchars($_POST['patient_id']));
                // Add new prescription
                $patient_id = trim($_POST['patient_id']);
                $pharmacist_id = trim($_POST['pharmacist_id']);
                $medicine_id = trim($_POST['medicine_id']);
                $quantity = trim($_POST['quantity']);
                $date_prescribed = trim($_POST['date_prescribed']);

                $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, pharmacist_id, medicine_id, quantity, date_prescribed) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiss", $patient_id, $pharmacist_id, $medicine_id, $quantity, $date_prescribed);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Prescription added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding prescription.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_prescription']) && ($user_role === 'admin' || $user_role === 'pharmacist Manager' || $user_role === 'Cashier Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated prescription ID: " . htmlspecialchars($edit_prescription_id));
                // Update existing prescription
                $patient_id = trim($_POST['patient_id']);
                $pharmacist_id = trim($_POST['pharmacist_id']);
                $medicine_id = trim($_POST['medicine_id']);
                $quantity = trim($_POST['quantity']);
                $date_prescribed = trim($_POST['date_prescribed']);

                $stmt = $conn->prepare("UPDATE prescriptions SET patient_id = ?, pharmacist_id = ?, medicine_id = ?, quantity = ?, date_prescribed = ? WHERE id = ?");
                $stmt->bind_param("iiissi", $patient_id, $pharmacist_id, $medicine_id, $quantity, $date_prescribed, $edit_prescription_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Prescription updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating prescription.</div>";
                }
                $stmt->close();
            }
        }
        // Handle Delete Prescription
        if (isset($_GET['action']) && $_GET['action'] === 'delete_prescription' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted prescription ID: " . htmlspecialchars($_GET['id']));
            $prescription_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
            $stmt->bind_param("i", $prescription_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Prescription deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting prescription.</div>";
            }
            $stmt->close();
        }
        ?>
        <div class="prescription-list">
            <h3>Existing Prescriptions</h3>
            <table>
                <thead>
                    <tr>
                        <th>prescript ID</th>
                        <th>Patient ID</th>
                        <th>Pharmacist ID</th>
                        <th>Medicine ID</th>
                        <th>Quantity</th>
                        <th>Date Prescribed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM prescriptions");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['patient_id']}</td>
                                    <td>{$row['pharmacist_id']}</td>
                                    <td>{$row['medicine_id']}</td>
                                    <td>{$row['quantity']}</td>
                                    <td>{$row['date_prescribed']}</td>
                                    <td>";
                        if ($user_role === 'admin' || $user_role === 'pharmacist Manager') {
                            echo "<a href='Editrecords.php?edit_prescription={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_prescription&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this prescription?');\">Delete</a>";
                        }
                        echo "</td>
                                  </tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payments Section code -->
    <div class="payments-container">
        <h3>Payments</h3>
        <img src="payment2.jpg" alt="Payment Management" class="payment-image">
        <?php
        // Initialize variables for editing payments
        $edit_payment_mode = false;
        $edit_payment_id = null;
        $edit_patient_id = '';
        $edit_prescript_id = '';
        $edit_payment_mode = '';
        $edit_total_cost = '';
        $edit_amount_paid = '';
        $edit_balance = '';
        $edit_cashier_id = '';
        $edit_payment_date = '';

        // Check if editing a payment

        if (isset($_GET['edit_payment']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
            $edit_payment_mode = true;
            $edit_payment_id = $_GET['edit_payment'];

            // Fetch payment details for editing
            $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->bind_param("i", $edit_payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_patient_id = $row['patient_id'];
                $edit_prescript_id = $row['prescript_id'];
                $edit_payment_mode = $row['payment_mode'];
                $edit_total_cost = $row['total_cost'];
                $edit_amount_paid = $row['amount_paid'];
                $edit_balance = $row['balance'];
                $edit_cashier_id = $row['cashier_id'];
                $edit_payment_date = $row['payment_date'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'cashier Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_payment_mode ? '?edit_payment=' . $edit_payment_id : ''; ?>">
                <select name="patient_id" required>
                    <option value="">Select Patient</option>
                    <?php
                    // Fetch patients for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM patients");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_patient_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <select name="prescript_id" required>
                    <option value="">Select Prescription</option>
                    <?php
                    // Fetch prescriptions for dropdown
                    $stmt = $conn->prepare("SELECT id FROM prescriptions");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_prescript_id == $row['id']) ? 'selected' : '') . ">{$row['id']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <select name="payment_mode" required>
                    <option value="">Select Payment Mode</option>
                    <option value="cash" <?php echo ($edit_payment_mode === 'cash') ? 'selected' : ''; ?>>Cash</option>
                    <option value="card" <?php echo ($edit_payment_mode === 'card') ? 'selected' : ''; ?>>Card</option>
                    <option value="mobile_money" <?php echo ($edit_payment_mode === 'mobile_money') ? 'selected' : ''; ?>>Mobile Money</option>
                </select>
                <input type="number" name="total_cost" placeholder="Total Cost" value="<?php echo htmlspecialchars($edit_total_cost); ?>" required>
                <input type="number" name="amount_paid" placeholder="Amount Paid" value="<?php echo htmlspecialchars($edit_amount_paid); ?>" required>
                <input type="number" name="balance" placeholder="Balance" value="<?php echo htmlspecialchars($edit_balance); ?>" required>
                <select name="cashier_id" required>
                    <option value="">Select Cashier</option>
                    <?php
                    // Fetch cashiers for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM cashiers");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_cashier_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <input type="date" name="payment_date" placeholder="Payment Date" value="<?php echo htmlspecialchars($edit_payment_date); ?>" required>
                <button type="submit" name="<?php echo $edit_payment_mode ? 'update_payment' : 'create_payment'; ?>">
                    <?php echo $edit_payment_mode ? 'Update Payment' : 'Add Payment'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for payments
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_payment']) && ($user_role === 'admin' || $user_role === 'cashier Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new payment for patient ID: " . htmlspecialchars($_POST['patient_id']));
                // Add new payment
                $patient_id = trim($_POST['patient_id']);
                $prescript_id = trim($_POST['prescript_id']);
                $payment_mode = trim($_POST['payment_mode']);
                $total_cost = trim($_POST['total_cost']);
                $amount_paid = trim($_POST['amount_paid']);
                $balance = trim($_POST['balance']);
                $cashier_id = trim($_POST['cashier_id']);
                $payment_date = trim($_POST['payment_date']);

                $stmt = $conn->prepare("INSERT INTO payments (patient_id, prescript_id, payment_mode, total_cost, amount_paid, balance, cashier_id, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iississs", $patient_id, $prescript_id, $payment_mode, $total_cost, $amount_paid, $balance, $cashier_id, $payment_date);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Payment added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding payment.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_payment']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated payment ID: " . htmlspecialchars($edit_payment_id));
                // Update existing payment
                $patient_id = trim($_POST['patient_id']);
                $prescript_id = trim($_POST['prescript_id']);
                $payment_mode = trim($_POST['payment_mode']);
                $total_cost = trim($_POST['total_cost']);
                $amount_paid = trim($_POST['amount_paid']);
                $balance = trim($_POST['balance']);
                $cashier_id = trim($_POST['cashier_id']);
                $payment_date = trim($_POST['payment_date']);

                $stmt = $conn->prepare("UPDATE payments SET patient_id = ?, prescript_id = ?, payment_mode = ?, total_cost = ?, amount_paid = ?, balance = ?, cashier_id = ?, payment_date = ? WHERE id = ?");
                $stmt->bind_param("iissssssi", $patient_id, $prescript_id, $payment_mode, $total_cost, $amount_paid, $balance, $cashier_id, $payment_date, $edit_payment_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Payment updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating payment.</div>";
                }
                $stmt->close();
            }
        }
        ?>
        <?php
        // Handle Delete Payment
        if (isset($_GET['action']) && $_GET['action'] === 'delete_payment' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted payment ID: " . htmlspecialchars($_GET['id']));
            $payment_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->bind_param("i", $payment_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Payment deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting payment.</div>";
            }
            $stmt->close();
        }
        ?>

        <!-- Display existing payments -->
        <div class="payment-list">
            <h3>Existing Payments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Patient ID</th>
                        <th>Prescription ID</th>
                        <th>Payment Mode</th>
                        <th>Total Cost</th>
                        <th>Amount Paid</th>
                        <th>Balance</th>
                        <th>Cashier ID</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM payments");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['patient_id']}</td>
                                    <td>{$row['prescript_id']}</td>
                                    <td>{$row['payment_mode']}</td>
                                    <td>{$row['total_cost']}</td>
                                    <td>{$row['amount_paid']}</td>
                                    <td>{$row['balance']}</td>
                                    <td>{$row['cashier_id']}</td>
                                    <td>{$row['payment_date']}</td>
                                    <td>";
                        if ($user_role === 'admin' || $user_role === 'cashier Manager') {
                            echo "<a href='Editrecords.php?edit_payment={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_payment&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this payment?');\">Delete</a>";
                        }
                        echo "</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales Section -->
    <div class="sales-container">
        <h2>Manage Sales</h2>
        <img src="Sales.jpg" alt="Sales Management" class="sales-image">
        <?php
        // Initialize variables for editing sales
        $edit_sales_mode = false;
        $edit_sales_id = null;
        $edit_patient_id = '';
        $edit_medicine_id = '';
        $edit_quantity_sold = '';
        $edit_total_price = '';
        $edit_sales_date = '';

        // Check if editing a sale
        if (isset($_GET['edit_sales']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
            $edit_sales_mode = true;
            $edit_sales_id = $_GET['edit_sales'];

            // Fetch sales details for editing
            $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->bind_param("i", $edit_sales_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_patient_id = $row['patient_id'];
                $edit_medicine_id = $row['medicine_id'];
                $edit_quantity_sold = $row['quantity_sold'];
                $edit_total_price = $row['total_price'];
                $edit_sales_date = $row['sales_date'];
            }
            $stmt->close();
        }
        ?>

        <?php if ($user_role === 'admin' || $user_role === 'cashier Manager'): ?>
            <form method="POST" action="Editrecords.php<?php echo $edit_sales_mode ? '?edit_sales=' . $edit_sales_id : ''; ?>">
                <select name="patient_id" required>
                    <option value="">Select Patient</option>
                    <?php
                    // Fetch patients for dropdown
                    $stmt = $conn->prepare("SELECT id, name FROM patients");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_patient_id == $row['id']) ? 'selected' : '') . ">{$row['name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <select name="medicine_id" required>
                    <option value="">Select Medicine</option>
                    <?php
                    // Fetch medicines for dropdown
                    $stmt = $conn->prepare("SELECT id, medicine_name FROM inventory");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}' " . (($edit_medicine_id == $row['id']) ? 'selected' : '') . ">{$row['medicine_name']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
                <input type="number" name="quantity_sold" placeholder="Quantity Sold" value="<?php echo htmlspecialchars($edit_quantity_sold); ?>" required>
                <input type="number" name="total_price" placeholder="Total Price" value="<?php echo htmlspecialchars($edit_total_price); ?>" required>
                <input type="date" name="sales_date" placeholder="Sales Date" value="<?php echo htmlspecialchars($edit_sales_date); ?>" required>
                <button type="submit" name="<?php echo $edit_sales_mode ? 'update_sales' : 'create_sales'; ?>">
                    <?php echo $edit_sales_mode ? 'Update Sale' : 'Add Sale'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle form submission for sales
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ((isset($_POST['create_sales']) && ($user_role === 'admin' || $user_role === 'cashier Manager'))) {
                logAudit($conn, $_SESSION['user_id'], "Created a new sale for patient ID: " . htmlspecialchars($_POST['patient_id']));
                // Add new sale
                $patient_id = trim($_POST['patient_id']);
                $medicine_id = trim($_POST['medicine_id']);
                $quantity_sold = trim($_POST['quantity_sold']);
                $total_price = trim($_POST['total_price']);
                $sales_date = trim($_POST['sales_date']);

                $stmt = $conn->prepare("INSERT INTO sales (patient_id, medicine_id, quantity_sold, total_price, sales_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiss", $patient_id, $medicine_id, $quantity_sold, $total_price, $sales_date);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Sale added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding sale.</div>";
                }
                $stmt->close();
            } elseif (isset($_POST['update_sales']) && ($user_role === 'admin' || $user_role === 'cashier Manager')) {
                logAudit($conn, $_SESSION['user_id'], "Updated sale ID: " . htmlspecialchars($edit_sales_id));
                // Update existing sale
                $patient_id = trim($_POST['patient_id']);
                $medicine_id = trim($_POST['medicine_id']);
                $quantity_sold = trim($_POST['quantity_sold']);
                $total_price = trim($_POST['total_price']);
                $sales_date = trim($_POST['sales_date']);

                $stmt = $conn->prepare("UPDATE sales SET patient_id = ?, medicine_id = ?, quantity_sold = ?, total_price = ?, sales_date = ? WHERE id = ?");
                $stmt->bind_param("iiissi", $patient_id, $medicine_id, $quantity_sold, $total_price, $sales_date, $edit_sales_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Sale updated successfully!</div>";
                    header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating sale.</div>";
                }
                $stmt->close();
            }
        }

        // Handle Delete Sale
        if (isset($_GET['action']) && $_GET['action'] === 'delete_sales' && $user_role === 'admin') {
            logAudit($conn, $_SESSION['user_id'], "Deleted sale ID: " . htmlspecialchars($_GET['id']));
            $sales_id = $_GET['id'];

            $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->bind_param("i", $sales_id);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Sale deleted successfully!</div>";
                header("Location: Editrecords.php"); // Redirect back to Editrecords.php
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error deleting sale.</div>";
            }
            $stmt->close();
        }
        ?>

        <!-- Display existing sales -->
        <div class="sales-list">
            <h3>Existing Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Patient ID</th>
                        <th>Medicine ID</th>
                        <th>Quantity Sold</th>
                        <th>Total Price</th>
                        <th>Sales Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM sales");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['patient_id']}</td>
                            <td>{$row['medicine_id']}</td>
                            <td>{$row['quantity_sold']}</td>
                            <td>{$row['total_price']}</td>
                            <td>{$row['sales_date']}</td>
                            <td>";
                        if ($user_role === 'admin' || $user_role === 'cashier Manager') {
                            echo "<a href='Editrecords.php?edit_sales={$row['id']}'>Edit</a> | ";
                        }
                        if ($user_role === 'admin') {
                            echo "<a href='Editrecords.php?action=delete_sales&id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this sale?');\">Delete</a>";
                        }
                        echo "</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 R&M Pharmacy Management System. All rights reserved.</p>
        <p>Developed by Ssemanda Ronald</p>
    </footer>
    <?php ob_end_flush(); // Flush the output buffer 
    ?>
</body>

</html>