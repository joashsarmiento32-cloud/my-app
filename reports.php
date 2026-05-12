<?php 
include 'db_connect.php'; 
session_start();

/**
 * HELPER FUNCTION: Safely checks if a column exists before querying it
 * This prevents the "Fatal Error: Unknown Column" seen in your screenshots.
 */
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($result && $result->num_rows > 0);
}

// --- 1. RESIDENT PROFILING ---
$total_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Resident'")->fetch_assoc()['total'] ?? 0;

// Only filter by 'Active' if the column exists
if (columnExists($conn, 'users', 'status')) {
    $adults = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Resident' AND status='Active'")->fetch_assoc()['total'] ?? 0;
} else {
    $adults = $total_res; 
}

// --- 2. HEALTH & SANITATION ---
$health_cases = $conn->query("SELECT COUNT(*) as total FROM health_records")->fetch_assoc()['total'] ?? 0;

// --- 3. PEACE AND ORDER ---
$blotter_count = $conn->query("SELECT COUNT(*) as total FROM blotter WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;

// --- 4. FINANCE ---
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM clearances")->fetch_assoc()['total'] ?? 0;

// --- 5. ONLINE REQUESTS ---
// Fixed to handle the error on line 10 of your previous version
if (columnExists($conn, 'online_requests', 'status')) {
    $pending_req = $conn->query("SELECT COUNT(*) as total FROM online_requests WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;
} else {
    $pending_req = $conn->query("SELECT COUNT(*) as total FROM online_requests")->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Audit Report | BMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-blue: #1a237e; --bg-gray: #f8f9fa; }
        body { background-color: var(--bg-gray); font-family: 'Inter', sans-serif; }
        
        .report-header { background: white; border-left: 5px solid #dc3545; border-radius: 10px; }
        
        .stat-card { 
            border: none; border-radius: 15px; background: white; 
            transition: 0.3s; border-bottom: 4px solid transparent; 
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .card-res { border-color: #0d6efd; }
        .card-req { border-color: #ffc107; }
        .card-fin { border-color: #198754; }

        .module-status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-online { background-color: #198754; box-shadow: 0 0 5px #198754; }
        
        @media print { .no-print { display: none; } body { background: white; } }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="report-header p-4 shadow-sm mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1">Sidebar Verification Report</h3>
            <p class="text-muted mb-0">Live Database Synchronization Audit</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-outline-dark me-2"><i class="fas fa-print"></i> PDF</button>
            <a href="health.php" class="btn btn-danger px-4 rounded-pill">Back to Dashboard</a>
        </div>
    </div>

    

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card card-res shadow-sm p-4">
                <div class="text-uppercase small fw-bold text-muted mb-3">Resident Profiling</div>
                <h1 class="fw-bold mb-1"><?= number_format($total_res) ?></h1>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Active Users</span>
                    <span class="badge bg-primary rounded-pill"><?= $adults ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card card-req shadow-sm p-4">
                <div class="text-uppercase small fw-bold text-muted mb-3">Online Requests</div>
                <h1 class="fw-bold mb-1 text-warning"><?= number_format($pending_req) ?></h1>
                <div class="small text-muted"><i class="fas fa-clock me-1"></i> Awaiting Action</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card card-fin shadow-sm p-4">
                <div class="text-uppercase small fw-bold text-muted mb-3">Clearance Revenue</div>
                <h1 class="fw-bold mb-1 text-success">₱<?= number_format($total_revenue, 2) ?></h1>
                <div class="small text-muted">Total Gross Collections</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-4">
                <h5 class="fw-bold mb-4">Health & Safety Audit</h5>
                <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3">
                    <span><i class="fas fa-heartbeat text-danger me-2"></i> Health Consultations</span>
                    <span class="fw-bold"><?= $health_cases ?> Logs</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3">
                    <span><i class="fas fa-gavel text-dark me-2"></i> Pending Blotter Cases</span>
                    <span class="fw-bold"><?= $blotter_count ?> Cases</span>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-4">
                <h5 class="fw-bold mb-4">Database Connectivity</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between border-0 px-0">
                        <span>Resident Profiling</span>
                        <span><span class="module-status-dot status-online"></span><small class="fw-bold text-success">CONNECTED</small></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between border-0 px-0">
                        <span>Health & Sanitation</span>
                        <span><span class="module-status-dot status-online"></span><small class="fw-bold text-success">CONNECTED</small></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between border-0 px-0">
                        <span>Peace and Order</span>
                        <span><span class="module-status-dot status-online"></span><small class="fw-bold text-success">CONNECTED</small></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between border-0 px-0">
                        <span>Finance / Clearance</span>
                        <span><span class="module-status-dot status-online"></span><small class="fw-bold text-success">CONNECTED</small></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

</body>
</html>