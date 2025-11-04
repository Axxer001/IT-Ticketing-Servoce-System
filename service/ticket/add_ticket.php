<?php
session_start();
require_once "../classes/ticket.php";
require_once "../classes/database.php";

$ticket = new Ticket();
$db = new Database();

$isLoggedIn = isset($_SESSION['user_id']);
$isEmployee = ($isLoggedIn && isset($_SESSION['type']) && $_SESSION['type'] === 'employee');

$employeeInfo = null;
if ($isEmployee) {
    $stmt = $db->connect()->prepare("SELECT * FROM employee_info WHERE accountId = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $employeeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($isEmployee && $employeeInfo) {
        $ticket->firstName = $employeeInfo['firstName'];
        $ticket->lastName = $employeeInfo['lastName'];
        $ticket->department = $employeeInfo['department'];
    } else {
        $ticket->firstName = $_POST['firstName'] ?? '';
        $ticket->lastName = $_POST['lastName'] ?? '';
        $ticket->department = $_POST['department'] ?? '';
    }

    $ticket->contact = $_POST['contact'] ?? '';
    $ticket->email = $_POST['email'] ?? '';
    $ticket->deviceType = ($_POST['deviceType'] === 'Other' && !empty($_POST['otherDevice']))
        ? $_POST['otherDevice']
        : ($_POST['deviceType'] ?? '');
    $ticket->deviceName = $_POST['deviceName'] ?? '';
    $ticket->issueDescription = $_POST['issueDescription'] ?? '';

    if ($isLoggedIn) {
        $ticket->accountId = $_SESSION['user_id'];
    }

    if ($ticket->addTicket()) {
        if ($isEmployee && !$employeeInfo) {
            $insert = $db->connect()->prepare("INSERT INTO employee_info (accountId, firstName, lastName, department) VALUES (?, ?, ?, ?)");
            $insert->execute([
                $_SESSION['user_id'],
                $ticket->firstName,
                $ticket->lastName,
                $ticket->department
            ]);
            $_SESSION['firstName'] = $ticket->firstName;
            $_SESSION['lastName'] = $ticket->lastName;
            $_SESSION['department'] = $ticket->department;
        }
        header("Location: view_tickets.php?success=1");
        exit;
    } else {
        echo "❌ Failed to submit ticket. Please try again.";
    }
}

$lastTicket = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Ticket</title>
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
    <h2 style="text-align:center; margin-bottom:30px;">Add New Ticket</h2>

    <div class="card">
        <form action="" method="POST">

            <!-- Credentials -->
            <div class="form-section">
                <h5>Credentials</h5>
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <label>First Name</label>
                        <?php
                        $prefillFirst = $employeeInfo['firstName'] ?? ($lastTicket['firstName'] ?? '');
                        $firstReadonly = ($isEmployee && $employeeInfo) ? 'readonly' : '';
                        ?>
                        <input type="text" name="firstName" class="form-control"
                               value="<?= htmlspecialchars($prefillFirst) ?>" required <?= $firstReadonly ?>>
                    </div>
                    <div style="flex:1;">
                        <label>Last Name</label>
                        <?php
                        $prefillLast = $employeeInfo['lastName'] ?? ($lastTicket['lastName'] ?? '');
                        $lastReadonly = ($isEmployee && $employeeInfo) ? 'readonly' : '';
                        ?>
                        <input type="text" name="lastName" class="form-control"
                               value="<?= htmlspecialchars($prefillLast) ?>" required <?= $lastReadonly ?>>
                    </div>
                </div>
            </div>

            <!-- Department -->
            <div class="form-section">
                <h5>Department</h5>
                <?php
                $prefillDept = $employeeInfo['department'] ?? ($lastTicket['department'] ?? '');
                $deptReadonly = ($isEmployee && $employeeInfo) ? 'disabled' : '';
                ?>
                <select name="department" class="form-select" required <?= $deptReadonly ?>>
                    <option value="">Select Department...</option>

                    <optgroup label="🎮 Game Development & Production">
                        <option <?= ($prefillDept === 'Game Design Department') ? 'selected' : '' ?>>Game Design Department</option>
                        <option <?= ($prefillDept === 'Art & Animation Department') ? 'selected' : '' ?>>Art & Animation Department</option>
                        <option <?= ($prefillDept === 'Programming / Engineering Department') ? 'selected' : '' ?>>Programming / Engineering Department</option>
                        <option <?= ($prefillDept === 'Sound Design / Music Department') ? 'selected' : '' ?>>Sound Design / Music Department</option>
                        <option <?= ($prefillDept === 'Quality Assurance (QA) / Testing Department') ? 'selected' : '' ?>>Quality Assurance (QA) / Testing Department</option>
                        <option <?= ($prefillDept === 'Localization Department') ? 'selected' : '' ?>>Localization Department</option>
                        <option <?= ($prefillDept === 'Game Production / Project Management') ? 'selected' : '' ?>>Game Production / Project Management</option>
                    </optgroup>

                    <optgroup label="💻 Technology & Infrastructure">
                        <option <?= ($prefillDept === 'IT Infrastructure Department') ? 'selected' : '' ?>>IT Infrastructure Department</option>
                        <option <?= ($prefillDept === 'Cloud Operations Department') ? 'selected' : '' ?>>Cloud Operations Department</option>
                        <option <?= ($prefillDept === 'Platform Engineering Department') ? 'selected' : '' ?>>Platform Engineering Department</option>
                        <option <?= ($prefillDept === 'Data Analytics / Business Intelligence Department') ? 'selected' : '' ?>>Data Analytics / Business Intelligence Department</option>
                        <option <?= ($prefillDept === 'Cybersecurity Department') ? 'selected' : '' ?>>Cybersecurity Department</option>
                        <option <?= ($prefillDept === 'DevOps / Tools Engineering Department') ? 'selected' : '' ?>>DevOps / Tools Engineering Department</option>
                    </optgroup>

                    <optgroup label="🏢 Corporate & Administrative">
                        <option <?= ($prefillDept === 'Executive Management / CEO Office') ? 'selected' : '' ?>>Executive Management / CEO Office</option>
                        <option <?= ($prefillDept === 'Finance Department') ? 'selected' : '' ?>>Finance Department</option>
                        <option <?= ($prefillDept === 'Human Resources (HR)') ? 'selected' : '' ?>>Human Resources (HR)</option>
                        <option <?= ($prefillDept === 'Legal & Compliance Department') ? 'selected' : '' ?>>Legal & Compliance Department</option>
                        <option <?= ($prefillDept === 'Procurement / Administration Department') ? 'selected' : '' ?>>Procurement / Administration Department</option>
                        <option <?= ($prefillDept === 'Corporate Strategy / Planning Department') ? 'selected' : '' ?>>Corporate Strategy / Planning Department</option>
                        <option <?= ($prefillDept === 'Investor Relations (IR)') ? 'selected' : '' ?>>Investor Relations (IR)</option>
                    </optgroup>

                    <optgroup label="🌐 Publishing & Live Operations">
                        <option <?= ($prefillDept === 'Game Operations / LiveOps Department') ? 'selected' : '' ?>>Game Operations / LiveOps Department</option>
                        <option <?= ($prefillDept === 'Customer Service / Player Support') ? 'selected' : '' ?>>Customer Service / Player Support</option>
                        <option <?= ($prefillDept === 'Community Management') ? 'selected' : '' ?>>Community Management</option>
                        <option <?= ($prefillDept === 'Localization QA / Content Operations') ? 'selected' : '' ?>>Localization QA / Content Operations</option>
                        <option <?= ($prefillDept === 'Server Operations') ? 'selected' : '' ?>>Server Operations</option>
                    </optgroup>

                    <optgroup label="📣 Marketing & Communication">
                        <option <?= ($prefillDept === 'Marketing Department') ? 'selected' : '' ?>>Marketing Department</option>
                        <option <?= ($prefillDept === 'Public Relations (PR)') ? 'selected' : '' ?>>Public Relations (PR)</option>
                        <option <?= ($prefillDept === 'Creative / Branding Team') ? 'selected' : '' ?>>Creative / Branding Team</option>
                        <option <?= ($prefillDept === 'Social Media Department') ? 'selected' : '' ?>>Social Media Department</option>
                        <option <?= ($prefillDept === 'User Acquisition & Retention Department') ? 'selected' : '' ?>>User Acquisition & Retention Department</option>
                    </optgroup>

                    <optgroup label="🔬 Research & Innovation">
                        <option <?= ($prefillDept === 'R&D Department') ? 'selected' : '' ?>>R&D Department</option>
                        <option <?= ($prefillDept === 'Game Data Science Department') ? 'selected' : '' ?>>Game Data Science Department</option>
                        <option <?= ($prefillDept === 'Innovation Lab / Prototyping Team') ? 'selected' : '' ?>>Innovation Lab / Prototyping Team</option>
                    </optgroup>
                </select>
                <?php if ($deptReadonly): ?>
                    <input type="hidden" name="department" value="<?= htmlspecialchars($prefillDept) ?>">
                <?php endif; ?>
            </div>

            <!-- Contact Info -->
            <div class="form-section">
                <h5>Contact Information</h5>
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <label>Contact Number</label>
                        <input type="text" name="contact" class="form-control"
                               value="<?= htmlspecialchars($lastTicket['contact'] ?? '') ?>" required>
                    </div>
                    <div style="flex:1;">
                        <label>Email (optional)</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($lastTicket['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Device & Issue -->
            <div class="form-section">
                <h5>Device Specification & Issue</h5>
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <label>Device Type</label>
                        <select name="deviceType" class="form-select" required onchange="toggleOtherDevice(this)">
                            <option value="">Select...</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Desktop">Desktop</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Smartphone">Smartphone</option>
                            <option value="Printer">Printer</option>
                            <option value="Router">Router</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" name="otherDevice" id="otherDevice" class="form-control mt-2"
                               placeholder="Specify device type" style="display:none;">
                    </div>
                    <div style="flex:1;">
                        <label>Device Name</label>
                        <input type="text" name="deviceName" class="form-control" required>
                    </div>
                </div>
                <div class="mt-3">
                    <label>Issue Description</label>
                    <textarea name="issueDescription" class="form-control" rows="3" required></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Submit Ticket</button>
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
