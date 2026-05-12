<?php
ob_start(); 
session_start();
include 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login_btn'])) {
    $username = mysqli_real_escape_string($conn, $_POST['user']);
    $password = mysqli_real_escape_string($conn, $_POST['pass']);

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password' LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['admin_logged_in'] = true; 
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['fullname'];

        header("Location: index.php"); 
        exit();
    } else {
        $error = "Access Denied: Invalid Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Hicming | Admin Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        /* VIDEO BACKGROUND - Optimized */
        #video-bg {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            object-fit: cover;
            filter: brightness(0.65) contrast(1.1); 
        }

        .main-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(18, 43, 33, 0.25);
            padding: 20px;
        }

        /* CRYSTAL GLASS CARD */
        .login-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 40px;
            padding: 65px 45px 45px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* THE GLOWING LOGO STYLING */
        .seal-container {
            position: absolute;
            top: -55px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 6px;
            border-radius: 50%;
            /* THE GLOW DEFINITION */
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 0 0 30px rgba(46, 204, 113, 0.6); 
            animation: sealGlow 3s ease-in-out infinite;
        }

        /* PULSING GLOW ANIMATION */
        @keyframes sealGlow {
            0% { box-shadow: 0 0 10px rgba(255, 255, 255, 0.6), 0 0 20px rgba(46, 204, 113, 0.4); }
            50% { box-shadow: 0 0 20px rgba(255, 255, 255, 1), 0 0 45px rgba(46, 204, 113, 0.8); }
            100% { box-shadow: 0 0 10px rgba(255, 255, 255, 0.6), 0 0 20px rgba(46, 204, 113, 0.4); }
        }

        .seal-container img { 
            width: 110px; 
            height: 110px; 
            border-radius: 50%;
            object-fit: cover;
        }

        h2 { 
            color: #fff; 
            font-weight: 800; 
            letter-spacing: 1.5px; 
            margin-top: 15px; 
            text-shadow: 2px 4px 10px rgba(0,0,0,0.5);
            font-size: 1.7rem;
        }
        
        p.tagline { 
            color: rgba(255,255,255,0.85); 
            font-size: 0.85rem; 
            margin-bottom: 35px; 
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* INPUTS - Glowing Focus */
        .form-control {
            background: rgba(255, 255, 255, 0.98);
            border: none;
            border-radius: 15px;
            padding: 15px 22px;
            color: #1b4332;
            font-weight: 500;
            margin-bottom: 18px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.2), 0 0 20px rgba(46, 204, 113, 0.4); /* Glow on focus */
            transform: scale(1.01);
            color: #1b4332;
        }

        /* ECO BUTTON */
        .btn-submit {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: #fff;
            border: none;
            border-radius: 15px;
            padding: 16px;
            width: 100%;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .btn-submit:hover {
            filter: brightness(1.1);
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.4), 0 0 15px rgba(46, 204, 113, 0.3); /* Button hover glow */
        }

        .error-msg {
            background: rgba(230, 57, 70, 0.9);
            color: #fff;
            border-radius: 12px;
            padding: 12px;
            font-size: 0.85rem;
            margin-bottom: 25px;
            animation: shake 0.5s;
            border-left: 4px solid #fff;
        }

        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            25% {transform: translateX(-6px);}
            75% {transform: translateX(6px);}
        }

        .home-link {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .home-link:hover {
            color: #fff;
            opacity: 1;
        }
    </style>
</head>
<body>

    <video autoplay muted loop playsinline id="video-bg">
        <source src="hicming.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>

    <div class="main-wrapper">
        <div class="login-card">
            
            <div class="seal-container">
                <img src="logo.jpg" alt="Barangay Hicming Seal">
            </div>

            <h2>ADMIN PORTAL</h2>
            <p class="tagline">Barangay Hicming Management System</p>

            <?php if(isset($error)): ?>
                <div class="error-msg text-start">
                    <i class="fas fa-shield-alt me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="user" class="form-control" placeholder="Admin Username" required autocomplete="off">
                <input type="password" name="pass" class="form-control" placeholder="Password" required>

                <button type="submit" name="login_btn" class="btn-submit">
                    Authorize Access <i class="fas fa-lock-open ms-2"></i>
                </button>
            </form>

            
        </div>
    </div>

</body>
</html>