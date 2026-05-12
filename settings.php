<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

// Handle Saving
if(isset($_POST['btn_save_settings'])){
    $name = mysqli_real_escape_string($conn, $_POST['gcash_name']);
    $number = mysqli_real_escape_string($conn, $_POST['gcash_number']);
    
    // Check if record exists
    $check = $conn->query("SELECT * FROM payment_settings LIMIT 1");
    if($check->num_rows > 0){
        $conn->query("UPDATE payment_settings SET gcash_name='$name', gcash_number='$number' WHERE id=1");
    } else {
        $conn->query("INSERT INTO payment_settings (gcash_name, gcash_number) VALUES ('$name', '$number')");
    }
    $msg = "Payment details updated successfully!";
}

// Fetch current values
$settings = $conn->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();
$g_name = $settings['gcash_name'] ?? '';
$g_num = $settings['gcash_number'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings | BMIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fcfcfd; font-family: 'Inter', sans-serif; }
        .sidebar-container { width: 280px; position: fixed; height: 100vh; background: #fff; border-right: 1px solid #f1f5f9; }
        .main-content { margin-left: 280px; padding: 50px; }
        .settings-card { background: white; border-radius: 24px; border: 1px solid #f1f5f9; padding: 40px; max-width: 600px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); }
        .form-control { border-radius: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; background: #fcfcfd; margin-bottom: 20px;}
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <h2 class="fw-bold mb-1">System Settings</h2>
    <p class="text-secondary mb-5">Configure payment details for resident requests.</p>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="POST">
            <h5 class="fw-bold mb-4"><i class="fa-solid fa-mobile-screen-button me-2 text-primary"></i> GCash Configuration</h5>
            
            <label class="small fw-bold text-muted mb-2 text-uppercase">Account Name</label>
            <input type="text" name="gcash_name" class="form-control" value="<?= htmlspecialchars($g_name) ?>" placeholder="e.g. BARANGAY ADMIN" required>

            <label class="small fw-bold text-muted mb-2 text-uppercase">GCash Number</label>
            <input type="text" name="gcash_number" class="form-control" value="<?= htmlspecialchars($g_num) ?>" placeholder="e.g. 09123456789" required>

            <button type="submit" name="btn_save_settings" class="btn btn-primary w-100 py-3 fw-bold rounded-4 shadow-sm">
                Update Payment Details
            </button>
        </form>
    </div>
</main>

</body>
</html>