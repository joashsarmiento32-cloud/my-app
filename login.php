<?php
include 'db_connect.php';
// This stops the white screen by showing errors
error_reporting(E_ALL); ini_set('display_errors', 1); 

if (isset($_POST['login_btn'])) {
    $uname = mysqli_real_escape_string($conn, $_POST['user']);
    $pass = $_POST['pass'];

    $sql = "SELECT id, password FROM users WHERE username = '$uname' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
        if ($pass == $user['password']) {
            $uid = $user['id'];
            
            // 1. Update the tracking table so the Admin can see you
            mysqli_query($conn, "REPLACE INTO user_tracking (user_id, last_active) VALUES ('$uid', NOW())");

            // 2. Redirect passing the ID in the URL (The Fix!)
            header("Location: account.php?my_id=" . $uid);
            exit();
        } else { $error = "Wrong password!"; }
    } else { $error = "User not found!"; }
}
?>
<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="height:100vh;">
    <div class="card p-4 mx-auto shadow" style="width:350px;">
        <h4 class="text-center">System Login</h4>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="user" class="form-control mb-2" placeholder="Username" required>
            <input type="password" name="pass" class="form-control mb-3" placeholder="Password" required>
            <button name="login_btn" class="btn btn-primary w-100">Sign In</button>
        </form>
    </div>
</body>
</html>