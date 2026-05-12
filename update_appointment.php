<?php
include '../db_connect.php';

if(isset($_GET['id']) && isset($_GET['status'])){
    $id = $_GET['id'];
    $status = $_GET['status'];

    $sql = "UPDATE appointments SET status = '$status' WHERE id = '$id'";
    
    if($conn->query($sql)){
        header("Location: health.php?msg=updated");
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>