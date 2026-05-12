<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

/** 
 * --- ADDITIONAL DELETE LOGIC FOR NEW SECTIONS ---
 */

// Delete Medicine
if(isset($_GET['delete_med_id'])){
    $id = intval($_GET['delete_med_id']);
    $conn->query("DELETE FROM medicine_inventory WHERE id = $id");
    header("Location: health.php?msg=med_deleted"); exit();
}

// Delete Family Planning Record
if(isset($_GET['delete_fp_id'])){
    $id = intval($_GET['delete_fp_id']);
    $conn->query("DELETE FROM family_planning WHERE id = $id");
    header("Location: health.php?msg=fp_deleted"); exit();
}

// Delete Immunization Record
if(isset($_GET['delete_imm_id'])){
    $id = intval($_GET['delete_imm_id']);
    $conn->query("DELETE FROM immunization_records WHERE id = $id");
    header("Location: health.php?msg=vax_deleted"); exit();
}

// Delete Staff/Worker
if(isset($_GET['delete_worker_id'])){
    $id = intval($_GET['delete_worker_id']);
    $conn->query("DELETE FROM health_workers WHERE id = $id");
    header("Location: health.php?msg=staff_deleted"); exit();
}

/** 
 * --- EXISTING LOGIC ---
 */
if(isset($_GET['approve_id'])){
    $id = intval($_GET['approve_id']);
    $conn->query("UPDATE appointments SET status = 'Approved' WHERE id = $id");
    header("Location: health.php?msg=approved"); exit();
}
if(isset($_GET['decline_id'])){
    $id = intval($_GET['decline_id']);
    $conn->query("UPDATE appointments SET status = 'Declined' WHERE id = $id");
    header("Location: health.php?msg=declined"); exit();
}
if(isset($_GET['delete_app_id'])){
    $id = intval($_GET['delete_app_id']);
    $conn->query("DELETE FROM appointments WHERE id = $id");
    header("Location: health.php?msg=deleted"); exit();
}
if(isset($_GET['delete_record_id'])){
    $id = intval($_GET['delete_record_id']);
    $conn->query("DELETE FROM health_records WHERE id = $id");
    header("Location: health.php?msg=record_deleted"); exit();
}
if(isset($_POST['btn_clear_all_apps'])){
    $conn->query("DELETE FROM appointments WHERE status != 'Pending'");
    header("Location: health.php?msg=all_cleared"); exit();
}

// (Logic for saving BHW, Medicine, and Consults remains the same as your original file...)
if(isset($_POST['btn_save_bhw'])){
    $name = mysqli_real_escape_string($conn, $_POST['worker_name']);
    $pos = mysqli_real_escape_string($conn, $_POST['position']);
    $conn->query("INSERT INTO health_workers (worker_name, position, status) VALUES ('$name', '$pos', 'Active')");
    header("Location: health.php?msg=bhw_added"); exit();
}
if(isset($_POST['btn_save_medicine'])){
    $name = mysqli_real_escape_string($conn, $_POST['med_name']);
    $qty = intval($_POST['stock_qty']);
    $exp = $_POST['expiry_date'];
    $check = $conn->query("SELECT id FROM medicine_inventory WHERE medicine_name = '$name'");
    if($check->num_rows > 0){
        $conn->query("UPDATE medicine_inventory SET quantity = quantity + $qty, expiry_date = '$exp' WHERE medicine_name = '$name'");
    } else {
        $conn->query("INSERT INTO medicine_inventory (medicine_name, quantity, expiry_date) VALUES ('$name', '$qty', '$exp')");
    }
    header("Location: health.php?msg=inventory_updated"); exit();
}
if(isset($_POST['btn_save_log'])){
    $res_id = intval($_POST['resident_id']);
    $program = $_POST['health_program'];
    $diag = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $instr = mysqli_real_escape_string($conn, $_POST['instruction']);
    $date = $_POST['log_date'];
    $treatment_desc = "";
    $conn->begin_transaction();
    try {
        if($program != 'Immunization' && !empty($_POST['medicine_id'])) {
            $med_id = intval($_POST['medicine_id']);
            $qty_given = intval($_POST['qty_given']);
            $med_query = $conn->query("SELECT medicine_name, quantity FROM medicine_inventory WHERE id = $med_id");
            $med_data = $med_query->fetch_assoc();
            if($med_data && $med_data['quantity'] >= $qty_given) {
                $treatment_desc = $qty_given . " pcs " . $med_data['medicine_name'] . " (" . $instr . ")";
                $conn->query("UPDATE medicine_inventory SET quantity = quantity - $qty_given WHERE id = $med_id");
            } else { throw new Exception('Low Stock!'); }
        } else { $treatment_desc = $instr ?: "Standard procedure"; }
        $conn->query("INSERT INTO health_records (resident_id, diagnosis, treatment, date_recorded) VALUES ('$res_id', '[$program] $diag', '$treatment_desc', '$date')");
        if($program == 'Immunization'){
            $vax = mysqli_real_escape_string($conn, $_POST['vax_name']);
            $conn->query("INSERT INTO immunization_records (resident_id, vaccine_name, date_given) VALUES ('$res_id', '$vax', '$date')");
        } elseif($program == 'Family Planning'){
            $method = mysqli_real_escape_string($conn, $_POST['fp_method']);
            $next_v = $_POST['fp_next_visit']; 
            $conn->query("INSERT INTO family_planning (resident_id, method, next_service_date) VALUES ('$res_id', '$method', '$next_v')");
        }
        $conn->commit();
        header("Location: health.php?msg=success");
    } catch (Exception $e) { $conn->rollback(); echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='health.php';</script>"; }
}

// FETCH DATA
$pending_apps = $conn->query("SELECT a.*, r.fullname, r.contact_no FROM appointments a JOIN residents r ON a.resident_id = r.id WHERE a.status = 'Pending' ORDER BY a.id DESC");
$confirmed_apps = $conn->query("SELECT a.*, r.fullname, r.contact_no FROM appointments a JOIN residents r ON a.resident_id = r.id WHERE a.status = 'Approved' ORDER BY a.appointment_date ASC");
$med_list = $conn->query("SELECT * FROM medicine_inventory ORDER BY medicine_name ASC");
$staff_list = $conn->query("SELECT * FROM health_workers ORDER BY worker_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Hub Pro | Specific Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        :root { --accent: #10b981; --sidebar-w: 280px; }
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }
        .main-content { margin-left: var(--sidebar-w); padding: 40px; }
        .modern-glass { background: white; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .table-wrap { border-radius: 16px; overflow: hidden; border: 1px solid #edf2f7; }
        .btn-grad { background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%); color: white; border: none; border-radius: 12px; font-weight: 600; padding: 10px 20px; transition: 0.2s; }
        .btn-grad:hover { transform: translateY(-2px); color: white; opacity: 0.9; }
        .nav-pills .nav-link { border-radius: 12px; color: #64748b; font-weight: 600; }
        .nav-pills .nav-link.active { background: #10b981 !important; color: white !important; }
        .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
        .action-icon:hover { background: #f1f5f9; }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <div class="modern-glass p-4 mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold m-0">Medical Center Management</h2>
            <p class="text-muted small m-0">Manage appointments, inventory, and health programs</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary rounded-3" data-bs-toggle="modal" data-bs-target="#modalBHW">Manage Staff</button>
            <button class="btn btn-grad" data-bs-toggle="modal" data-bs-target="#modalCheckup"><i class="fas fa-plus-circle me-2"></i>Record Consultation</button>
        </div>
    </div>

    <div class="modern-glass p-4">
        <ul class="nav nav-pills mb-4" id="pills-tab">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-online">Appointments</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-logs">Medical History</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-prog">Program Tracking</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-med">Pharmacy Inventory</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-staff">Staff Directory</button></li>
        </ul>

        <div class="tab-content">
            <!-- TAB: APPOINTMENTS -->
            <div class="tab-pane fade show active" id="tab-online">
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Resident Name</th><th>Service Type</th><th>Schedule</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while($p = $pending_apps->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $p['fullname'] ?></strong><br><small class="text-muted"><?= $p['contact_no'] ?></small></td>
                                <td><span class="badge bg-info-subtle text-info border border-info-subtle"><?= $p['service_type'] ?></span></td>
                                <td><span class="text-warning fw-bold">PENDING APPROVAL</span></td>
                                <td>
                                    <a href="health.php?approve_id=<?= $p['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="health.php?decline_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger">Decline</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php while($conf = $confirmed_apps->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $conf['fullname'] ?></strong></td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= $conf['service_type'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($conf['appointment_date'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="syncToConsult('<?= $conf['resident_id'] ?>', '<?= $conf['service_type'] ?>')">Treat Patient</button>
                                    <a href="health.php?delete_app_id=<?= $conf['id'] ?>" class="text-danger ms-2" onclick="return confirm('Delete this appointment?')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: MEDICAL HISTORY -->
            <div class="tab-pane fade" id="tab-logs">
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Patient</th><th>Clinical Diagnosis</th><th>Treatment/Medicine</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php $logs=$conn->query("SELECT hr.*, r.fullname FROM health_records hr JOIN residents r ON hr.resident_id = r.id ORDER BY hr.id DESC");
                            while($row=$logs->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $row['fullname'] ?></strong></td>
                                <td><small class="fw-bold text-uppercase text-muted"><?= $row['diagnosis'] ?></small></td>
                                <td><?= $row['treatment'] ?></td>
                                <td><?= date('m/d/Y', strtotime($row['date_recorded'])) ?></td>
                                <td><a href="health.php?delete_record_id=<?= $row['id'] ?>" class="text-danger" onclick="return confirm('Delete this record permanently?')"><i class="fas fa-trash-alt"></i></a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: PROGRAM TRACKING (Specific) -->
            <div class="tab-pane fade" id="tab-prog">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="fas fa-baby me-2"></i>Immunization Logs</h6>
                        <div class="table-wrap">
                            <table class="table table-sm">
                                <thead><tr><th>Resident</th><th>Vaccine</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php $imm=$conn->query("SELECT i.*, r.fullname FROM immunization_records i JOIN residents r ON i.resident_id = r.id ORDER BY id DESC");
                                    while($i=$imm->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i['fullname'] ?></td>
                                        <td><span class="badge bg-primary"><?= $i['vaccine_name'] ?></span></td>
                                        <td><a href="health.php?delete_imm_id=<?= $i['id'] ?>" class="text-danger" onclick="return confirm('Delete immunization record?')"><i class="fas fa-times"></i></a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="fas fa-users me-2"></i>Family Planning</h6>
                        <div class="table-wrap">
                            <table class="table table-sm">
                                <thead><tr><th>Resident</th><th>Return Date</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php $fp=$conn->query("SELECT f.*, r.fullname FROM family_planning f JOIN residents r ON f.resident_id = r.id ORDER BY next_service_date ASC");
                                    while($f=$fp->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $f['fullname'] ?></td>
                                        <td class="text-danger fw-bold"><?= $f['next_service_date'] ?></td>
                                        <td><a href="health.php?delete_fp_id=<?= $f['id'] ?>" class="text-danger" onclick="return confirm('Delete FP record?')"><i class="fas fa-times"></i></a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: MEDICINE -->
            <div class="tab-pane fade" id="tab-med">
                <div class="d-flex justify-content-between mb-3">
                    <h6 class="fw-bold">Pharmacy Stock Control</h6>
                    <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalMed">+ New Stock</button>
                </div>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Medicine Name</th><th>Qty</th><th>Expiry</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php $med_list = $conn->query("SELECT * FROM medicine_inventory ORDER BY medicine_name ASC");
                            while($m=$med_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $m['medicine_name'] ?></strong></td>
                                <td><?= $m['quantity'] ?> units</td>
                                <td><?= $m['expiry_date'] ?></td>
                                <td><a href="health.php?delete_med_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete medicine from inventory?')">Remove</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: STAFF -->
            <div class="tab-pane fade" id="tab-staff">
                <h6 class="fw-bold">Active Health Workers (BHW)</h6>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Full Name</th><th>Designation</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while($s=$staff_list->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $s['worker_name'] ?></strong></td>
                                <td><?= $s['position'] ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td><a href="health.php?delete_worker_id=<?= $s['id'] ?>" class="text-danger" onclick="return confirm('Remove staff member?')"><i class="fas fa-user-minus"></i></a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODALS (Modified to be more specific) -->
<div class="modal fade" id="modalCheckup" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg p-4" style="border-radius: 24px;">
            <h4 class="fw-bold text-center mb-4">New Health Consultation</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small fw-bold">Target Program</label>
                    <select name="health_program" id="progSelector" class="form-select border-0 bg-light" required>
                        <option value="General">General Consultation</option>
                        <option value="Immunization">Immunization</option>
                        <option value="Family Planning">Family Planning</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small fw-bold">Resident/Patient</label>
                    <select name="resident_id" class="form-select select2-setup" required>
                        <?php $l=$conn->query("SELECT id, fullname FROM residents"); while($r=$l->fetch_assoc()) echo "<option value='{$r['id']}'>{$r['fullname']}</option>"; ?>
                    </select>
                </div>
            </div>

            <div id="extra-vax" class="p-3 mb-3 border rounded-3 bg-light" style="display:none;">
                <label class="small fw-bold">Vaccine Brand/Name</label>
                <input type="text" name="vax_name" class="form-control" placeholder="e.g., Pfizer, Moderna, BCG">
            </div>

            <div id="extra-fp" class="p-3 mb-3 border rounded-3 bg-light" style="display:none;">
                <label class="small fw-bold">Contraceptive Method & Next Visit</label>
                <input type="text" name="fp_method" class="form-control mb-2" placeholder="e.g., Pills, Injectable">
                <input type="date" name="fp_next_visit" class="form-control">
            </div>

            <div class="mb-3">
                <label class="small fw-bold">Clinical Findings / Diagnosis</label>
                <textarea name="diagnosis" class="form-control bg-light border-0" rows="3" placeholder="Enter patient complaints or findings..." required></textarea>
            </div>

            <div id="inventory-section" class="row g-2 mb-4 border p-3 rounded-3">
                <div class="col-12"><label class="small fw-bold">Dispense Medicine</label></div>
                <div class="col-8">
                    <select name="medicine_id" class="form-select bg-light border-0">
                        <option value="">Select Medicine</option>
                        <?php $i=$conn->query("SELECT id, medicine_name, quantity FROM medicine_inventory WHERE quantity > 0"); 
                        while($m=$i->fetch_assoc()) echo "<option value='{$m['id']}'>{$m['medicine_name']} (Stock: {$m['quantity']})</option>"; ?>
                    </select>
                </div>
                <div class="col-4"><input type="number" name="qty_given" class="form-control bg-light border-0" placeholder="Qty"></div>
            </div>

            <input type="hidden" name="instruction" value="Prescribed Treatment">
            <input type="hidden" name="log_date" value="<?= date('Y-m-d') ?>">
            <button type="submit" name="btn_save_log" class="btn btn-grad w-100 py-3">Finish & Save Record</button>
        </form>
    </div>
</div>

<!-- (Inventory and Staff Modals remain largely same but added clear titles) -->
<div class="modal fade" id="modalMed" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content p-4 border-0 rounded-4 shadow">
            <h5 class="fw-bold mb-3">Inventory Restock</h5>
            <div class="mb-3"><input type="text" name="med_name" class="form-control bg-light" placeholder="Medicine Name" required></div>
            <div class="row g-2 mb-3">
                <div class="col-6"><input type="number" name="stock_qty" class="form-control bg-light" placeholder="Quantity" required></div>
                <div class="col-6"><input type="date" name="expiry_date" class="form-control bg-light" required></div>
            </div>
            <button name="btn_save_medicine" class="btn btn-grad w-100">Update Stock</button>
        </form>
    </div>
</div>

<div class="modal fade" id="modalBHW" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content p-4 border-0 rounded-4 shadow">
            <h5 class="fw-bold mb-3">Register Health Worker</h5>
            <div class="mb-3"><label class="small fw-bold">Worker Name</label><input type="text" name="worker_name" class="form-control" required></div>
            <div class="mb-3"><label class="small fw-bold">Position</label><input type="text" name="position" class="form-control" placeholder="BHW, Midwife, etc." required></div>
            <button name="btn_save_bhw" class="btn btn-grad w-100">Save Staff Member</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-setup').select2({ dropdownParent: $('#modalCheckup'), width: '100%' });
        $('#progSelector').on('change', function() {
            let val = $(this).val();
            $('#extra-vax, #extra-fp').hide();
            if(val === 'Immunization') { $('#extra-vax').show(); } 
            else if(val === 'Family Planning') { $('#extra-fp').show(); } 
        });
    });
    function syncToConsult(resId, program) {
        var consultModal = new bootstrap.Modal(document.getElementById('modalCheckup'));
        consultModal.show();
        $('.select2-setup').val(resId).trigger('change');
        $('#progSelector').val(program).trigger('change');
    }
</script>
</body>
</html>