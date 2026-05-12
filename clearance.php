<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

// --- LOGIC FOR APPROVAL ---
if(isset($_GET['approve_id'])){
    $id = intval($_GET['approve_id']);
    
    // Fetch the document details to ensure we respect the amount and type
    $stmt = $conn->prepare("SELECT document_type, amount FROM document_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $request_data = $stmt->get_result()->fetch_assoc();
    
    // If it's a digital request, use the amount already stored (requested by user)
    // If it was empty for some reason, default to the logic below
    $fee = $request_data['amount'];
    if($request_data['document_type'] == 'Certificate of Indigency') {
        $fee = 0.00;
    } elseif ($fee <= 0) {
        $fee = 50.00;
    }
    if(isset($_GET['decline_id'])){
    $id = intval($_GET['decline_id']);
    
    // Set status to 'Payment Failed' so the resident gets the retry notification
    $update = $conn->prepare("UPDATE document_requests SET status = 'Payment Failed' WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    
    header("Location: clearance.php?msg=declined"); exit();
}
    
    $update = $conn->prepare("UPDATE document_requests SET status = 'Approved', amount = ? WHERE id = ?");
    $update->bind_param("di", $fee, $id);
    $update->execute();
    
    header("Location: clearance.php?msg=approved"); exit();
}

// --- LOGIC FOR DELETE ---
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM document_requests WHERE id = $id");
    header("Location: clearance.php?msg=deleted"); exit();
}

// --- LOGIC FOR CLEAR HISTORY ---
if(isset($_GET['action']) && $_GET['action'] == 'clear_all'){
    $conn->query("DELETE FROM document_requests WHERE status = 'Approved'");
    header("Location: clearance.php?msg=cleared"); exit();
}

// --- LOGIC FOR NEW ISSUANCE (WALK-IN) ---
if(isset($_POST['btn_save_walkin'])){
    $res_id = intval($_POST['resident_id']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['document_type']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $amount = ($doc_type == 'Certificate of Indigency') ? 0.00 : floatval($_POST['amount']);
    
    if($res_id > 0) {
        $sql = "INSERT INTO document_requests (resident_id, document_type, purpose, status, request_type, amount, payment_ref) 
                VALUES ('$res_id', '$doc_type', '$purpose', 'Approved', 'Walk-in', '$amount', 'WALK-IN')";
        $conn->query($sql);
        header("Location: clearance.php?msg=success"); exit();
    }
}

// Stats
$total_collected = $conn->query("SELECT SUM(amount) as total FROM document_requests WHERE status='Approved'")->fetch_assoc()['total'] ?? 0;
$pending_count = $conn->query("SELECT COUNT(*) as total FROM document_requests WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;
$docs_issued = $conn->query("SELECT COUNT(*) FROM document_requests")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clearance & Finance | BMIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        :root { --bg-main: #fcfcfd; --primary: #4f46e5; --slate-800: #1e293b; --slate-400: #94a3b8; --border: #f1f5f9; }
        body { background-color: var(--bg-main); font-family: 'Inter', sans-serif; color: var(--slate-800); }
        .sidebar-container { width: 280px; position: fixed; height: 100vh; background: #fff; border-right: 1px solid var(--border); }
        .main-content { margin-left: 280px; padding: 50px; }
        .stat-group { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .content-card { background: white; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); overflow: hidden; }
        .table thead th { background: #fafafa; color: var(--slate-400); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .table tbody td { padding: 20px 24px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .res-name { font-weight: 600; color: var(--slate-800); margin-bottom: 2px; }
        .badge-pill { padding: 6px 12px; border-radius: 100px; font-weight: 600; font-size: 11px; }
        .bg-pending { background: #fff7ed; color: #c2410c; }
        .bg-approved { background: #f0fdf4; color: #15803d; }
        .price-tag { font-family: 'Monaco', monospace; font-weight: 700; color: #0f172a; background: #f8fafc; padding: 4px 8px; border-radius: 6px; }
        .price-free { color: #16a34a !important; background: #f0fdf4 !important; border: 1px solid #bbf7d0; }
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: 1px solid transparent; text-decoration: none; }
        .btn-approve { background: #f0fdf4; color: #16a34a; }
        .btn-approve:hover { background: #16a34a; color: #fff; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: #fff; }
        .btn-print { background: #f8fafc; color: var(--slate-800); border: 1px solid #e2e8f0; }
        .ref-box { font-size: 11px; background: #eef2ff; color: #4f46e5; padding: 2px 6px; border-radius: 4px; font-weight: 700; display: inline-block; margin-top: 4px; }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h2 class="fw-bold mb-1">Clearance & Finance</h2>
            <p class="text-secondary m-0">Manage resident documentation and revenue tracking.</p>
        </div>
        <div class="d-flex gap-3">
            <a href="clearance.php?action=clear_all" onclick="return confirm('Clear all approved records?')" class="btn btn-light border-0 px-4 fw-600 rounded-pill text-danger">
                <i class="fas fa-trash-alt me-2"></i> Clean History
            </a>
            <button class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#strictModal">
                <i class="fas fa-plus me-2"></i> New Issuance
            </button>
        </div>
    </div>

    <div class="stat-group">
        <div class="stat-card">
            <div class="stat-icon mb-3" style="background: #eef2ff; color: #4f46e5;"><i class="fas fa-wallet"></i></div>
            <div class="text-secondary small fw-medium">Total Revenue</div>
            <div class="fs-3 fw-bold mt-1">₱ <?= number_format($total_collected, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon mb-3" style="background: #fff7ed; color: #f97316;"><i class="fas fa-hourglass-half"></i></div>
            <div class="text-secondary small fw-medium">Pending Requests</div>
            <div class="fs-3 fw-bold mt-1"><?= $pending_count ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon mb-3" style="background: #f0fdf4; color: #22c55e;"><i class="fas fa-file-invoice"></i></div>
            <div class="text-secondary small fw-medium">Documents Issued</div>
            <div class="fs-3 fw-bold mt-1"><?= $docs_issued ?></div>
        </div>
    </div>

    <div class="content-card">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Resident Detail</th>
                    <th>Document Type</th>
                    <th>Payment Info</th> <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT dr.*, r.fullname FROM document_requests dr JOIN residents r ON dr.resident_id = r.id ORDER BY dr.id DESC");
                while($row = $res->fetch_assoc()):
                    $isIndigency = ($row['document_type'] == 'Certificate of Indigency');
                ?>
                <tr>
                    <td>
                        <div class="res-name"><?= htmlspecialchars($row['fullname']) ?></div>
                        <div class="small text-muted"><?= $row['request_type'] ?> • <?= date("M d, Y", strtotime($row['date_requested'])) ?></div>
                    </td>
                    <td>
                        <div class="fw-medium"><?= $row['document_type'] ?></div>
                        <div class="small text-muted"><em>"<?= htmlspecialchars($row['purpose']) ?>"</em></div>
                    </td>
                    <td>
                        <span class="price-tag <?= $isIndigency ? 'price-free' : '' ?>">
                            <?= $isIndigency ? 'FREE' : '₱'.number_format($row['amount'], 2) ?>
                        </span>
                        <?php if(!$isIndigency && !empty($row['payment_ref'])): ?>
                            <br><span class="ref-box"><i class="fas fa-fingerprint me-1"></i> REF: <?= $row['payment_ref'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-pill <?= $row['status']=='Approved' ? 'bg-approved' : 'bg-pending' ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <?php if($row['status'] == 'Pending'): ?>
                                <a href="clearance.php?approve_id=<?= $row['id'] ?>" class="btn-action btn-approve" title="Approve" onclick="return confirm('Verify Payment: REF <?= $row['payment_ref'] ?> received?')">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>

                            <?php if($row['status'] == 'Approved'): ?>
                                <a href="print_clearance.php?id=<?= $row['id'] ?>" target="_blank" class="btn-action btn-print" title="Print">
                                    <i class="fas fa-print"></i>
                                </a>
                                
                            <?php endif; ?>
                            
                            <a href="clearance.php?delete_id=<?= $row['id'] ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Permanent delete?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<div class="modal fade" id="strictModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content shadow">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold">New Issuance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <div class="mb-4">
                    <label class="small fw-bold text-muted mb-2 text-uppercase">Resident</label>
                    <select name="resident_id" class="form-select select2-setup" required>
                        <option value="">Select resident...</option>
                        <?php 
                        $list = $conn->query("SELECT id, fullname FROM residents ORDER BY fullname ASC");
                        while($l = $list->fetch_assoc()) echo "<option value='{$l['id']}'>".htmlspecialchars($l['fullname'])."</option>";
                        ?>
                    </select>
                </div>
                <div class="row mb-4">
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-2 text-uppercase">Document</label>
                        <select name="document_type" id="docSelect" class="form-select" required>
                            <option>Barangay Clearance</option>
                            <option>Certificate of Indigency</option>
                            <option>Barangay Residency</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-2 text-uppercase">Fee (₱)</label>
                        <input type="number" name="amount" id="feeInput" class="form-control fw-bold" value="50.00" step="0.01" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="small fw-bold text-muted mb-2 text-uppercase">Purpose</label>
                    <input type="text" name="purpose" class="form-control" placeholder="e.g. Scholarship / Employment" required>
                </div>
                <button type="submit" name="btn_save_walkin" class="btn btn-primary w-100 py-3 fw-bold rounded-4">
                    Complete & Save Record
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2-setup').select2({ 
        dropdownParent: $('#strictModal'), 
        width: '100%'
    });

    $('#docSelect').on('change', function() {
        if($(this).val() === 'Certificate of Indigency') { 
            $('#feeInput').val('0.00').prop('readonly', true).css('background-color', '#f0fdf4');
        } else {
            $('#feeInput').val('50.00').prop('readonly', false).css('background-color', '#fcfcfd');
        }
    });
});
</script>
</body>
</html>