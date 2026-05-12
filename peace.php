<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- LOGIC 1: RECORD NEW BLOTTER ---
if(isset($_POST['add_blotter'])){
    $complainant = mysqli_real_escape_string($conn, $_POST['complainant']);
    $accused = mysqli_real_escape_string($conn, $_POST['accused']);
    $incident_type = mysqli_real_escape_string($conn, $_POST['incident_type']);
    $case_category = $_POST['case_category'];
    $date = $_POST['incident_date'];
    $time = $_POST['incident_time'];
    $location = mysqli_real_escape_string($conn, $_POST['incident_location']);
    $narrative = mysqli_real_escape_string($conn, $_POST['narrative']);
    
    $stmt = $conn->prepare("INSERT INTO blotter (complainant, accused, incident_type, case_category, incident_date, incident_time, incident_location, narrative, status, mediation_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Ongoing', 'Mediation (Captain)')");
    $stmt->bind_param("ssssssss", $complainant, $accused, $incident_type, $case_category, $date, $time, $location, $narrative);
    
    if($stmt->execute()){ 
        // Flow fix: If this was promoted from a resident report, mark it as 'Processed'
        if(!empty($_POST['report_id'])) {
            $rid = intval($_POST['report_id']);
            $conn->query("UPDATE reports SET status = 'Processed' WHERE id = $rid");
        }
        header("Location: peace.php?msg=recorded"); exit(); 
    }
}

// --- LOGIC 2: SETTLE & CLOSE CASE ---
if(isset($_POST['settle_case'])){
    $id = intval($_POST['case_id']);
    $res = mysqli_real_escape_string($conn, $_POST['resolution_notes']);
    $conn->query("UPDATE blotter SET status = 'Closed', resolution_notes = '$res', date_closed = NOW() WHERE id = $id");
    header("Location: peace.php?msg=settled"); exit();
}

// Accurate Counters
$count_ongoing = $conn->query("SELECT id FROM blotter WHERE status='Ongoing'")->num_rows;
$count_pending = $conn->query("SELECT id FROM reports WHERE status='Pending'")->num_rows;
$count_closed  = $conn->query("SELECT id FROM blotter WHERE status='Closed'")->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peace & Order Command | BMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #2563eb; --success: #059669; --warning: #d97706; 
            --slate-50: #f8fafc; --slate-800: #1e293b;
        }

        body { background: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--slate-800); }

        /* KEEPING YOUR SIDEBAR DESIGN EXACTLY */
        .sidebar-fixed { width: 280px; height: 100vh; position: fixed; background: #fff; border-right: 1px solid #e2e8f0; z-index: 1050; }
        .content-area { margin-left: 280px; padding: 40px; }

        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 40px; border-radius: 24px; color: white; margin-bottom: 40px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .badge-status { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-ongoing { background: #fef3c7; color: #92400e; }
        .bg-closed { background: #d1fae5; color: #065f46; }
        .bg-pending { background: #eff6ff; color: #1e40af; }

        .data-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table thead th { background: var(--slate-50); padding: 20px; font-size: 12px; color: #64748b; border-bottom: 2px solid #f1f5f9; }
        .table tbody td { padding: 20px; vertical-align: middle; }

        .nav-tabs-modern { border: none; gap: 10px; }
        .nav-tabs-modern .nav-link { border: none; border-radius: 12px; padding: 12px 20px; font-weight: 600; color: #64748b; }
        .nav-tabs-modern .nav-link.active { background: white; color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<aside class="sidebar-fixed">
    <?php include 'sidebar.php'; ?> </aside>

<main class="content-area">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Katarungang Pambarangay</h2>
            <p class="opacity-75 mb-0">Peace & Order Command Center • Virtual Lupon Office</p>
        </div>
        <button class="btn btn-light fw-bold py-3 px-4 rounded-4 shadow-sm" onclick="openFileModal()">
            <i class="fas fa-plus me-2 text-primary"></i> New Case Entry
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="data-card p-4 d-flex align-items-center">
                <div class="p-3 bg-primary bg-opacity-10 rounded-4 me-3"><i class="fas fa-balance-scale fa-2x text-primary"></i></div>
                <div><h3 class="fw-bold m-0"><?= $count_ongoing ?></h3><small class="text-muted fw-semibold">Active Mediations</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="data-card p-4 d-flex align-items-center">
                <div class="p-3 bg-warning bg-opacity-10 rounded-4 me-3"><i class="fas fa-bell fa-2x text-warning"></i></div>
                <div><h3 class="fw-bold m-0"><?= $count_pending ?></h3><small class="text-muted fw-semibold">New Reports</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="data-card p-4 d-flex align-items-center">
                <div class="p-3 bg-success bg-opacity-10 rounded-4 me-3"><i class="fas fa-check-double fa-2x text-success"></i></div>
                <div><h3 class="fw-bold m-0"><?= $count_closed ?></h3><small class="text-muted fw-semibold">Cases Resolved</small></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-tabs-modern mb-4">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#active"><i class="fas fa-folder-open me-2"></i>Active Blotters</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pending"><i class="fas fa-inbox me-2"></i>Resident Submissions (<?= $count_pending ?>)</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#archives"><i class="fas fa-archive me-2"></i>Archives</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="active">
            <div class="data-card overflow-hidden">
                <table class="table mb-0">
                    <thead>
                        <tr><th>CASE REF</th><th>COMPLAINANT vs ACCUSED</th><th>CATEGORY</th><th>STAGE</th><th class="text-end">ACTION</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM blotter WHERE status = 'Ongoing' ORDER BY id DESC");
                        while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-bold text-primary">#BLT-<?= sprintf("%03d", $row['id']) ?></span></td>
                            <td><div class="fw-bold"><?= $row['complainant'] ?></div><div class="small text-muted">vs. <?= $row['accused'] ?></div></td>
                            <td><div class="badge-status bg-pending mb-1 d-inline-block"><?= $row['case_category'] ?></div></td>
                            <td><span class="badge-status bg-ongoing"><?= $row['mediation_stage'] ?></span></td>
                            <td class="text-end"><button class="btn btn-success btn-sm px-4 rounded-pill fw-bold shadow-sm" onclick="openSettleModal(<?= $row['id'] ?>, '<?= $row['complainant'] ?>', '<?= $row['accused'] ?>')">Settle Case</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="pending">
            <div class="data-card overflow-hidden">
                <table class="table mb-0">
                    <thead><tr><th>SENDER</th><th>INCIDENT TYPE</th><th>LOCATION</th><th>SUBMITTED</th><th class="text-end">ACTION</th></tr></thead>
                    <tbody>
                        <?php 
                        $online = $conn->query("SELECT r.*, res.fullname FROM reports r LEFT JOIN residents res ON r.resident_id = res.id WHERE r.status = 'Pending' ORDER BY r.created_at DESC");
                        while($rep = $online->fetch_assoc()): 
                            $js_data = json_encode(['id'=>$rep['id'], 'fullname'=>$rep['fullname'], 'incident_type'=>$rep['incident_type'], 'location'=>$rep['location'], 'description'=>$rep['description']]);
                        ?>
                        <tr>
                            <td><strong><?= $rep['fullname'] ?></strong></td>
                            <td><?= $rep['incident_type'] ?></td>
                            <td><small><?= $rep['location'] ?></small></td>
                            <td><?= date('M d, Y', strtotime($rep['created_at'])) ?></td>
                            <td class="text-end"><button class="btn btn-primary btn-sm px-3 rounded-pill fw-bold" onclick='promoteToBlotter(<?= htmlspecialchars($js_data, ENT_QUOTES, 'UTF-8') ?>)'>Review & Record</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="archives">
            <div class="data-card overflow-hidden">
                <table class="table mb-0">
                    <thead><tr><th>REFERENCE</th><th>PARTIES</th><th>RESOLUTION NOTES</th><th>CLOSED ON</th></tr></thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM blotter WHERE status = 'Closed' ORDER BY date_closed DESC");
                        while($row = $res->fetch_assoc()): ?>
                        <tr class="bg-light bg-opacity-25">
                            <td><span class="text-muted fw-bold">#BLT-<?= sprintf("%03d", $row['id']) ?></span></td>
                            <td><strong><?= $row['complainant'] ?> vs <?= $row['accused'] ?></strong></td>
                            <td><p class="small text-muted mb-0"><em>"<?= $row['resolution_notes'] ?>"</em></p></td>
                            <td><span class="small fw-bold"><?= date('M d, Y', strtotime($row['date_closed'])) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="settleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-5">
                <div class="text-center mb-4">
                    <div class="p-4 bg-success bg-opacity-10 text-success d-inline-block rounded-circle mb-3"><i class="fas fa-handshake fa-3x"></i></div>
                    <h3 class="fw-bold">Final Settlement</h3>
                    <p class="text-muted" id="partyNames"></p>
                </div>
                <input type="hidden" name="case_id" id="caseIdField">
                <textarea name="resolution_notes" rows="4" class="form-control border-0 bg-light p-3 rounded-4" placeholder="Describe the resolution agreement..." required></textarea>
                <button type="submit" name="settle_case" class="btn btn-success w-100 mt-4 py-3 rounded-4 fw-bold shadow">Seal Case</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="fileModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-5">
                <h4 class="fw-bold mb-4">Official Blotter Record</h4>
                <input type="hidden" name="report_id" id="f_report_id">
                <div class="row g-4">
                    <div class="col-md-6"><label class="small fw-bold">Complainant</label><input type="text" name="complainant" id="f_complainant" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Respondent</label><input type="text" name="accused" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Category</label><select name="case_category" class="form-select bg-light border-0 p-3 rounded-3"><option>Criminal Case</option><option>Civil Case</option><option>Dispute</option></select></div>
                    <div class="col-md-6"><label class="small fw-bold">Incident Type</label><input type="text" name="incident_type" id="f_type" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-md-4"><label class="small fw-bold">Date</label><input type="date" name="incident_date" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-md-4"><label class="small fw-bold">Time</label><input type="time" name="incident_time" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-md-4"><label class="small fw-bold">Location</label><input type="text" name="incident_location" id="f_loc" class="form-control bg-light border-0 p-3 rounded-3" required></div>
                    <div class="col-12"><label class="small fw-bold">Narrative</label><textarea name="narrative" id="f_desc" rows="4" class="form-control bg-light border-0 p-3 rounded-3" required></textarea></div>
                </div>
                <button type="submit" name="add_blotter" class="btn btn-primary w-100 mt-4 py-3 rounded-4 fw-bold shadow">Record Blotter</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const fModal = new bootstrap.Modal(document.getElementById('fileModal'));
    const sModal = new bootstrap.Modal(document.getElementById('settleModal'));

    function openFileModal() { 
        document.getElementById('f_report_id').value = ""; 
        fModal.show(); 
    }

    function promoteToBlotter(data) {
        document.getElementById('f_report_id').value = data.id;
        document.getElementById('f_complainant').value = data.fullname;
        document.getElementById('f_type').value = data.incident_type;
        document.getElementById('f_loc').value = data.location;
        document.getElementById('f_desc').value = data.description;
        fModal.show();
    }

    function openSettleModal(id, c, a) {
        document.getElementById('caseIdField').value = id;
        document.getElementById('partyNames').innerText = c + " vs. " + a;
        sModal.show();
    }
</script>
</body>
</html>