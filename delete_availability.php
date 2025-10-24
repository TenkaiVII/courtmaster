<?php
session_start();
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

include "db.php";

if (!isset($_GET["id"])) {
    header("Location: availability.php");
    exit;
}

$id = intval($_GET["id"]);

// Delete the record safely
$stmt = $conn->prepare("DELETE FROM availability WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: availability.php?msg=deleted");
    exit;
} else {
    echo "<h3>❌ Failed to delete availability.</h3>";
}
