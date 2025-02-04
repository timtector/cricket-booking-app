<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center">Welcome to the Net Booking System</h1>
        <p class="text-center">Please log in or register to book a net.</p>
        <div class="d-flex justify-content-center mt-4">
            <?php if (!isset($_SESSION['user_logged_in'])): ?>
                <a href="login.php" class="btn btn-primary mx-2">Login</a>
                <a href="register.php" class="btn btn-secondary mx-2">Register</a>
            <?php else: ?>
                <a href="booking.php" class="btn btn-success mx-2">Book a Net</a>
                <a href="logout.php" class="btn btn-danger mx-2">Logout</a>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
