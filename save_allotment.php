<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $month = $_POST['month'];
    $year = $_POST['year'];
    $amounts = $_POST['amounts']; // This is an array

    foreach($amounts as $category => $amount) {
        $amount = floatval($amount);
        $sql = "INSERT INTO budget_allotments (category, amount, month, year) 
                VALUES ('$category', '$amount', '$month', '$year') 
                ON DUPLICATE KEY UPDATE amount = '$amount'";
        $conn->query($sql);
    }
    
    header("Location: finance.php?m=$month&y=$year&msg=allotment_saved");
}
?>