<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Filters for bookings
$where = "1=1";
$params = [];
$types = "";

if (!empty($_GET["court_id"])) {
    $where .= " AND b.court_id = ?";
    $params[] = $_GET["court_id"];
    $types .= "i";
}
if (!empty($_GET["status"])) {
    $where .= " AND b.status = ?";
    $params[] = $_GET["status"];
    $types .= "s";
}
if (!empty($_GET["date"])) {
    $where .= " AND b.booking_date = ?";
    $params[] = $_GET["date"];
    $types .= "s";
} else {
    $_GET["date"] = date("Y-m-d");
}

// Fetch bookings
$sql = "SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.status, b.price,
               u.name AS user_name, c.name AS court_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN courts c ON b.court_id = c.court_id
        WHERE $where
        ORDER BY b.booking_date, b.start_time";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Fetch availability
$avail_stmt = $conn->prepare("SELECT a.id, a.available_date, a.start_time, a.end_time, c.name AS court_name
                              FROM availability a
                              JOIN courts c ON a.court_id = c.court_id
                              WHERE a.available_date = ?
                              ORDER BY a.start_time");
$avail_stmt->bind_param("s", $_GET["date"]);
$avail_stmt->execute();
$availability = $avail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    async function loadRevenue(type) {
        const container = document.getElementById("revenueContainer");
        container.innerHTML = "<div class='text-center p-3'>Loading...</div>";

        let res = await fetch("fetch_revenue.php?type=" + type);
        let data = await res.json();

        let html = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4>💰 ${data.label}</h4>
                <div>
                    <button onclick="loadRevenue('day')" class="btn btn-sm ${type==='day'?'btn-primary':'btn-outline-primary'}">Day</button>
                    <button onclick="loadRevenue('week')" class="btn btn-sm ${type==='week'?'btn-primary':'btn-outline-primary'}">Week</button>
                    <button onclick="loadRevenue('month')" class="btn btn-sm ${type==='month'?'btn-primary':'btn-outline-primary'}">Month</button>
                </div>
            </div>
            <div class="alert alert-success text-center fw-bold mb-3">
                🏆 All-Time Total Revenue: ${parseFloat(data.total_revenue).toFixed(2)} AED
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr><th>Period</th><th>Total Revenue (AED)</th></tr>
                    </thead>
                    <tbody>
        `;

        if (data.rows.length === 0) {
            html += `<tr><td colspan="2" class="text-center">No revenue data found.</td></tr>`;
        } else {
            data.rows.forEach(r => {
                html += `<tr><td>${r.label}</td><td>${parseFloat(r.total).toFixed(2)}</td></tr>`;
            });
        }

        html += `</tbody></table></div>`;
        container.innerHTML = html;
    }

    window.onload = () => loadRevenue("day");
    </script>
</head>
<body class="bg-light">
<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">⚙️ Admin Dashboard</h1>
        <div>
            Welcome, <?= htmlspecialchars($_SESSION["admin_name"]) ?> |
            <a href="admin_logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <!-- Navigation -->
    <div class="mb-4">
        <a href="add_booking.php" class="btn btn-success btn-sm">➕ Add Booking</a>
        <a href="maintenance.php" class="btn btn-warning btn-sm">🛠 Manage Maintenance</a>
        <a href="availability.php" class="btn btn-info btn-sm">🗓 Manage Availability</a>
    </div>

    <!-- Availability -->
    <h4>📅 Court Availability (<?= $_GET['date'] ?>)</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr><th>ID</th><th>Court</th><th>Date</th><th>Time</th></tr>
            </thead>
            <tbody>
                <?php if ($availability): foreach ($availability as $a): ?>
                    <tr>
                        <td><?= $a["id"] ?></td>
                        <td><?= htmlspecialchars($a["court_name"]) ?></td>
                        <td><?= $a["available_date"] ?></td>
                        <td><?= date("g:i A", strtotime($a["start_time"])) ?> - <?= date("g:i A", strtotime($a["end_time"])) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center">No availability set for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bookings -->
    <h4>📖 Bookings</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr><th>ID</th><th>User</th><th>Court</th><th>Date</th><th>Time</th><th>Status</th><th>Price</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if ($bookings): foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b["booking_id"] ?></td>
                        <td><?= htmlspecialchars($b["user_name"]) ?></td>
                        <td><?= htmlspecialchars($b["court_name"]) ?></td>
                        <td><?= $b["booking_date"] ?></td>
                        <td><?= date("g:i A", strtotime($b["start_time"])) ?> - <?= date("g:i A", strtotime($b["end_time"])) ?></td>
                        <td><?= ucfirst($b["status"]) ?></td>
                        <td><?= number_format($b["price"], 2) ?> AED</td>
                        <td>
                            <a href="edit_booking.php?id=<?= $b['booking_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete_booking.php?id=<?= $b['booking_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this booking?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Revenue Section -->
    <div id="revenueContainer"></div>

</div>
</body>
</html>
