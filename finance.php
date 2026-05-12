<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

// --- 1. LOGIC: DELETE TRANSACTION ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $m_ref = $_GET['m'];
    $y_ref = $_GET['y'];
    $conn->query("DELETE FROM finance WHERE id = $id");
    header("Location: finance.php?m=$m_ref&y=$y_ref");
    exit();
}

// --- 2. LOGIC: PROCESSING POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $m_p = $_POST['month'] ?? date('m');
    $y_p = $_POST['year'] ?? date('Y');

    if (isset($_POST['save_targets'])) {
        $ira = $_POST['annual_ira'];
        $target = $_POST['monthly_target'];
        $conn->query("INSERT INTO budget_settings (year, monthly_target, annual_ira) VALUES ('$y_p', '$target', '$ira') ON DUPLICATE KEY UPDATE annual_ira='$ira', monthly_target='$target'");
    }

    if (isset($_POST['save_allotments'])) {
        foreach ($_POST['amounts'] as $cat => $amt) {
            $conn->query("INSERT INTO budget_allotments (category, amount, month, year) VALUES ('$cat', '$amt', '$m_p', '$y_p') ON DUPLICATE KEY UPDATE amount='$amt'");
        }
    }

    if (isset($_POST['btn_save_payroll'])) {
        $pay_date = $_POST['pay_date'];
        $fund = $_POST['pay_fund'];
        $remarks = mysqli_real_escape_string($conn, $_POST['pay_remarks']);
        $total_payroll = 0; $items = [];
        if(!empty($_POST['staff'])){
            foreach($_POST['staff'] as $role => $amount){
                if($amount > 0){ $total_payroll += $amount; $items[] = "$role: ₱" . number_format($amount); }
            }
        }
        $full_desc = "Payroll ($remarks): " . implode(", ", $items);
        $conn->query("INSERT INTO finance (description, amount, type, category, date_transacted, ref_no) VALUES ('$full_desc', $total_payroll, 'Expense', '$fund', '$pay_date', 'PAYROLL')");
    }
    echo "<script>window.location.href='finance.php?m=$m_p&y=$y_p';</script>";
    exit();
}

// --- 3. DATA FETCHING & DYNAMIC COLUMN DETECTION ---
$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');
$display_date = date('F Y', mktime(0, 0, 0, $m, 1, $y));

// Safer Column Detection
$check_cols = $conn->query("SHOW COLUMNS FROM officials");
$cols = [];
if($check_cols) { while($c = $check_cols->fetch_assoc()) { $cols[] = $c['Field']; } }

$name_col = in_array('fullname', $cols) ? 'fullname' : (in_array('name', $cols) ? 'name' : 'name');
$filter_col = in_array('category', $cols) ? 'category' : (in_array('role', $cols) ? 'role' : $name_col);

// REVENUE SYNC CODE ADDED HERE
$check_date_col = $conn->query("SHOW COLUMNS FROM document_requests LIKE 'date_requested'");
$date_col_name = ($check_date_col && $check_date_col->num_rows > 0) ? 'date_requested' : 'date_transacted';

$clearance_sync = $conn->query("SELECT SUM(amount) as total FROM document_requests WHERE status='Approved' AND MONTH($date_col_name) = '$m' AND YEAR($date_col_name) = '$y'");
$clearance_income = $clearance_sync->fetch_assoc()['total'] ?? 0;

// Officials Queries (Fixed Syntax)
$sk_officials = $conn->query("SELECT *, $name_col AS display_name FROM officials WHERE $filter_col LIKE '%SK%' OR $filter_col LIKE '%Kabataan%'");
$barangay_officials = $conn->query("SELECT *, $name_col AS display_name FROM officials WHERE ($filter_col LIKE '%Official%' OR $filter_col LIKE '%Kagawad%' OR $filter_col LIKE '%Captain%') AND $filter_col NOT LIKE '%SK%'");
$bhw_workers = $conn->query("SELECT *, $name_col AS display_name FROM officials WHERE $filter_col LIKE '%BHW%' OR $filter_col LIKE '%Health%'");
$tanods = $conn->query("SELECT *, $name_col AS display_name FROM officials WHERE $filter_col LIKE '%Tanod%'");

$payroll_total = $conn->query("SELECT SUM(amount) as total FROM finance WHERE ref_no='PAYROLL' AND MONTH(date_transacted) = '$m' AND YEAR(date_transacted) = '$y'")->fetch_assoc()['total'] ?? 0;

$yearly_fin = $conn->query("SELECT SUM(amount) as total FROM finance WHERE YEAR(date_transacted) = '$y' AND type='Expense'")->fetch_assoc()['total'] ?? 0;
$yearly_proj = $conn->query("SELECT SUM(budget) as total FROM projects WHERE YEAR(date_started) = '$y'")->fetch_assoc()['total'] ?? 0;
$grand_total_yearly_spent = $yearly_fin + $yearly_proj;

$rev = $conn->query("SELECT * FROM budget_settings WHERE year = '$y' LIMIT 1")->fetch_assoc();
$annual_ira = $rev['annual_ira'] ?? 0;
$monthly_budget = $rev['monthly_target'] ?? 0;

$funds = ['General Fund', '20% Development Fund', 'SK Fund (10%)', 'LDRRMF (Calamity)', 'BDRRM Fund'];
$fund_data = []; $total_spent = 0;

foreach($funds as $fund) {
    $limit = $conn->query("SELECT amount FROM budget_allotments WHERE category='$fund' AND month='$m' AND year='$y'")->fetch_assoc()['amount'] ?? 0;
    $fin_spent = $conn->query("SELECT SUM(amount) as total FROM finance WHERE category='$fund' AND type='Expense' AND MONTH(date_transacted) = '$m' AND YEAR(date_transacted) = '$y'")->fetch_assoc()['total'] ?? 0;
    $proj_spent = $conn->query("SELECT SUM(budget) as total FROM projects WHERE fund_source='$fund' AND MONTH(date_started) = '$m' AND YEAR(date_started) = '$y'")->fetch_assoc()['total'] ?? 0;
    $combined = $fin_spent + $proj_spent;
    $fund_data[] = ['name' => $fund, 'limit' => $limit, 'spent' => $combined, 'bal' => $limit - $combined];
    $total_spent += $combined;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Dashboard | Barangay OS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { background: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; margin: 0; }
        .sidebar-container { width: 260px; position: fixed; height: 100vh; z-index: 1000; }
        .main-content { margin-left: 260px; padding: 40px 50px; width: calc(100% - 260px); }
        .glass-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .yearly-banner { background: linear-gradient(135deg, #0f172a 0%, #334155 100%); color: white; border-radius: 24px; padding: 30px; margin-bottom: 40px; position: relative; overflow: hidden; }
        .stat-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; }
        .badge-payroll { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-collection { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-sk { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .clickable-header { cursor: pointer; transition: opacity 0.2s; }
        .clickable-header:hover { opacity: 0.8; }
        .staff-list-item { font-size: 13px; border-bottom: 1px dashed #eee; padding: 5px 0; }

        @media print { 
            .no-print { display: none !important; } 
            .main-content { margin-left: 0; width: 100%; padding: 0; }
            .collapse { display: block !important; height: auto !important; }
            .signature-space { display: flex !important; margin-top: 50px; }
        }
        .signature-space { display: none; }
    </style>
</head>
<body>

<div class="sidebar-container no-print">
    <?php include 'sidebar.php'; ?>
</div>

<main class="main-content">
    <div id="printBanner" class="yearly-banner shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="fw-700 opacity-75 mb-1">Fiscal Year <?= $y ?> Summary</h5>
                <h2 class="fw-800 mb-0">₱<?= number_format($grand_total_yearly_spent, 2) ?></h2>
                <p class="small mb-0 mt-2"><i class="fas fa-chart-line me-1 text-warning"></i> Total Expenses for <?= $y ?></p>
            </div>
            <div class="col-md-3 text-center border-start border-secondary">
                <span class="stat-label text-light opacity-50">Annual IRA</span>
                <div class="h4 fw-800 mb-0">₱<?= number_format($annual_ira, 2) ?></div>
            </div>
            <div class="col-md-3 text-center border-start border-secondary">
                <span class="stat-label text-light opacity-50">Utilization Rate</span>
                <div class="h4 fw-800 mb-0"><?= $annual_ira > 0 ? round(($grand_total_yearly_spent/$annual_ira)*100, 1) : 0 ?>%</div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h1 class="fw-800 mb-0" style="font-size: 2.2rem; letter-spacing: -1.5px;"><?= $display_date ?></h1>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 fw-bold">Financial Records</span>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-dark fw-bold rounded-3 px-3" onclick="window.print()"><i class="fas fa-print me-2"></i>Full Print</button>
            <button class="btn btn-warning fw-bold px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#payrollModal">Payroll</button>
            <button class="btn btn-primary fw-bold px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#revModal">Settings</button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div id="printClearance" class="glass-card border-start border-4 border-info h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="stat-label">Clearance Fees</span>
                        <div class="stat-value text-info mt-1">₱<?= number_format($clearance_income, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3"><div class="glass-card h-100"><span class="stat-label">SK 10% Allotment</span><div class="stat-value text-orange mt-1">₱<?= number_format($monthly_budget * 0.10, 2) ?></div></div></div>
        <div class="col-md-3"><div class="glass-card h-100"><span class="stat-label">Total Spent</span><div class="stat-value text-danger mt-1">₱<?= number_format($total_spent, 2) ?></div></div></div>
        <div class="col-md-3"><div class="glass-card bg-success text-white h-100"><span class="stat-label text-white opacity-75">Net Available</span><div class="stat-value mt-1">₱<?= number_format(($monthly_budget + $clearance_income) - $total_spent, 2) ?></div></div></div>
    </div>

    <div id="sectionPayroll" class="glass-card p-0 overflow-hidden">
        <div class="p-4 d-flex justify-content-between align-items-center bg-light border-bottom clickable-header" data-bs-toggle="collapse" data-bs-target="#collapsePayroll">
            <h6 class="fw-800 mb-0 text-uppercase small tracking-wider"><i class="fas fa-users-cog me-2 opacity-50"></i>Unified Payroll & Honoraria Breakdown</h6>
            <button class="btn btn-sm btn-dark px-3 fw-bold rounded-pill no-print" onclick="event.stopPropagation(); printSection('sectionPayroll', 'Personnel Payroll Report')">Print Payroll</button>
        </div>
        <div id="collapsePayroll" class="collapse show">
            <div class="p-4">
                <div class="row g-4">
                    <div class="col-md-3 border-end">
                        <span class="stat-label text-warning mb-2 d-block">Sangguniang Kabataan</span>
                        <?php if($sk_officials && $sk_officials->num_rows > 0): while($sk = $sk_officials->fetch_assoc()): ?>
                            <div class="staff-list-item d-flex justify-content-between">
                                <span><?= $sk['display_name'] ?> <br><small class="text-muted"><?= $sk[$filter_col] ?></small></span>
                                <span class="fw-bold text-muted small">₱--</span>
                            </div>
                        <?php endwhile; else: echo "<p class='small text-muted'>No SK staff recorded.</p>"; endif; ?>
                    </div>

                    <div class="col-md-3 border-end">
                        <span class="stat-label text-primary mb-2 d-block">Barangay Officials</span>
                        <?php if($barangay_officials): while($off = $barangay_officials->fetch_assoc()): ?>
                            <div class="staff-list-item d-flex justify-content-between">
                                <span><?= $off['display_name'] ?> <br><small class="text-muted"><?= $off[$filter_col] ?></small></span>
                                <span class="fw-bold text-muted small">₱--</span>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>

                    <div class="col-md-3 border-end">
                        <span class="stat-label text-success mb-2 d-block">Health Workers</span>
                        <?php if($bhw_workers): while($bhw = $bhw_workers->fetch_assoc()): ?>
                            <div class="staff-list-item d-flex justify-content-between">
                                <span><?= $bhw['display_name'] ?></span>
                                <span class="fw-bold text-muted small">₱--</span>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>

                    <div class="col-md-3">
                        <span class="stat-label text-danger mb-2 d-block">Barangay Tanods</span>
                        <?php if($tanods): while($tan = $tanods->fetch_assoc()): ?>
                            <div class="staff-list-item d-flex justify-content-between">
                                <span><?= $tan['display_name'] ?></span>
                                <span class="fw-bold text-muted small">₱--</span>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top text-end">
                    <h5 class="fw-800">Total Monthly Release: <span class="text-danger">₱<?= number_format($payroll_total, 2) ?></span></h5>
                </div>

                <div class="signature-space row mt-5">
                    <div class="col-4 text-center"><hr class="mx-3"><small class="fw-bold">Barangay Treasurer</small></div>
                    <div class="col-4 text-center"><hr class="mx-3"><small class="fw-bold">Barangay Secretary</small></div>
                    <div class="col-4 text-center"><hr class="mx-3"><small class="fw-bold">Punong Barangay</small></div>
                </div>
            </div>
        </div>
    </div>

    <div id="sectionAppropriation" class="glass-card p-0 overflow-hidden mt-4">
        <div class="p-4 d-flex justify-content-between align-items-center bg-light border-bottom clickable-header" data-bs-toggle="collapse" data-bs-target="#collapseApprop">
            <h6 class="fw-800 mb-0 text-uppercase small tracking-wider"><i class="fas fa-file-invoice-dollar me-2 opacity-50"></i>Appropriation & Allotment Control</h6>
        </div>
        <div id="collapseApprop" class="collapse show">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="bg-light border-bottom"><tr class="text-muted small"><th class="ps-4">Fund Source</th><th>Allotment</th><th>Actual Spent</th><th class="text-end pe-4">Remaining Balance</th></tr></thead>
                    <tbody>
                        <?php foreach($fund_data as $row): ?>
                        <tr>
                            <td class="ps-4 fw-800"><?= $row['name'] ?></td>
                            <td>₱<?= number_format($row['limit'], 2) ?></td>
                            <td class="text-danger">₱<?= number_format($row['spent'], 2) ?></td>
                            <td class="text-end pe-4">
                                <span class="badge rounded-pill px-3 py-2 <?= $row['bal'] < 0 ? 'bg-danger' : 'bg-success' ?>">
                                    ₱<?= number_format($row['bal'], 2) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="sectionLedger" class="glass-card p-0 overflow-hidden shadow-sm mt-4">
        <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center clickable-header" data-bs-toggle="collapse" data-bs-target="#collapseLedger">
            <h6 class="fw-800 mb-0 text-uppercase small tracking-wider"><i class="fas fa-history me-2 opacity-50"></i>Unified Transaction Ledger</h6>
            <div class="d-flex gap-2">
                <input type="text" id="ledgerSearch" class="form-control form-control-sm bg-light border-0 no-print" style="width:180px" placeholder="Search transactions..." onclick="event.stopPropagation()">
            </div>
        </div>
        <div id="collapseLedger" class="collapse show">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover mb-0" id="ledgerTable">
                    <thead class="sticky-top bg-white border-bottom"><tr class="text-muted small"><th class="ps-4">Date</th><th>Source Fund</th><th>Description</th><th class="text-end pe-4">Amount</th><th class="text-center no-print">Action</th></tr></thead>
                    <tbody>
                        <?php 
                        $sql = "(SELECT id, date_transacted as d, ref_no as r, description as ds, category as c, amount as a, type as t, 'finance' as origin FROM finance WHERE MONTH(date_transacted) = '$m' AND YEAR(date_transacted) = '$y')
                                UNION 
                                (SELECT id, date_started as d, 'PROJECT' as r, project_name as ds, fund_source as c, budget as a, 'Expense' as t, 'projects' as origin FROM projects WHERE MONTH(date_started) = '$m' AND YEAR(date_started) = '$y')
                                ORDER BY d DESC";
                        $res = $conn->query($sql);
                        if($res):
                        while($l = $res->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 small fw-bold text-muted"><?= date('M d, Y', strtotime($l['d'])) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= ($l['c'] == 'SK Fund (10%)') ? 'badge-sk' : (($l['r'] == 'PAYROLL') ? 'badge-payroll' : 'bg-secondary-subtle text-secondary') ?>">
                                    <?= $l['c'] ?>
                                </span>
                            </td>
                            <td class="small fw-600 text-uppercase"><?= substr($l['ds'], 0, 100) ?></td>
                            <td class="text-end pe-4 fw-800 <?= $l['t'] == 'Income' ? 'text-success' : 'text-danger' ?>">₱<?= number_format($l['a'], 2) ?></td>
                            <td class="text-center no-print">
                                <?php if($l['origin'] == 'finance'): ?>
                                    <a href="finance.php?delete_id=<?= $l['id'] ?>&m=<?= $m ?>&y=<?= $y ?>" class="text-danger opacity-50" onclick="return confirm('Confirm deletion?')"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include 'finance_modals.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function printSection(id, title) {
        var content = document.getElementById(id).innerHTML;
        var win = window.open('', '', 'height=700,width=1000');
        win.document.write('<html><head><title>Print Report</title>');
        win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        win.document.write('<style>body{padding:20px;} .no-print { display: none !important; } .collapse { display: block !important; } .signature-space { display: flex !important; }</style>');
        win.document.write('</head><body><div class="container mt-5">');
        win.document.write('<div class="text-center mb-4"><h2>Barangay Financial Records</h2><h4>' + title + '</h4><p><?= $display_date ?></p></div>');
        win.document.write(content);
        win.document.write('</div></body></html>');
        setTimeout(() => { win.print(); win.close(); }, 500);
    }

    $("#ledgerSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#ledgerTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
</script>
</body>
</html>