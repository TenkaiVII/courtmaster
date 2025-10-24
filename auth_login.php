<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$password = $data["password"] ?? "";

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Missing fields"]);
    exit;
}

// check user
$stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user["password_hash"])) {
    //78ghtb2 login success
    echo json_encode([
        "message" => "Login successful",
        "user" => [
            "id" => $user["user_id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"]
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
}

$stmt->close();
$conn->close();
?>
