<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id      = $data["user_id"] ?? 0;
$court_id     = $data["court_id"] ?? 0;
$booking_date = $data["booking_date"] ?? "";
$start_time   = $data["start_time"] ?? "";
$end_time     = $data["end_time"] ?? "";
$price        = $data["price"] ?? 0;

if (!$user_id || !$court_id || !$booking_date || !$start_time || !$end_time) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// ✅ Check for conflicts (confirmed bookings + maintenance blocks)
$sql = "SELECT 'booking' AS type FROM bookings 
        WHERE court_id = ? 
          AND booking_date = ? 
          AND status = 'confirmed'
          AND NOT (? >= end_time OR ? <= start_time)
        UNION
        SELECT 'maintenance' AS type FROM maintenance 
        WHERE court_id = ? 
          AND block_date = ? 
          AND NOT (? >= end_time OR ? <= start_time)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ississs", 
    $court_id, $booking_date, $start_time, $end_time, 
    $court_id, $booking_date, $start_time, $end_time
);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $conflict = $result->fetch_assoc();

    if ($conflict["type"] === "booking") {
        http_response_code(409);
        echo json_encode(["error" => "Time slot already booked"]);
    } elseif ($conflict["type"] === "maintenance") {
        http_response_code(409);
        echo json_encode(["error" => "Time slot blocked for maintenance"]);
    }
    exit;
}

// ✅ Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, price) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisssd", $user_id, $court_id, $booking_date, $start_time, $end_time, $price);

if ($stmt->execute()) {
    echo json_encode(["message" => "Booking successful", "booking_id" => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create booking", "details" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
