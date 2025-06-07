<?php
ob_start(); // Start output buffering
// Include database connection
include 'Db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in to access this page.'); window.location.href='login.php';</script>";
    exit();
}

$user_role = $_SESSION['role'] ?? null; // Get the user's role

// Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access denied!'); window.location.href='index.php';</script>";
    exit();
}

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
    <title>Create User</title>
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

    <nav class="navigation">
        <ul>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="index.php#dashboard">Dashboard</a></li>
                <li><a href="Editrecords.php">Details</a></li>
                <li><a href="audit_logs.php">Audit Logs</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <h2>Create or Edit User</h2>
        <img src="users.jpg" alt="Users" class="user-image">

        <?php
        // Initialize variables for editing
        $edit_mode = false;
        $edit_user_id = null;
        $edit_username = '';
        $edit_role = '';

        // Check if editing a user
        if (isset($_GET['edit'])) {
            $edit_mode = true;
            $edit_user_id = $_GET['edit'];

            // Fetch user details for editing
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $edit_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $edit_username = $row['username'];
                $edit_role = $row['role'];
            }
            $stmt->close();
        }
        ?>

        <form method="POST" action="createUser.php<?php echo $edit_mode ? '?edit=' . $edit_user_id : ''; ?>">
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($edit_username); ?>" required>
            <input type="password" name="password" placeholder="Password (Leave blank to keep current password)">
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin" <?php echo $edit_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="pharmacist Manager" <?php echo $edit_role === 'pharmacist Manager' ? 'selected' : ''; ?>>Pharmacist Manager</option>
                <option value="cashier Manager" <?php echo $edit_role === 'cashier Manager' ? 'selected' : ''; ?>>Cashier Manager</option>
            </select>
            <button type="submit" name="<?php echo $edit_mode ? 'update_user' : 'create_user'; ?>">
                <?php echo $edit_mode ? 'Update User' : 'Create User'; ?>
            </button>
        </form>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['create_user'])) {
                // Log the audit action
                logAudit($conn, $_SESSION['user_id'], "Created a new user: " . htmlspecialchars($_POST['username']));
                
                // Create new user
                $username = trim($_POST['username']);
                $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
                $role = trim($_POST['role']);

                // Check if the username already exists
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "<div class='alert alert-danger'>Username already exists!</div>";
                } else {
                    // Insert the new user into the database
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $role);
                    if ($stmt->execute()) {
                        echo "<div class='alert alert-success'>User created successfully!</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Error creating user.</div>";
                    }
                }
                $stmt->close();
            } elseif (isset($_POST['update_user'])) {
                logAudit($conn, $_SESSION['user_id'], "Updated user ID: " . htmlspecialchars($edit_user_id));
                // Update existing user
                $username = trim($_POST['username']);
                $role = trim($_POST['role']);
                $password = trim($_POST['password']);

                if (!empty($password)) {
                    // Hash the new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Update username, role, and password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $username, $role, $hashed_password, $edit_user_id);
                } else {
                    // Update username and role only
                    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $username, $role, $edit_user_id);
                }

                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>User updated successfully!</div>";
                    header("Location: createUser.php"); // Redirect back to createUser.php
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Error updating user.</div>";
                }
                $stmt->close();
            }
        }
        ?>

        <div class="user-list">
            <h3>Existing Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
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
                                <td>
                                    <a href='createUser.php?edit={$row['id']}'>Edit</a> |
                                    <a href='deleteuser.php?id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>
                                </td>
                              </tr>";
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