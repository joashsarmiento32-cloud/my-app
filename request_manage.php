<?php 
include 'db_connect.php'; 

// Logic to Approve and "Send"
if(isset($_GET['approve_id'])){
    $id = $_GET['approve_id'];
    // In a real system, this is where you'd trigger an email script
    $sql = "UPDATE requests SET status='Approved' WHERE id=$id";
    $conn->query($sql);
    header("Location: request_manage.php?msg=Sent to User Online");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Requests | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto p-4">
            <h2>Online Document Requests</h2>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Document</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $reqs = $conn->query("SELECT * FROM requests ORDER BY request_date DESC");
                            while($row = $reqs->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['resident_name'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= $row['request_type'] ?></td>
                                <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
                                <td>
                                    <a href="generate_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-file-pdf"></i> Generate & Send
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>