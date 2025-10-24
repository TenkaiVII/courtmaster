<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Ensure booking ID is provided
if (!isset($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit;
}
$booking_id = intval($_GET["id"]);

// Fetch booking details
$stmt = $conn->prepare("SELECT b.*, u.name AS user_name, c.name AS court_name 
                        FROM bookings b
                        JOIN users u ON b.user_id = u.user_id
                        JOIN courts c ON b.court_id = c.court_id
                        WHERE b.booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="mb-3 text-danger">🗑️ Delete Booking</h2>
            <p>Are you sure you want to delete the following booking?</p>

            <ul class="list-group mb-3">
                <li class="list-group-item"><strong>User:</strong> <?= htmlspecialchars($booking['user_name']) ?></li>
                <li class="list-group-item"><strong>Court:</strong> <?= htmlspecialchars($booking['court_name']) ?></li>
                <li class="list-group-item"><strong>Date:</strong> <?= $booking['booking_date'] ?></li>
                <li class="list-group-item"><strong>Time:</strong> 
                    <?= date("g:i A", strtotime($booking['start_time'])) ?> - 
                    <?= date("g:i A", strtotime($booking['end_time'])) ?>
                </li>
                <li class="list-group-item"><strong>Price:</strong> <?= $booking['price'] ?> AED</li>
                <li class="list-group-item"><strong>Status:</strong> <?= ucfirst($booking['status']) ?></li>
            </ul>

            <form method="post" class="d-flex justify-content-between">
                <a href="admin_dashboard.php" class="btn btn-secondary">⬅ Cancel</a>
                <button type="submit" class="btn btn-danger">🗑️ Confirm Delete</button>
            </form>
        </div>
    </div>
</body>
</html>
