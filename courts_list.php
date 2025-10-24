<?php
header("Content-Type: application/json");
include "db.php";

$sql = "SELECT * FROM courts";
$result = $conn->query($sql);

$courts = [];
while ($row = $result->fetch_assoc()) {
    $courts[] = $row;
}

echo json_encode($courts);

$conn->close();
?>
