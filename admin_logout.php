<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Logged Out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="3;url=admin_login.php">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
        <div class="card shadow p-4 text-center" style="width: 400px;">
            <h2 class="text-danger mb-3">🚪 Logged Out</h2>
            <p>You have been logged out of the admin panel.</p>
            <p class="text-muted">Redirecting to admin login...</p>
            <a href="admin_login.php" class="btn btn-dark w-100 mt-3">Go to Admin Login</a>
        </div>
    </div>
</body>
</html>
