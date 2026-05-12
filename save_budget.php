<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amount = floatval($_POST['budget_amount']);

    // This command "Updates if exists, Inserts if new" (DUPLICATE KEY UPDATE)
    $sql = "INSERT INTO budget_settings (month, year, amount) 
            VALUES ('$month', '$year', '$amount') 
            ON DUPLICATE KEY UPDATE amount = '$amount'";

    if ($conn->query($sql)) {
        header("Location: finance.php?m=$month&y=$year&msg=budget_updated");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>