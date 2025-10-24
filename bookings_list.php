<?php
header("Content-Type: application/json");
include "db.php";

$user_id = $_GET["user_id"] ?? 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["error" => "User ID required"]);
    exit;
}

$sql = "SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.status,
               c.name AS court_name, c.location
        FROM bookings b
        JOIN courts c ON b.court_id = c.court_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date, b.start_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode($bookings);

$stmt->close();
$conn->close();
?>
