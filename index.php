<?php 
// 1. MUST START SESSION FIRST
session_start(); 

include 'db_connect.php'; 

// 2. SECURITY GATEKEEPER - Simplified to match our login logic
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){ 
    header("Location: admin_login.php"); 
    exit(); 
}   

// --- 1. POPULATION STATS ---
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 2 THEN 1 ELSE 0 END) as infants,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 2 AND 12 THEN 1 ELSE 0 END) as children,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 19 THEN 1 ELSE 0 END) as teens,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 20 AND 59 THEN 1 ELSE 0 END) as adults,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END) as seniors
FROM residents";
$stats = $conn->query($stats_query)->fetch_assoc();

// --- 2. REQUEST STATS ---
$request_query = "SELECT 
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_req,
    COUNT(CASE WHEN status = 'Approved' AND DATE(date_requested) = CURDATE() THEN 1 END) as issued_today
FROM document_requests";
$req_stats = $conn->query($request_query)->fetch_assoc();

// --- 3. LIVE ONLINE COUNTER (The Fix) ---
$online_query = $conn->query("SELECT COUNT(*) as online_total FROM portal_activity WHERE last_seen > NOW() - INTERVAL 5 MINUTE");
$online_count = $online_query->fetch_assoc()['online_total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BMIS Official Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-width: 280px; 
            --bg-main: #f1f5f9; 
            --accent: #3b82f6;
            --glass: rgba(255, 255, 255, 0.7);
        }
        
        body { background-color: var(--bg-main); font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; overflow-x: hidden; }
        
        .sidebar-container { width: var(--sidebar-width); height: 100vh; position: fixed; z-index: 1000; }
        .dashboard-wrapper { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; min-height: 100vh; }

        /* Modern Action Cards */
        .hero-card { 
            border-radius: 24px; padding: 30px; position: relative; overflow: hidden; 
            transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        }
        .hero-card:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1); }
        .hero-card.primary { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; }
        .hero-card.success { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; }
        .hero-card.info { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; }

        /* Online Badge Animation */
        .pulse-online { 
            display: inline-block; width: 10px; height: 10px; background: #4ade80; 
            border-radius: 50%; margin-right: 8px; box-shadow: 0 0 10px #4ade80;
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring { 0% { transform: scale(0.8); opacity: 1; } 100% { transform: scale(2); opacity: 0; } }

        /* Demographic Stats */
        .demographic-box { 
            background: white; border-radius: 20px; padding: 20px; border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .demographic-box:hover { border-color: var(--accent); background: #f8fafc; }
        
        .icon-box { 
            width: 48px; height: 48px; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; margin-bottom: 15px; 
        }

        .glass-box { background: var(--glass); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid #ffffff; padding: 30px; }

        @media (max-width: 991px) {
            .sidebar-container { display: none; }
            .dashboard-wrapper { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar-container">
    <?php include 'sidebar.php'; ?>
</div>

<main class="dashboard-wrapper">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-bold">ADMIN PANEL v2.0</span>
            <h1 class="fw-bold mb-0">Overview</h1>
        </div>
        <div class="d-flex align-items-center mt-3 mt-md-0">
            <div class="me-3 text-end">
                <p class="small text-muted mb-0">Local Time</p>
                <p class="fw-bold mb-0"><?= date('h:i A') ?></p>
            </div>
            <div class="bg-white p-3 rounded-4 shadow-sm border border-light">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                <span class="fw-semibold"><?= date('M d, Y') ?></span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <div class="hero-card info">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase small fw-bold opacity-75">Online Residents</h6>
                        <h1 class="fw-bold mb-1"><?= $online_count ?></h1>
                        <p class="small mb-3"><span class="pulse-online"></span>Live connection status</p>
                        <a href="account.php" class="btn btn-sm btn-light rounded-pill px-4 fw-bold text-primary">View Active Users</a>
                    </div>
                    <i class="fas fa-users-viewfinder fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="hero-card primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase small fw-bold opacity-75">Pending Requests</h6>
                        <h1 class="fw-bold mb-1"><?= $req_stats['pending_req'] ?></h1>
                        <p class="small mb-3">Awaiting Administrative review</p>
                        <a href="clearance.php" class="btn btn-sm btn-outline-light rounded-pill px-4 fw-bold">Process Now</a>
                    </div>
                    <i class="fas fa-clock-rotate-left fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="hero-card success">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase small fw-bold opacity-75">Documents Issued</h6>
                        <h1 class="fw-bold mb-1"><?= $req_stats['issued_today'] ?></h1>
                        <p class="small mb-3">Total accomplished today</p>
                        <div class="progress bg-white bg-opacity-25" style="height: 6px; width: 120px; border-radius: 10px;">
                            <div class="progress-bar bg-white" style="width: 70%"></div>
                        </div>
                    </div>
                    <i class="fas fa-certificate fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="glass-box h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Resident Demographics</h5>
                    <button class="btn btn-light btn-sm rounded-circle"><i class="fas fa-ellipsis-v"></i></button>
                </div>
                
                <div class="row g-3">
                    <?php 
                    $demo = [
                        ['Total', $stats['total'], 'blue', 'fa-users'],
                        ['Infants', $stats['infants'], 'purple', 'fa-baby'],
                        ['Children', $stats['children'], 'orange', 'fa-child'],
                        ['Teens', $stats['teens'], 'amber', 'fa-walking'],
                        ['Adults', $stats['adults'], 'emerald', 'fa-user-tie'],
                        ['Seniors', $stats['seniors'], 'slate', 'fa-person-cane']
                    ];
                    $colors = ['blue'=>'#3b82f6', 'purple'=>'#8b5cf6', 'orange'=>'#f97316', 'amber'=>'#f59e0b', 'emerald'=>'#10b981', 'slate'=>'#64748b'];
                    
                    foreach($demo as $d): ?>
                    <div class="col-6 col-sm-4 col-md-4">
                        <div class="demographic-box text-center">
                            <div class="icon-box mx-auto" style="background: <?= $colors[$d[2]] ?>15; color: <?= $colors[$d[2]] ?>">
                                <i class="fas <?= $d[3] ?>"></i>
                            </div>
                            <p class="small text-muted fw-bold mb-1 text-uppercase"><?= $d[0] ?></p>
                            <h4 class="fw-bold mb-0"><?= number_format($d[1]) ?></h4>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-box h-100 shadow-sm text-center d-flex flex-column justify-content-center">
                <h6 class="fw-bold text-muted mb-4">POPULATION RATIO</h6>
                <div style="height: 250px;">
                    <canvas id="ageDistributionChart"></canvas>
                </div>
                <div class="mt-4 pt-3 border-top border-light">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Voters</small>
                            <span class="fw-bold fs-5 text-primary"><?= $stats['adults']+$stats['seniors'] ?></span>
                        </div>
                        <div class="col-6 border-start border-light">
                            <small class="text-muted d-block">Minors</small>
                            <span class="fw-bold fs-5 text-warning"><?= $stats['infants']+$stats['children']+$stats['teens'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('ageDistributionChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Infants', 'Children', 'Teens', 'Adults', 'Seniors'],
            datasets: [{
                data: [<?= $stats['infants'] ?>, <?= $stats['children'] ?>, <?= $stats['teens'] ?>, <?= $stats['adults'] ?>, <?= $stats['seniors'] ?>],
                backgroundColor: ['#8b5cf6', '#f97316', '#f59e0b', '#10b981', '#64748b'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false } 
            },
            cutout: '80%',
            borderRadius: 10
        }
    });
</script>
</body>
</html>