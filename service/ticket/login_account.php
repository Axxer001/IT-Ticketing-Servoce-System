<?php
session_start();
require_once "../classes/database.php";

$db = new Database();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $workEmail = $_POST['workEmail'];
    $password = $_POST['password'];

    $stmt = $db->connect()->prepare("SELECT * FROM accounts WHERE workEmail = ?");
    $stmt->execute([$workEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['workEmail'] = $user['workEmail'];

        if ($user['type'] === 'employee') {
            $stmtInfo = $db->connect()->prepare("SELECT * FROM employee_info WHERE accountId = ?");
            $stmtInfo->execute([$user['id']]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                $_SESSION['firstName'] = $info['firstName'];
                $_SESSION['lastName'] = $info['lastName'];
                $_SESSION['department'] = $info['department'];
            }
        }

        // ✅ Redirect all users to main_page.php
        header("Location: ../ticket/main_page.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Account</title>
<style>
    /* Body & container */
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #111;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .login-container {
        background-color: #1e1e1e;
        padding: 40px 30px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        color: #fff;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Logo */
    .login-container .logo {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 30px;
        letter-spacing: 2px;
    }

    /* Tabs */
    .login-tabs {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    .login-tabs span {
        cursor: default;
        font-weight: bold;
        padding-bottom: 5px;
        border-bottom: 2px solid transparent;
    }
    .login-tabs .active {
        border-color: #4e54c8;
    }

    /* Form */
    .login-container form {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .login-container input[type="email"],
    .login-container input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        background-color: #111;
        border: 1px solid #333;
        border-radius: 4px;
        color: #fff;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }
    .login-container input:focus {
        border-color: #4e54c8;
        box-shadow: 0 0 5px #4e54c8;
    }

    /* Button */
    .login-container button {
        width: 106.2%;
        padding: 12px;
        margin-top: 30px;
        border: none;
        border-radius: 4px;
        background-color: #fff;
        color: #111;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }
    .login-container button:hover {
        background-color: #ddd;
    }

    /* Error message */
    .error {
        color: #ff4b5c;
        margin-bottom: 15px;
        font-size: 14px;
        text-align: center;
    }

    /* Responsive */
    @media (max-width: 450px) {
        .login-container {
            width: 90%;
            padding: 30px 20px;
        }
    }
</style>
</head>
<body>

<div class="login-container">
    <div class="logo">NEXON</div>

    <div class="login-tabs">
        <span class="active">LOG IN</span>
    </div>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">
        <input type="email" name="workEmail" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">CONTINUE</button>
    </form>
</div>

</body>
</html>
