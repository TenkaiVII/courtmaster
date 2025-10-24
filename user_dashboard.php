<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
include "db.php";

$user_id = $_SESSION["user_id"];

// Fetch courts
$courts = $conn->query("SELECT * FROM courts");

// Handle booking request
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $court_id = $_POST["court_id"];
    $slot     = $_POST["slot"];
    $date     = $_POST["date"];

    // Extract start/end from "2:00 PM - 3:00 PM (1h)"
    $slot_parts = explode(" (", $slot);
    $time_range = $slot_parts[0];
    list($start_time, $end_time) = explode(" - ", $time_range);

    // Convert to 24h
    $start_time_24 = date("H:i:s", strtotime($start_time));
    $end_time_24   = date("H:i:s", strtotime($end_time));

    // Price rules
    $duration = (strtotime($end_time) - strtotime($start_time)) / 60;
    if ($duration == 30) $price = 50;
    elseif ($duration == 60) $price = 90;
    elseif ($duration == 120) $price = 170;
    elseif ($duration == 180) $price = 240;
    else $price = 0;

    // Check overlap again for safety
    $sql = "SELECT * FROM bookings 
            WHERE court_id=? AND booking_date=? AND status='confirmed'
              AND NOT (? >= end_time OR ? <= start_time)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $court_id, $date, $start_time_24, $end_time_24);
    $stmt->execute();
    $conflicts = $stmt->get_result();

    if ($conflicts->num_rows > 0) {
        $message = "<div class='alert alert-danger'>❌ Slot already booked!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, price, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
        $stmt->bind_param("iisssd", $user_id, $court_id, $date, $start_time_24, $end_time_24, $price);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Booking successful!</div>";
        } else {
            $message = "<div class='alert alert-danger'>⚠️ Failed to book slot.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Fetch slots dynamically
        async function loadSlots() {
            let courtId = document.getElementById("court").value;
            let date = document.getElementById("date").value;
            if (!courtId || !date) return;

            let response = await fetch("fetch_slots.php?court_id=" + courtId + "&date=" + date);
            let slots = await response.json();

            let slotSelect = document.getElementById("slot");
            slotSelect.innerHTML = "<option value=''>Select Slot</option>";

            slots.forEach(s => {
                let durationLabel = (s.duration === 30) ? "30m" : (s.duration/60) + "h";
                let label = s.start + " - " + s.end + " (" + durationLabel + ")";

                if (s.status === "booked") {
                    label += " (Booked)";
                } else if (s.status === "maintenance") {
                    label = "~~ " + label + " ~~";
                }

                let opt = document.createElement("option");
                opt.value = s.start + " - " + s.end + " (" + durationLabel + ")";
                opt.textContent = label;

                // Disable if not available
                if (s.status !== "available") {
                    opt.disabled = true;
                }

                slotSelect.appendChild(opt);
            });
        }
    </script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">⚽ Welcome, <?= htmlspecialchars($_SESSION["user_name"] ?? "User") ?>!</h2>
            <div>
                <a href="my_bookings.php" class="btn btn-outline-primary btn-sm">📋 My Bookings</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>

        <?= $message ?>

        <!-- Booking form -->
        <form method="post" class="row g-3">
            <!-- Court -->
            <div class="col-md-4">
                <label class="form-label">Court</label>
                <select name="court_id" id="court" class="form-select" onchange="loadSlots()" required>
                    <option value="">Select Court</option>
                    <?php while ($c = $courts->fetch_assoc()): ?>
                        <option value="<?= $c['court_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Date -->
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" id="date" class="form-control" onchange="loadSlots()" required>
            </div>

            <!-- Slots -->
            <div class="col-md-4">
                <label class="form-label">Available Slots</label>
                <select name="slot" id="slot" class="form-select" required>
                    <option value="">Select Slot</option>
                </select>
            </div>

            <!-- Submit -->
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Book</button>
            </div>
        </form>

        <!-- Legend -->
        <div class="mt-3 text-muted">
            <small>
                ✅ Normal = Available &nbsp; | &nbsp; ❌ (Booked) = Already taken &nbsp; | &nbsp; ~~Strike~~ = Maintenance
            </small>
        </div>
    </div>
</div>
</body>
</html>
