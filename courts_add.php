<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name       = $data["name"] ?? "";
$location   = $data["location"] ?? "";
$court_type = $data["court_type"] ?? "";
$price      = $data["price_per_slot"] ?? 0;
$image_url  = $data["image_url"] ?? "";
$open_time  = $data["open_time"] ?? "08:00:00";
$close_time = $data["close_time"] ?? "22:00:00";
$created_by = $data["created_by"] ?? 1; // later we’ll take this from logged-in admin

if (!$name || !$location) {
    http_response_code(400);
    echo json_encode(["error" => "Name and location are required"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO courts 
    (name, location, court_type, price_per_slot, image_url, open_time, close_time, created_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

// 🟢 Fixed bind types
// s = string, d = double (float), i = integer
$stmt->bind_param("sssdsisi", $name, $location, $court_type, $price, $image_url, $open_time, $close_time, $created_by);

if ($stmt->execute()) {
    echo json_encode(["message" => "Court added successfully", "court_id" => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to add court", "details" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
