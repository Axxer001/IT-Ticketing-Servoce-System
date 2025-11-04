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

// ✅ Check if user is admin
$stmt = $db->connect()->prepare("SELECT * FROM accounts WHERE workEmail = ?");
$stmt->execute([$_SESSION['workEmail']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['type'] !== 'admin') {
    die("❌ Access denied. Admins only.");
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

// Load service providers
$serviceProviders = $ticket->getServiceProviders();

// Get current assignment counts for providers
$providerCounts = $ticket->getServiceProviderCounts();
$maxAssignments = 3;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedProvider = $_POST['serviceProvider'] ?? '';

    // Add new service provider if specified
    if (!empty($_POST['newServiceProvider'])) {
        $newProvider = trim($_POST['newServiceProvider']);
        if ($newProvider) {
            $ticket->addServiceProvider($newProvider);
            $selectedProvider = $newProvider;
        }
    }

    $ticket->serviceProvider = $selectedProvider;
    $ticket->status = $_POST['status'] ?? 'Pending';

    if ($ticket->adminUpdateTicket($ticketId)) {
        header("Location: admin_view.php?updated=1");
        exit;
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to update ticket.</div>";
    }

    // Reload providers after update
    $serviceProviders = $ticket->getServiceProviders();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Update Ticket</title>
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
    <a href="admin_view.php" class="back-btn">← Back</a>
</div>

<div class="container">
    <h2 style="text-align:center; margin-bottom:30px;">Admin Update Ticket</h2>

    <div class="card">
        <form action="" method="POST">

            <!-- Employee Info (Read-only) -->
            <div class="form-section">
                <h5>Employee Information</h5>
                <div class="flex-row">
                    <div>
                        <label>First Name</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($ticketData['accountFirstName'] ?? $ticketData['firstName']) ?>" readonly>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($ticketData['accountLastName'] ?? $ticketData['lastName']) ?>" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Department</label>
                    <input type="text" class="form-control" 
                           value="<?= htmlspecialchars($ticketData['accountDepartment'] ?? $ticketData['department']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="text" class="form-control" 
                           value="<?= htmlspecialchars($ticketData['accountEmail'] ?? $ticketData['email']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label>Contact Number</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['contact']) ?>" readonly>
                </div>
            </div>

            <!-- Device & Issue Info -->
            <div class="form-section">
                <h5>Device Specification & Issue</h5>
                <div class="flex-row">
                    <div>
                        <label>Device Type</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['deviceType']) ?>" readonly>
                    </div>
                    <div>
                        <label>Device Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ticketData['deviceName']) ?>" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Issue Description</label>
                    <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($ticketData['issueDescription']) ?></textarea>
                </div>
            </div>

            <hr>

            <!-- Service Provider -->
            <div class="form-section">
                <h5>Service Provider & Status</h5>
                <div class="mb-3">
                    <label>Service Provider</label>
                    <select name="serviceProvider" class="form-select" onchange="toggleNewProvider(this)">
                        <option value="">-- Select Provider --</option>
                        <?php foreach ($serviceProviders as $provider):
                            $count = $providerCounts[$provider] ?? 0;
                            $disabled = ($count >= $maxAssignments && $ticketData['serviceProvider'] !== $provider) ? 'disabled' : '';
                            $label = ($count >= $maxAssignments && $ticketData['serviceProvider'] !== $provider) 
                                     ? "$provider (max assignments reached)" 
                                     : $provider;
                        ?>
                            <option value="<?= htmlspecialchars($provider) ?>" <?= ($ticketData['serviceProvider'] === $provider || ($selectedProvider ?? '') === $provider) ? 'selected' : '' ?> <?= $disabled ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="__add_new__">➕ Add new provider...</option>
                    </select>
                    <input type="text" name="newServiceProvider" id="newServiceProvider" class="form-control mt-2"
                           placeholder="Enter new provider name" style="display: none;">
                </div>

                <div class="mb-3">
                    <label>Status</label><br>
                    <?php
                    $statuses = ["Pending", "In Progress", "Completed", "Failed"];
                    foreach ($statuses as $status):
                    ?>
                        <label class="me-3">
                            <input type="radio" name="status" value="<?= $status ?>"
                                <?= ($ticketData['status'] === $status || ($ticket->status ?? '') === $status) ? 'checked' : '' ?>> <?= $status ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit">Save Update</button>
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
function toggleNewProvider(select) {
    const input = document.getElementById('newServiceProvider');
    input.style.display = select.value === '__add_new__' ? 'block' : 'none';
}
</script>

</body>
</html>
