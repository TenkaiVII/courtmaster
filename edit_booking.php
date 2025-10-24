<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Get booking details
if (!isset($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit;
}
$booking_id = intval($_GET["id"]);
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Update booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = $_POST["status"];
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_id=?");
    $stmt->bind_param("si", $status, $booking_id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="mb-3">✏️ Edit Booking</h2>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">User ID</label>
                    <input type="text" class="form-control" value="<?= $booking['user_id'] ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Court ID</label>
                    <input type="text" class="form-control" value="<?= $booking['court_id'] ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="text" class="form-control" value="<?= $booking['booking_date'] ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Time</label>
                    <input type="text" class="form-control" 
                           value="<?= date("g:i A", strtotime($booking['start_time'])) ?> - <?= date("g:i A", strtotime($booking['end_time'])) ?>" 
                           disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="text" class="form-control" value="<?= $booking['price'] ?> AED" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="confirmed" <?= $booking['status']=="confirmed"?"selected":"" ?>>Confirmed</option>
                        <option value="cancelled" <?= $booking['status']=="cancelled"?"selected":"" ?>>Cancelled</option>
                        <option value="completed" <?= $booking['status']=="completed"?"selected":"" ?>>Completed</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="admin_dashboard.php" class="btn btn-secondary">⬅ Back</a>
                    <button type="submit" class="btn btn-success">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
