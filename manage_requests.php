<?php 
include 'db_connect.php'; 
session_start();

// 1. HANDLE APPROVAL
if(isset($_GET['approve_id'])){
    $id = intval($_GET['approve_id']);
    
    $stmt = $conn->prepare("SELECT * FROM online_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $request_data = $stmt->get_result()->fetch_assoc();
    
    if($request_data){
        $name = $request_data['resident_name'];
        $type = $request_data['type'];

        $user_stmt = $conn->prepare("SELECT resident_id FROM users WHERE fullname = ? LIMIT 1");
        $user_stmt->bind_param("s", $name);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        $res_id = ($user_res->num_rows > 0) ? $user_res->fetch_assoc()['resident_id'] : 'N/A';

        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $ins = $conn->prepare("INSERT INTO clearances (resident_id, type, amount, date_issued) VALUES (?, ?, 0, NOW())");
        $ins->bind_param("ss", $res_id, $type);
        
        if($ins->execute()){
            $conn->query("UPDATE online_requests SET status='Approved' WHERE id = $id");
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            header("Location: manage_requests.php?msg=approved");
            exit();
        }
    }
}

// 2. HANDLE DELETE
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM online_requests WHERE id = $id");
    header("Location: manage_requests.php?msg=deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Requests | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; color: #334155; margin: 0; }
        
        .wrapper { display: flex; }
        
        /* THE FIX: Sidebar container and Content margin */
        .sidebar-container { width: var(--sidebar-width); position: fixed; height: 100vh; z-index: 1000; }
        
        .main-content { 
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            padding: 40px; 
            min-height: 100vh;
        }
        
        .glass-header { 
            background: #fff; border-radius: 16px; padding: 24px; 
            margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-left: 8px solid #3b82f6;
        }
        .custom-card { 
            background: #fff; border-radius: 20px; border: none; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); overflow: hidden;
        }
        
        .table thead th { 
            background: #f8fafc; color: #64748b; font-weight: 700; 
            text-transform: uppercase; font-size: 0.75rem; padding: 15px 20px;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; }
        .badge-approved { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; }
        
        .btn-approve { background: #10b981; color: #fff; border: none; border-radius: 10px; padding: 8px 16px; font-weight: 600; }
        .btn-print { background: #3b82f6; color: #fff; border: none; border-radius: 10px; padding: 8px 16px; font-weight: 600; }
        .btn-delete { color: #ef4444; background: #fee2e2; border: none; border-radius: 10px; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar-container">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="main-content">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> 
                Request successfully <strong><?= $_GET['msg'] ?></strong>!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="glass-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1">Document Requests</h3>
                <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> Review and process resident clearance requests</p>
            </div>
            <a href="index.php" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Dashboard
            </a>
        </div>

        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Resident Details</th>
                            <th>Request Type</th>
                            <th>Current Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $requests = $conn->query("SELECT * FROM online_requests ORDER BY id DESC");
                        while($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold fs-6"><?= htmlspecialchars($row['resident_name']) ?></div>
                                <div class="text-muted small">Linked to Resident Profile</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                    <i class="fas fa-file-alt me-1 text-primary"></i> <?= $row['type'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['status'] == 'Pending'): ?>
                                    <span class="badge-pending"><i class="fas fa-clock me-1"></i> PENDING</span>
                                <?php else: ?>
                                    <span class="badge-approved"><i class="fas fa-check-circle me-1"></i> APPROVED</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <a href="manage_requests.php?approve_id=<?= $row['id'] ?>" class="btn btn-approve shadow-sm">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    <?php else: ?>
                                        <a href="print_clearance.php?name=<?= urlencode($row['resident_name']) ?>&type=<?= urlencode($row['type']) ?>" 
                                           target="_blank" class="btn btn-print shadow-sm">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                    <?php endif; ?>

                                    <a href="manage_requests.php?delete_id=<?= $row['id'] ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Archive this record?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($requests->num_rows == 0): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>