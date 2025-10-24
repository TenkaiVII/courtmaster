<?php
header("Content-Type: application/json; charset=utf-8");
include "db.php";

$type = $_GET["type"] ?? "day";

// All-time total
$total_sql = "SELECT SUM(price) AS total FROM bookings WHERE status IN ('confirmed', 'completed')";
$total_res = $conn->query($total_sql);
$total = $total_res->fetch_assoc()["total"] ?? 0;

if ($type === "week") {
    $sql = "SELECT YEARWEEK(booking_date, 1) AS period, 
                   CONCAT('Week ', WEEK(booking_date, 1)) AS label, 
                   SUM(price) AS total
            FROM bookings
            WHERE status IN ('confirmed','completed')
            GROUP BY YEARWEEK(booking_date, 1)
            ORDER BY period DESC
            LIMIT 8";
    $label = "Weekly Revenue";
} elseif ($type === "month") {
    $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m') AS period, 
                   DATE_FORMAT(booking_date, '%M %Y') AS label, 
                   SUM(price) AS total
            FROM bookings
            WHERE status IN ('confirmed','completed')
            GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
            ORDER BY period DESC
            LIMIT 6";
    $label = "Monthly Revenue";
} else {
    $sql = "SELECT DATE(booking_date) AS period, 
                   DATE(booking_date) AS label, 
                   SUM(price) AS total
            FROM bookings
            WHERE status IN ('confirmed','completed')
            GROUP BY DATE(booking_date)
            ORDER BY period DESC
            LIMIT 7";
    $label = "Daily Revenue";
}

$result = $conn->query($sql);
$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode([
    "label" => $label,
    "total_revenue" => $total,
    "rows" => $rows
]);
