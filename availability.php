<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $court_id = $_POST["court_id"];
    $available_date = $_POST["available_date"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];

    // Basic validation
    if (empty($court_id) || empty($available_date) || empty($start_time) || empty($end_time)) {
        $message = "<div class='alert alert-danger'>⚠️ Please fill in all fields.</div>";
    } else {
        // Normalize date
        $available_date = date("Y-m-d", strtotime($available_date));

        // Insert availability
        $stmt = $conn->prepare("
            INSERT INTO availability (court_id, available_date, start_time, end_time)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $court_id, $available_date, $start_time, $end_time);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Availability added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Failed to add availability. Please try again.</div>";
        }
    }
}

// Fetch all courts for dropdown
$courts = $conn->query("SELECT court_id, name FROM courts");

// Fetch existing availability for table view
$avail = $conn->query("
    SELECT a.id, c.name AS court_name, a.available_date, a.start_time, a.end_time
    FROM availability a
    JOIN courts c ON a.court_id = c.court_id
    ORDER BY a.available_date DESC, a.start_time
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Court Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">🗓️ Manage Court Availability</h2>
        <div>
            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">⬅️ Back to Dashboard</a>
            <a href="admin_logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="card shadow p-4">
        <?= $message ?>

        <!-- Add new availability -->
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Court</label>
                <select name="court_id" class="form-select" required>
                    <option value="">Select Court</option>
                    <?php while ($c = $courts->fetch_assoc()): ?>
                        <option value="<?= $c['court_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="available_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </form>
    </div>

    <!-- Availability List -->
    <div class="card shadow p-4 mt-4">
        <h4>📋 Existing Availability</h4>
        <table class="table table-bordered table-hover mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Time Range</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($avail->num_rows > 0): ?>
                    <?php while ($a = $avail->fetch_assoc()): ?>
                        <tr>
                            <td><?= $a['id'] ?></td>
                            <td><?= htmlspecialchars($a['court_name']) ?></td>
                            <td><?= $a['available_date'] ?></td>
                            <td><?= date("g:i A", strtotime($a['start_time'])) ?> - <?= date("g:i A", strtotime($a['end_time'])) ?></td>
                            <td>
                                <a href="delete_availability.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this availability?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No availability set yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
