<?php
include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['gcash_name'];
    $number = $_POST['gcash_number'];
    $qr_path = $_POST['current_qr'];

    if (!empty($_FILES['qr_code']['name'])) {
        $target_dir = "../uploads/";
        $qr_path = "uploads/" . basename($_FILES["qr_code"]["name"]);
        move_uploaded_file($_FILES["qr_code"]["tmp_name"], "../" . $qr_path);
    }

    $conn->query("UPDATE payment_settings SET gcash_name='$name', gcash_number='$number', gcash_qr='$qr_path' WHERE id=1");
    echo "<script>alert('Settings Updated!');</script>";
}

$settings = $conn->query("SELECT * FROM payment_settings WHERE id=1")->fetch_assoc();
?>

<div class="container mt-4">
    <h3>GCash Payment Settings</h3>
    <form method="POST" enctype="multipart/form-data" class="card p-4">
        <input type="hidden" name="current_qr" value="<?= $settings['gcash_qr'] ?>">
        
        <div class="mb-3">
            <label>GCash Account Name</label>
            <input type="text" name="gcash_name" class="form-control" value="<?= $settings['gcash_name'] ?>" required>
        </div>
        
        <div class="mb-3">
            <label>GCash Mobile Number</label>
            <input type="text" name="gcash_number" class="form-control" value="<?= $settings['gcash_number'] ?>" required>
        </div>
        
        <div class="mb-3">
            <label>QR Code Image</label>
            <input type="file" name="qr_code" class="form-control">
            <?php if($settings['gcash_qr']): ?>
                <img src="../<?= $settings['gcash_qr'] ?>" width="150" class="mt-2 border">
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>