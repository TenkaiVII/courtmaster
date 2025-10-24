<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

include "db.php";

// Handle new maintenance slot
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $court_id = $_POST["court_id"];
    $block_date = $_POST["block_date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $reason = $_POST["reason"];

    $stmt = $conn->prepare("INSERT INTO maintenance (court_id, block_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $court_id, $block_date, $start_time, $end_time, $reason);
    $stmt->execute();
    $stmt->close();
}

// Handle delete
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $conn->query("DELETE FROM maintenance WHERE id = $id");
}

// Fetch courts
$courts = $conn->query("SELECT court_id, name FROM courts");

// Fetch all maintenance records
$sql = "SELECT m.id, m.block_date AS date, m.start_time, m.end_time, m.reason, c.name AS court_name
        FROM maintenance m
        JOIN courts c ON m.court_id = c.court_id
        ORDER BY m.block_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Slots</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="card shadow p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>🛠️ Manage Maintenance Slots</h2>
            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">⬅ Back to Dashboard</a>
        </div>

        <!-- Add New Maintenance Slot -->
        <form method="post" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Court</label>
                <select name="court_id" class="form-select" required>
                    <option value="">Select Court</option>
                    <?php while ($c = $courts->fetch_assoc()): ?>
                        <option value="<?= $c['court_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="block_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Cleaning">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">Add Maintenance Slot</button>
            </div>
        </form>

        <!-- Maintenance List -->
        <h4>Existing Maintenance Slots</h4>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row["id"] ?></td>
                    <td><?= htmlspecialchars($row["court_name"]) ?></td>
                    <td><?= $row["date"] ?></td>
                    <td><?= date("g:i A", strtotime($row["start_time"])) ?> - <?= date("g:i A", strtotime($row["end_time"])) ?></td>
                    <td><?= htmlspecialchars($row["reason"]) ?></td>
                    <td>
                        <a href="?delete=<?= $row["id"] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this maintenance slot?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
