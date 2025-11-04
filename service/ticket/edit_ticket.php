<?php
session_start();
require_once "../classes/ticket.php";
require_once "../classes/database.php";

$ticket = new Ticket();
$db = new Database();

// ✅ Redirect if not logged in
if (!isset($_SESSION['workEmail'])) {
    header("Location: login_account.php");
    exit;
}

// ✅ Fetch logged-in user info directly from DB
$stmt = $db->connect()->prepare("SELECT * FROM accounts WHERE workEmail = ?");
$stmt->execute([$_SESSION['workEmail']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ User not found.");
}

// ✅ Get ticket info
if (!isset($_GET['id'])) {
    die("❌ Ticket ID not provided.");
}

$ticketId = $_GET['id'];
$ticketData = $ticket->getTicketById($ticketId);

if (!$ticketData) {
    die("❌ Ticket not found.");
}

// ✅ Ensure employees can only edit their own tickets
if ($user['type'] === 'employee' && $ticketData['accountId'] != $user['id']) {
    die("❌ You are not authorized to edit this ticket.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ticket->contact = $_POST['contact'];
    $ticket->email = $_POST['email'];
    $ticket->deviceType = $_POST['deviceType'] === 'Other' && !empty($_POST['otherDevice'])
        ? $_POST['otherDevice']
        : $_POST['deviceType'];
    $ticket->deviceName = $_POST['deviceName'];
    $ticket->issueDescription = $_POST['issueDescription'];

    if ($ticket->editTicket($ticketId)) {
        header("Location: view_tickets.php?updated=1");
        exit;
    } else {
        echo "❌ Failed to update ticket.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Ticket</title>
<style>
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

/* Container & Card */
.container {
    width: 95%;
    max-width: 900px;
    margin: 30px auto;
}
.card {
    background-color: #1e1e1e;
    padding: 30px 35px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

/* Form Elements */
form label {
    font-weight: bold;
    margin-bottom: 8px;
    display: block;
}
.form-control, .form-select, textarea {
    background-color: #2c2c2c;
    color: #fff;
    border: 1px solid #444;
    border-radius: 6px;
    padding: 10px 12px;
    width: 100%;
    margin-bottom: 20px;
}
.form-control:focus, .form-select:focus, textarea:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 5px #00d4ff;
}
textarea { resize: none; }

/* Form sections */
.form-section {
    margin-bottom: 30px;
}
.form-section h5 {
    border-bottom: 1px solid #444;
    padding-bottom: 5px;
    margin-bottom: 20px;
    color: #00d4ff;
}

/* Flex row adjustments */
.flex-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.flex-row > div {
    flex: 1 1 48%;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 14px;
    background-color: #00d4ff;
    color: #111;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}
.btn-submit:hover { background-color: #009ecf; }

/* Footer */
.footer {
    margin-top: auto;
    padding: 30px 20px;
    background-color: #1e1e1e;
    text-align: center;
    font-size: 0.9rem;
    color: #aaa;
}
.footer a { color: #aaa; text-decoration: none; margin: 0 5px; }
.footer a:hover { color: #fff; }

/* Responsive */
@media (max-width: 768px) {
    .form-control, .form-select, textarea { font-size: 14px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <a href="view_tickets.php" class="back-btn">← Back</a>
</div>

<div class="container">
    <h2 style="text-align:center; margin-bottom:30px;">Edit Ticket</h2>

    <div class="card">
        <form action="" method="POST">

            <!-- Employee Info (Read-Only) -->
            <div class="form-section">
                <h5>Employee Info</h5>
                <div class="flex-row">
                    <div>
                        <label>First Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['firstName']) ?>" readonly>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['lastName']) ?>" readonly>
                    </div>
                    <div>
                        <label>Department</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['department']) ?>" readonly>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Contact Info -->
            <div class="form-section">
                <h5>Contact Information</h5>
                <div class="flex-row">
                    <div>
                        <label>Contact Number</label>
                        <input type="text" name="contact" class="form-control"
                               value="<?= htmlspecialchars($ticketData['contact']) ?>" required>
                    </div>
                    <div>
                        <label>Email (optional)</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($ticketData['email']) ?>">
                    </div>
                </div>
            </div>

            <!-- Device Info -->
            <div class="form-section">
                <h5>Device Specification & Issue</h5>
                <div class="flex-row">
                    <div>
                        <label>Device Type</label>
                        <select name="deviceType" class="form-select" required onchange="toggleOtherDevice(this)">
                            <?php
                            $deviceTypes = ["Laptop", "Desktop", "Tablet", "Smartphone", "Printer", "Router", "Other"];
                            foreach ($deviceTypes as $type) {
                                $selected = ($ticketData['deviceType'] === $type) ? 'selected' : '';
                                echo "<option value='$type' $selected>$type</option>";
                            }
                            ?>
                        </select>
                        <input type="text" name="otherDevice" id="otherDevice" class="form-control mt-2"
                               placeholder="Specify device type"
                               style="display: <?= ($ticketData['deviceType'] === 'Other') ? 'block' : 'none' ?>;"
                               value="<?= ($ticketData['deviceType'] === 'Other') ? htmlspecialchars($ticketData['deviceType']) : '' ?>">
                    </div>
                    <div>
                        <label>Device Name</label>
                        <input type="text" name="deviceName" class="form-control"
                               value="<?= htmlspecialchars($ticketData['deviceName']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Issue Description</label>
                    <textarea name="issueDescription" class="form-control" rows="3" required><?= htmlspecialchars($ticketData['issueDescription']) ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Update Ticket</button>
        </form>
    </div>
</div>

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
function toggleOtherDevice(select) {
    const otherInput = document.getElementById('otherDevice');
    otherInput.style.display = select.value === 'Other' ? 'block' : 'none';
}
</script>

</body>
</html>
