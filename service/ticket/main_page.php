<?php
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['workEmail']);
$userType = $_SESSION['type'] ?? ''; // Can be 'employee' or 'admin'
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Main Page</title>
<style>
    /* Body & container */
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #111;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        color: #fff;
    }

    /* Navbar */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 30px;
        background-color: #1e1e1e;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    }
    .navbar .logo {
        font-size: 28px;
        font-weight: bold;
        letter-spacing: 2px;
    }
    .navbar .nav-buttons {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .navbar .nav-buttons button,
    .navbar .nav-buttons a {
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        background-color: #fff;
        color: #111;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
        width: 120px;
        text-align: center;
    }
    .navbar .nav-buttons button:hover:not(.disabled),
    .navbar .nav-buttons a:hover:not(.disabled) {
        background-color: #ddd;
    }
    .navbar .nav-buttons .disabled {
        background-color: #555;
        color: #ccc;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Main content card */
    .main-container {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 50px 20px;
    }
    .card {
        background-color: #1e1e1e;
        padding: 40px 30px;
        border-radius: 8px;
        width: 500px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    .card h1 {
        font-size: 2rem;
        font-weight: bold;
    }
    .card p {
        font-size: 1rem;
        color: #ccc;
    }
    .card button {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 4px;
        background-color: #fff;
        color: #111;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }
    .card button:hover {
        background-color: #ddd;
    }

    /* Footer */
    .footer {
        padding: 30px 20px;
        background-color: #1e1e1e;
        text-align: center;
        font-size: 0.9rem;
        color: #aaa;
    }
    .footer a {
        color: #aaa;
        text-decoration: none;
        margin: 0 5px;
    }
    .footer a:hover {
        color: #fff;
    }

    /* Responsive */
    @media (max-width: 550px) {
        .card {
            width: 90%;
            padding: 30px 20px;
        }
        .navbar {
            flex-direction: column;
            gap: 15px;
        }
        .navbar .nav-buttons button,
        .navbar .nav-buttons a {
            width: 100%;
        }
    }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="logo">NEXON</div>
    <div class="nav-buttons">
        <?php if ($loggedIn): ?>
            <?php if ($userType === 'admin'): ?>
                <a href="admin_view.php">Manage</a>
            <?php else: ?>
                <a href="view_tickets.php">Support</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login_account.php">Log In</a>
            <button class="disabled">Support</button>
        <?php endif; ?>
    </div>
</nav>

<!-- Main Content -->
<div class="main-container">
    <div class="card">
        <h1>Welcome to Nexon</h1>
        <?php if ($loggedIn): ?>
            <?php if ($userType === 'admin'): ?>
                <p>Hello, <strong>Admin</strong>!</p>
                <p>Click "Manage" in the navbar to manage tickets.</p>
            <?php else: ?>
                <p>Hello, <strong><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName'] ?? '') ?></strong>!</p>
                <p>Click "Support" in the navbar to view your tickets.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Explore our games, get the launcher, and start playing today!</p>
        <?php endif; ?>
        <button>Get Launcher</button>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <p>
        <a href="#">ABOUT NEXON</a> | 
        <a href="#">OUR TEAM</a> | 
        <a href="#">CAREERS</a> | 
        <a href="#">SUPPORT</a>
    </p>
    <p>
        <a href="#">PRESS</a> | 
        <a href="#">INVESTOR RELATIONS</a> | 
        <a href="#">PRIVACY POLICY</a> | 
        <a href="#">LEGAL DOCUMENTATION</a>
    </p>
    <p>&copy;2025 NEXON America Inc. All Rights Reserved.</p>
</footer>

</body>
</html>
