<?php
require_once "database.php";

class Ticket {
    public $firstName;
    public $lastName;
    public $department;
    public $contact;
    public $email;
    public $deviceType;
    public $deviceName;
    public $issueDescription;
    public $serviceProvider;
    public $status;

    // new: account id for linking tickets to accounts/users
    public $accountId;

    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addTicket() {
        // Note: accountId column assumed present in ticket table (nullable)
        $sql = "INSERT INTO ticket (
                    firstName, lastName, department, contact, email, 
                    deviceType, deviceName, issueDescription,
                    status, creationDate, recentUpdate, accountId
                ) VALUES (
                    :firstName, :lastName, :department, :contact, :email,
                    :deviceType, :deviceName, :issueDescription,
                    :status, :creationDate, :recentUpdate, :accountId
                )";

        $query = $this->db->connect()->prepare($sql);

        $status = "Pending";
        $creationDate = date('Y-m-d H:i:s');
        $recentUpdate = $creationDate;

        $query->bindParam(':firstName', $this->firstName);
        $query->bindParam(':lastName', $this->lastName);
        $query->bindParam(':department', $this->department);
        $query->bindParam(':contact', $this->contact);
        $query->bindParam(':email', $this->email);
        $query->bindParam(':deviceType', $this->deviceType);
        $query->bindParam(':deviceName', $this->deviceName);
        $query->bindParam(':issueDescription', $this->issueDescription);
        $query->bindParam(':status', $status);
        $query->bindParam(':creationDate', $creationDate);
        $query->bindParam(':recentUpdate', $recentUpdate);

        // bind accountId (may be null)
        if (isset($this->accountId)) {
            $query->bindParam(':accountId', $this->accountId);
        } else {
            $null = null;
            $query->bindParam(':accountId', $null, PDO::PARAM_NULL);
        }

        return $query->execute();
    }

    public function getAllTickets() {
        $sql = "SELECT * FROM ticket ORDER BY creationDate DESC";
        $query = $this->db->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMostRecentTicket() {
        $sql = "SELECT * FROM ticket ORDER BY creationDate DESC LIMIT 1";
        $query = $this->db->connect()->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function editTicket($id, $updateAccountFields = false) {
    $recentUpdate = date('Y-m-d H:i:s');

    if ($updateAccountFields) {
        // Update all fields including firstName, lastName, department
        $sql = "UPDATE ticket SET
                    firstName = :firstName,
                    lastName = :lastName,
                    department = :department,
                    contact = :contact,
                    email = :email,
                    deviceType = :deviceType,
                    deviceName = :deviceName,
                    issueDescription = :issueDescription,
                    recentUpdate = :recentUpdate
                WHERE id = :id";

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':firstName', $this->firstName);
        $query->bindParam(':lastName', $this->lastName);
        $query->bindParam(':department', $this->department);
    } else {
        // Only update contact, email, device info, and issue description
        $sql = "UPDATE ticket SET
                    contact = :contact,
                    email = :email,
                    deviceType = :deviceType,
                    deviceName = :deviceName,
                    issueDescription = :issueDescription,
                    recentUpdate = :recentUpdate
                WHERE id = :id";

        $query = $this->db->connect()->prepare($sql);
    }

    // Bind common parameters
    $query->bindParam(':contact', $this->contact);
    $query->bindParam(':email', $this->email);
    $query->bindParam(':deviceType', $this->deviceType);
    $query->bindParam(':deviceName', $this->deviceName);
    $query->bindParam(':issueDescription', $this->issueDescription);
    $query->bindParam(':recentUpdate', $recentUpdate);
    $query->bindParam(':id', $id);

    return $query->execute();
}


public function getTicketById($id) {
    $sql = "
        SELECT 
            t.*, 
            ei.firstName AS accountFirstName,
            ei.lastName AS accountLastName,
            ei.department AS accountDepartment,
            a.workEmail AS accountEmail
        FROM ticket t
        LEFT JOIN accounts a ON t.accountId = a.id
        LEFT JOIN employee_info ei ON ei.accountId = a.id
        WHERE t.id = :id
    ";

    $query = $this->db->connect()->prepare($sql);
    $query->bindParam(':id', $id);
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC);
}



    public function deleteTicket($id) {
        $sql = "UPDATE ticket 
                SET status = 'Deleted', recentUpdate = :recentUpdate 
                WHERE id = :id";

        $query = $this->db->connect()->prepare($sql);
        $recentUpdate = date('Y-m-d H:i:s');
        $query->bindParam(':recentUpdate', $recentUpdate);
        $query->bindParam(':id', $id);

        return $query->execute();
    }

    public function adminUpdateTicket($id) {
        $sql = "UPDATE ticket 
                SET serviceProvider = :serviceProvider, 
                    status = :status, 
                    recentUpdate = :recentUpdate
                WHERE id = :id";

        $query = $this->db->connect()->prepare($sql);
        $recentUpdate = date('Y-m-d H:i:s');

        $query->bindParam(':serviceProvider', $this->serviceProvider);
        $query->bindParam(':status', $this->status);
        $query->bindParam(':recentUpdate', $recentUpdate);
        $query->bindParam(':id', $id);

        return $query->execute();
    }

    public function getServiceProviders() {
        $sql = "SELECT name FROM service_providers ORDER BY name ASC";
        $query = $this->db->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function addServiceProvider($name) {
        $sql = "INSERT INTO service_providers (name) VALUES (:name)";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':name', $name);
        return $query->execute();
    }

    public function permanentDeleteTicket($id) {
        $sql = "DELETE FROM ticket WHERE id = :id";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':id', $id);
        return $query->execute();
    }

    // new: get tickets by account/user id (employee)
    public function getTicketsByUser($accountId) {
        $sql = "SELECT * FROM ticket WHERE accountId = :accountId ORDER BY creationDate DESC";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':accountId', $accountId);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceProviderCounts() {
    $sql = "SELECT serviceProvider, COUNT(*) AS assignments
            FROM ticket
            WHERE serviceProvider IS NOT NULL AND status != 'Deleted'
            GROUP BY serviceProvider";
    $stmt = $this->db->connect()->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = [];
    foreach ($result as $row) {
        $counts[$row['serviceProvider']] = (int)$row['assignments'];
    }
    return $counts;
}

}
?>
