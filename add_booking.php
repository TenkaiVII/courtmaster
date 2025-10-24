<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $court_id = $_POST["court_id"];
    $date = $_POST["booking_date"];
    $start_time = date("H:i", strtotime($_POST["start_time"])); // convert to 24h
    $end_time = date("H:i", strtotime($_POST["end_time"]));     // convert to 24h
    $price = $_POST["price"];

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, price, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
    $stmt->bind_param("iisssd", $user_id, $court_id, $date, $start_time, $end_time, $price);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Error adding booking: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="mb-3">➕ Add New Booking</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <!-- User -->
                <div class="col-md-6">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select User</option>
                        <?php
                        $users = $conn->query("SELECT * FROM users");
                        while ($u = $users->fetch_assoc()): ?>
                            <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Court -->
                <div class="col-md-6">
                    <label class="form-label">Court</label>
                    <select name="court_id" class="form-select" required>
                        <option value="">Select Court</option>
                        <?php
                        $courts = $conn->query("SELECT * FROM courts");
                        while ($c = $courts->fetch_assoc()): ?>
                            <option value="<?= $c['court_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Date -->
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="booking_date" class="form-control" required>
                </div>

                <!-- Start Time -->
                <div class="col-md-4">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                    <small class="text-muted">Example: 01:00 PM</small>
                </div>

                <!-- End Time -->
                <div class="col-md-4">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                    <small class="text-muted">Example: 03:00 PM</small>
                </div>

                <!-- Price -->
                <div class="col-md-6">
                    <label class="form-label">Price (AED)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex justify-content-between">
                    <a href="admin_dashboard.php" class="btn btn-secondary">⬅ Cancel</a>
                    <button type="submit" class="btn btn-success">💾 Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
