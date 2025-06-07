<?php
// Start session

include 'Db_connection.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css"></a>
    <style type="text/css">

        .container {
            background: #fdf4fd;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
        }

        button {
            margin-top: 10px;
            background-color: brown;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border: none;
            padding: 10px;
            width: 100%;
        }

        button:hover {
            background-color: darkblue;
        }

        .hidden {
            display: none;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <header class="header">
        <img src="&.png" alt="Pharmacy Management System Logo" class="logo">
        <div class="header-text">
            <h1>Welcome to R&M Pharmacy Management System</h1>
            <p>Manage patients, pharmacists, cashiers, prescriptions, inventory, suppliers, and payments efficiently.</p>
        </div>
    </header>

    <div class="container">
    <form method="POST" action="login.php">
            <h1>Login</h1>
            <input type="text" name="username" placeholder="Enter your username" required>
            <input type="password" name="password" placeholder="Enter your password" required>
            <button type="submit" name="login">Login</button>
    </form>
    </div>


    <?php
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit();
            } else {
                echo "Invalid password.";
            }
        } else {
            echo "User not found.";
        }
    }
    ?>


     <footer class="footer">
            <p>&copy; 2025 R&M Pharmacy Management System. All rights reserved.</p>
            <p>Developed by Ssemanda Ronald</p>
        </footer>
    </div>
</body>

</html>