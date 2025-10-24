<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$booking_id = $data["booking_id"] ?? 0;

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(["error" => "Booking ID required"]);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Booking cancelled"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to cancel booking", "details" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
