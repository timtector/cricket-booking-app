<?php
session_start();
include 'db.php'; // This file must set up your $conn connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input values
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Hash the password using BCRYPT
    $password = password_hash($password_raw, PASSWORD_BCRYPT);

    // Check if the username or email already exists in the users table
    $checkQuery  = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $error = "Username or email already exists. Please try another.";
    } else {
        // Insert new user into the database, setting role to 'user'
        $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'user')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = mysqli_insert_id($conn);
            $_SESSION['role'] = 'user';
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit();
        } else {
            $error = "Error: Could not register. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'nav.php'; ?>
  <div class="container mt-5">
      <h1 class="text-center">Register</h1>
      <?php if (isset($error)): ?>
          <div class="alert alert-danger text-center"><?= $error; ?></div>
      <?php endif; ?>
      <form method="POST" action="" class="mt-4 mx-auto" style="max-width: 400px;">
          <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Register</button>
      </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
