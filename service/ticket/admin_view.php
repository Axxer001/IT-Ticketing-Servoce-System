<?php
session_start();
require_once "../classes/ticket.php";
require_once "../classes/database.php";

// ✅ Redirect if not logged in
if (!isset($_SESSION['workEmail'])) {
    header("Location: ../ticket/login_account.php");
    exit();
}

// ✅ Restrict access to admins only
if ($_SESSION['type'] !== 'admin') {
    header("Location: ../ticket/view_tickets.php");
    exit();
}

$ticket = new Ticket();

// ✅ Handle permanent delete
if (isset($_GET['remove_id'])) {
    $removeId = $_GET['remove_id'];
    if ($ticket->permanentDeleteTicket($removeId)) {
        header("Location: admin_view.php?removed=1");
        exit();
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to remove ticket.</div>";
    }
}

$tickets = $ticket->getAllTickets();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - View Tickets</title>
<style>
/* Body */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #111;
    color: #fff;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Navbar */
.navbar {
    display: flex;
    align-items: center;
    padding: 15px 30px;
    background-color: #1e1e1e;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    gap: 20px;
}
.navbar .back-btn {
    background-color: #fff;
    color: #111;
    font-weight: bold;
    padding: 10px 18px;
    border-radius: 4px;
    text-decoration: none;
    transition: 0.3s;
}
.navbar .back-btn:hover {
    background-color: #ddd;
}
.navbar h2 {
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px auto;
    background-color: #1e1e1e; /* dark card */
    color: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    border-radius: 8px;
    overflow: hidden;
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #333;
}
th {
    background-color: #2c2c2c; /* dark grey header */
    color: #fff;
    font-weight: bold;
}
tr:hover {
    background-color: #333;
}
.deleted-row {
    background-color: #2a1a1a !important;
    text-decoration: line-through;
    color: #aaa;
}
.issue-cell {
    position: relative;
    cursor: help;
    color: #00d4ff;
    font-weight: bold;
}
.issue-cell::after {
    content: attr(data-full-issue);
    position: absolute;
    top: 120%;
    left: 0;
    white-space: pre-wrap;
    background-color: #333;
    color: #fff;
    padding: 8px;
    border-radius: 8px;
    opacity: 0;
    pointer-events: none;
    width: 300px;
    transition: opacity 0.2s ease;
    font-weight: normal;
    z-index: 10;
}
.issue-cell:hover::after {
    opacity: 1;
}

/* Buttons */
.btn, .btn-remove {
    display: inline-block;
    margin: 5px 0;
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.3s;
    font-weight: bold;
    cursor: pointer;
}

/* Update button */
.btn {
    background-color: #2c2c2c; /* dark grey */
    color: #00ff99; /* green accent */
}
.btn:hover {
    background-color: #444;
}

/* Remove button */
.btn-remove {
    background-color: #2c2c2c;
    color: #ff6666; /* red accent */
}
.btn-remove:hover {
    background-color: #444;
}

/* Flash message */
.flash-msg {
    background-color: #28a745;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: bold;
    display: inline-block;
    margin: 20px auto;
    text-align: center;
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
.footer a:hover { color: #fff; }

/* Responsive */
@media (max-width: 768px) {
    table, th, td { font-size: 12px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <a href="main_page.php" class="back-btn">← Back</a>
</div>

<?php if (isset($_GET['removed'])): ?>
<div class="flash-msg">🗑️ Ticket permanently removed!</div>
<?php endif; ?>

<table>
<tr>
    <th>ID</th>
    <th>Full Name</th>
    <th>Department</th>
    <th>Contact</th>
    <th>Email</th>
    <th>Device Type</th>
    <th>Device Name</th>
    <th>Issue</th>
    <th>Service Provider</th>
    <th>Status</th>
    <th>Created</th>
    <th>Updated</th>
    <th>Actions</th>
</tr>

<?php foreach ($tickets as $row): ?>
<?php $isDeleted = strtolower($row['status']) === 'deleted'; ?>
<tr class="<?= $isDeleted ? 'deleted-row' : '' ?>">
    <td><?= htmlspecialchars($row['id']) ?></td>
    <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) ?></td>
    <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['contact']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['deviceType']) ?></td>
    <td><?= htmlspecialchars($row['deviceName']) ?></td>
    <td class="issue-cell" data-full-issue="<?= htmlspecialchars($row['issueDescription']) ?>">...</td>
    <td><?= htmlspecialchars($row['serviceProvider'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['status']) ?></td>
    <td>
        <?php
            $datetime = new DateTime($row['creationDate'], new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Asia/Manila'));
            echo $datetime->format('Y-m-d h:i:s A');
        ?>
    </td>
    <td>
        <?php
            $datetime = new DateTime($row['recentUpdate'], new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Asia/Manila'));
            echo $datetime->format('Y-m-d h:i:s A');
        ?>
    </td>
    <td>
        <?php if ($isDeleted): ?>
            <a href="admin_view.php?remove_id=<?= $row['id'] ?>" class="btn-remove"
               onclick="return confirm('Are you sure you want to permanently delete this ticket?');">
               Remove
            </a>
        <?php else: ?>
            <a href="admin_update.php?id=<?= $row['id'] ?>" class="btn">Update</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

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
