<?php
session_start();
require_once "../classes/ticket.php";

$ticket = new Ticket();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../ticket/login_account.php");
    exit();
}

// Soft delete (mark as deleted)
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    if ($ticket->deleteTicket($deleteId)) {
        header("Location: view_tickets.php?deleted=1");
        exit();
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to delete ticket.</div>";
    }
}

// Fetch tickets
if ($_SESSION['type'] === 'employee') {
    $tickets = $ticket->getTicketsByUser($_SESSION['user_id']);
} else {
    $tickets = $ticket->getAllTicketsWithEmployeeInfo();
}

// Helper functions for employee info
function getTicketUserEmail($row) {
    return $row['workEmail'] ?? $row['accountEmail'] ?? '';
}

function getTicketFirstName($row) {
    return $row['firstName'] ?? $row['accountFirstName'] ?? '';
}

function getTicketLastName($row) {
    return $row['lastName'] ?? $row['accountLastName'] ?? '';
}

function getTicketDepartment($row) {
    return $row['department'] ?? $row['accountDepartment'] ?? '-';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Tickets</title>
<style>
/* Body & container */
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
.issue-cell:hover::after { opacity: 1; }

/* Buttons */
.btn, .btn-edit, .btn-delete, .btn-disabled {
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

/* Add New Ticket button */
.btn {
    background-color: #2c2c2c; /* dark grey */
    color: #fff;
}
.btn:hover {
    background-color: #444; /* slightly lighter grey */
}

/* Edit button */
.btn-edit {
    background-color: #2c2c2c; 
    color: #00ff99; /* green accent */
}
.btn-edit:hover {
    background-color: #444;
}

/* Delete button */
.btn-delete {
    background-color: #2c2c2c;
    color: #ff6666; /* red accent */
}
.btn-delete:hover {
    background-color: #444;
}

/* Disabled buttons */
.btn-disabled {
    background-color: #555;
    color: #aaa;
    cursor: not-allowed;
    pointer-events: none;
    opacity: 0.6;
}

/* Flash message */
#flash-message {
    background-color: #28a745;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: bold;
    display: inline-block;
    margin: 20px auto;
    text-align: center;
    animation: fadeOut 3s ease forwards;
}
@keyframes fadeOut { to { opacity: 0; visibility: hidden; } }

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
<nav class="navbar">
    <a href="main_page.php" class="back-btn">← Back</a>
</nav>

<h2 style="text-align:center; margin-top:20px;">View Submitted Tickets</h2>

<div style="text-align:center;">
    <a href="add_ticket.php" class="btn">Add New Ticket</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div id="flash-message">Ticket marked as deleted!</div>
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
<?php
    $ticketFirstName = getTicketFirstName($row);
    $ticketLastName = getTicketLastName($row);
    $ticketDept = getTicketDepartment($row);

    // Disable edit/delete only if service provider exists
    $disableActions = !empty($row['serviceProvider']);
?>
<tr>
    <td><?= htmlspecialchars($row['id']) ?></td>
    <td><?= htmlspecialchars($ticketFirstName . ' ' . $ticketLastName) ?></td>
    <td><?= htmlspecialchars($ticketDept) ?></td>
    <td><?= htmlspecialchars($row['contact']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['deviceType']) ?></td>
    <td><?= htmlspecialchars($row['deviceName']) ?></td>
    <td class="issue-cell" data-full-issue="<?= htmlspecialchars($row['issueDescription']) ?>">...</td>
    <td><?= htmlspecialchars($row['serviceProvider'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['status']) ?></td>
    <td><?= (new DateTime($row['creationDate'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('Y-m-d h:i:s A') ?></td>
    <td><?= (new DateTime($row['recentUpdate'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('Y-m-d h:i:s A') ?></td>
    <td>
        <?php if ($disableActions): ?>
            <button class="btn-disabled">Edit</button>
            <button class="btn-disabled">Delete</button>
        <?php else: ?>
            <a href="edit_ticket.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
            <a href="view_tickets.php?delete_id=<?= $row['id'] ?>" class="btn-delete"
               onclick="return confirm('Are you sure you want to mark this ticket as deleted?');">Delete</a>
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    const msg = document.getElementById("flash-message");
    if (msg) {
        setTimeout(() => {
            msg.style.transition = "opacity 0.5s";
            msg.style.opacity = "0";
            setTimeout(() => msg.remove(), 500);
        }, 3000);
    }
});
</script>

</body>
</html>
