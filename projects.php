<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

/** * FUND SOURCE LOGIC */
function getAccurateBadge($source) {
    $s = trim($source);
    return match($s) {
        'SK Fund (10%)'        => '<span class="f-badge f-sk">SK Project</span>',
        '20% Development Fund' => '<span class="f-badge f-dev">20% Dev Fund</span>',
        'LDRRMF (Calamity)', 
        'BDRRM Fund'           => '<span class="f-badge f-cal">BDRRM Fund</span>',
        default                => '<span class="f-badge f-gen">Brgy General</span>',
    };
}

// --- LOGIC: DELETE PROJECT ---
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    // Optional: Delete related finance records or just nullify project_link_id
    $conn->query("DELETE FROM projects WHERE id = $id");
    header("Location: projects.php?msg=deleted"); exit();
}

// --- LOGIC: NEW PROJECT ---
if(isset($_POST['btn_save_project'])){
    $name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $budget = floatval($_POST['budget']);
    $source = $_POST['fund_source'];
    $date = $_POST['date_started'];
    $conn->query("INSERT INTO projects (project_name, location, budget, fund_source, status, date_started) VALUES ('$name', '$loc', $budget, '$source', 'Ongoing', '$date')");
    header("Location: projects.php?msg=added"); exit();
}

// --- LOGIC: DISBURSEMENT ---
if(isset($_POST['btn_disburse'])){
    $pid = intval($_POST['project_id']);
    $amt = floatval($_POST['amount']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $proj = $conn->query("SELECT fund_source, project_name FROM projects WHERE id = $pid")->fetch_assoc();
    $fund = $proj['fund_source'];
    $pname = $proj['project_name'];

    $conn->query("INSERT INTO finance (description, amount, type, category, date_transacted, project_link_id) 
                  VALUES ('Project Disbursement: $pname ($remarks)', $amt, 'Expense', '$fund', NOW(), $pid)");
    header("Location: projects.php?msg=disbursed"); exit();
}

if(isset($_GET['complete_id'])){ $conn->query("UPDATE projects SET status='Completed' WHERE id=".intval($_GET['complete_id'])); header("Location: projects.php"); exit(); }
if(isset($_GET['reopen_id'])){ $conn->query("UPDATE projects SET status='Ongoing' WHERE id=".intval($_GET['reopen_id'])); header("Location: projects.php"); exit(); }

$m = date('m');
$y = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay & SK Fiscal Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --accent: #020617;
        }
        body { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
            color: #334155;
            min-height: 100vh;
        }
        .sidebar-container { width: 260px; position: fixed; height: 100vh; background: white; z-index: 1000; border-right: 1px solid rgba(0,0,0,0.05); }
        .main-content { margin-left: 260px; padding: 3rem; }
        
        /* Precision Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }

        .table-transparent { background: transparent !important; }
        .table-transparent thead th { 
            background: rgba(0,0,0,0.02); 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            font-size: 11px;
            border-top: none;
        }

        /* Accurate Badges */
        .f-badge { 
            padding: 4px 10px; 
            border-radius: 6px; 
            font-size: 11px; 
            font-weight: 600;
            border: 1px solid transparent;
        }
        .f-gen { background: #f1f5f9; color: #475569; }
        .f-sk { background: #f5f3ff; color: #7c3aed; }
        .f-dev { background: #f0fdf4; color: #16a34a; }
        .f-cal { background: #fef2f2; color: #dc2626; }

        .progress-slim { height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05); }
        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
        
        .project-name { font-weight: 700; color: var(--accent); letter-spacing: -0.02em; }
        
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body>

<div class="sidebar-container no-print"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 no-print">
        <div>
            <h2 class="fw-bold text-dark mb-1">Fiscal Registry</h2>
            <p class="text-muted small mb-0">Monitor appropriation and disbursement in real-time.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-white bg-white border-0 shadow-sm rounded-3 px-4 fw-600" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Export
            </button>
            <button class="btn btn-dark rounded-3 px-4 fw-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#newProj">
                <i class="fas fa-plus me-2"></i>New Project
            </button>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <table class="table table-transparent align-middle mb-0">
            <thead>
                <tr class="text-muted">
                    <th class="ps-4 py-3">Project Detail</th>
                    <th>Source</th>
                    <th>Utilization</th>
                    <th>Available Fund</th>
                    <th class="text-end pe-4 no-print">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $res = $conn->query("SELECT p.*, (SELECT SUM(amount) FROM finance WHERE project_link_id = p.id) as spent FROM projects p ORDER BY id DESC");
                while($row = $res->fetch_assoc()): 
                    $fund_source = $row['fund_source'];
                    $allotment_q = $conn->query("SELECT amount FROM budget_allotments WHERE category='$fund_source' AND month='$m' AND year='$y'")->fetch_assoc();
                    $total_fund_limit = $allotment_q['amount'] ?? 0;
                    $total_spent_in_fund = $conn->query("SELECT SUM(amount) as total FROM finance WHERE category='$fund_source' AND type='Expense' AND MONTH(date_transacted)='$m' AND YEAR(date_transacted)='$y'")->fetch_assoc()['total'] ?? 0;

                    $project_spent = $row['spent'] ?? 0;
                    $project_pct = ($row['budget'] > 0) ? ($project_spent / $row['budget']) * 100 : 0;
                    $remaining_in_fund = $total_fund_limit - $total_spent_in_fund;
                    $is_done = ($row['status'] == 'Completed');
                ?>
                <tr style="<?= $is_done ? 'opacity: 0.6;' : '' ?>">
                    <td class="ps-4 py-4">
                        <div class="project-name"><?= $row['project_name'] ?></div>
                        <div class="text-muted small" style="font-size: 11px;">
                            <i class="fas fa-map-marker-alt me-1"></i><?= $row['location'] ?>
                        </div>
                    </td>
                    <td><?= getAccurateBadge($row['fund_source']) ?></td>
                    <td style="min-width: 180px;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-600">₱<?= number_format($project_spent) ?></span>
                            <span class="text-muted"><?= number_format($project_pct, 0) ?>%</span>
                        </div>
                        <div class="progress-slim">
                            <div class="progress-bar bg-dark" style="width: <?= min($project_pct, 100) ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold <?= $remaining_in_fund < 0 ? 'text-danger' : '' ?>">
                            ₱<?= number_format($remaining_in_fund, 2) ?>
                        </div>
                    </td>
                    <td class="text-end pe-4 no-print">
                        <div class="d-flex justify-content-end gap-1">
                            <?php if(!$is_done): ?>
                                <button onclick="openDisburse(<?= $row['id'] ?>, '<?= addslashes($row['project_name']) ?>')" class="btn btn-action btn-outline-dark" title="Disburse"><i class="fas fa-hand-holding-usd fa-xs"></i></button>
                                <a href="projects.php?complete_id=<?= $row['id'] ?>" class="btn btn-action btn-outline-success" title="Complete"><i class="fas fa-check fa-xs"></i></a>
                            <?php else: ?>
                                <a href="projects.php?reopen_id=<?= $row['id'] ?>" class="btn btn-action btn-outline-warning" title="Reopen"><i class="fas fa-undo fa-xs"></i></a>
                            <?php endif; ?>
                            
                            <!-- Delete Button -->
                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-action btn-outline-danger" title="Delete">
                                <i class="fas fa-trash fa-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modals & Scripts (Kept functional, styled for precision) -->
<div class="modal fade" id="disburseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-1">Disburse Funds</h5>
                <p id="proj_name_display" class="text-muted small mb-4"></p>
                <input type="hidden" name="project_id" id="disburse_pid">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Amount (PHP)</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-lg border-light bg-light" placeholder="0.00" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Purpose / Remarks</label>
                    <textarea name="remarks" class="form-control border-light bg-light" rows="2" placeholder="e.g. Materials, Labor..." required></textarea>
                </div>
                <button type="submit" name="btn_disburse" class="btn btn-dark w-100 py-2 fw-bold">Confirm Release</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: New Project -->
<div class="modal fade" id="newProj" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-4">Project Registration</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="small fw-bold">Project Title</label>
                        <input type="text" name="project_name" class="form-control border-light bg-light" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Budget Limit</label>
                        <input type="number" step="0.01" name="budget" class="form-control border-light bg-light" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Fund Source</label>
                        <select name="fund_source" class="form-select border-light bg-light" required>
                            <option value="General Fund">General Fund</option>
                            <option value="20% Development Fund">20% Development Fund</option>
                            <option value="SK Fund (10%)">SK Fund (10%)</option>
                            <option value="BDRRM Fund">BDRRM Fund</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Location</label>
                        <input type="text" name="location" class="form-control border-light bg-light" required>
                    </div>
                </div>
                <button type="submit" name="btn_save_project" class="btn btn-dark w-100 py-3 fw-bold mt-4">Deploy Project</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openDisburse(id, name) {
    document.getElementById('disburse_pid').value = id;
    document.getElementById('proj_name_display').innerText = name;
    new bootstrap.Modal(document.getElementById('disburseModal')).show();
}

function confirmDelete(id) {
    if(confirm('Are you sure? This will permanently remove the project registry.')) {
        window.location.href = 'projects.php?delete_id=' + id;
    }
}
</script>
</body>
</html>