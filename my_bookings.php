<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
include "db.php";

$user_id = $_SESSION["user_id"];
$message = "";

// Handle booking cancellation
if (isset($_GET["cancel_id"])) {
    $cancel_id = intval($_GET["cancel_id"]);

    // Fetch booking details first
    $stmt = $conn->prepare("SELECT booking_id, court_id, booking_date, start_time, end_time, status 
                            FROM bookings WHERE booking_id=? AND user_id=? AND status='confirmed'");
    $stmt->bind_param("ii", $cancel_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        $current_time = strtotime(date("Y-m-d H:i:s"));
        $booking_end = strtotime($booking["booking_date"] . " " . $booking["end_time"]);

        if ($booking_end > $current_time) {
            // Future booking – allow cancellation and free slot
            $stmt2 = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=?");
            $stmt2->bind_param("i", $cancel_id);
            if ($stmt2->execute()) {
                $message = "<div class='alert alert-success'>✅ Booking cancelled successfully. The slot is now available again.</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to cancel booking. Please try again.</div>";
            }
        } else {
            // Past booking – only mark as cancelled
            $stmt2 = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=?");
            $stmt2->bind_param("i", $cancel_id);
            $stmt2->execute();
            $message = "<div class='alert alert-warning'>⚠️ Booking cancelled, but time has already passed — slot not reopened.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>⚠️ Booking not found or already cancelled.</div>";
    }
}

// Fetch all bookings by user
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.status, b.price, 
           c.name AS court_name
    FROM bookings b
    JOIN courts c ON b.court_id = c.court_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.start_time
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">📋 My Bookings</h2>
            <div>
                <a href="user_dashboard.php" class="btn btn-secondary btn-sm">⬅️ Back to Dashboard</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>

        <?= $message ?>

        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Court</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Price (AED)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings): ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= $b["booking_id"] ?></td>
                            <td><?= htmlspecialchars($b["court_name"]) ?></td>
                            <td><?= htmlspecialchars($b["booking_date"]) ?></td>
                            <td><?= date("g:i A", strtotime($b["start_time"])) ?> - <?= date("g:i A", strtotime($b["end_time"])) ?></td>
                            <td><?= $b["price"] ?></td>
                            <td>
                                <?php if ($b["status"] === "confirmed"): ?>
                                    <span class="badge bg-success">Confirmed</span>
                                <?php elseif ($b["status"] === "cancelled"): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php elseif ($b["status"] === "completed"): ?>
                                    <span class="badge bg-primary">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= ucfirst($b["status"]) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($b["status"] === "confirmed"): ?>
                                    <a href="?cancel_id=<?= $b['booking_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')">Cancel</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
